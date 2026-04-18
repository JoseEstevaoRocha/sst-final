<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{EPI, EPIEstoque, EntregaEPI, EpiMovimentacao, Colaborador, Empresa};
use App\Services\CaEpiService;
use Carbon\Carbon;

class EPIController extends Controller {
    public function index(Request $r) {
        $q = EPI::query();
        if ($r->search) $q->where(fn($sq)=>$sq->where('nome','ilike',"%{$r->search}%")->orWhere('numero_ca','ilike',"%{$r->search}%"));
        if ($r->tipo)   $q->where('tipo',$r->tipo);
        if ($r->status) $q->where('status',$r->status);
        $epis    = $q->withCount('entregas')->orderBy('nome')->paginate(20)->withQueryString();
        $empresas = auth()->user()->isSuperAdmin() ? Empresa::ativas()->get() : collect([auth()->user()->empresa]);
        $dash    = $this->calcDash();
        return view('epi.index',compact('epis','dash','empresas'));
    }
    public function create() {
        $tamanhos = \App\Models\Tamanho::orderBy('ordem')->get();
        return view('epi.form', ['epi'=>null, 'tamanhos'=>$tamanhos]);
    }
    public function store(Request $r, CaEpiService $caService) {
        $r->validate(['nome'=>'required','tipo'=>'required']);
        $dados = $r->only(['nome','descricao','tipo','numero_ca','validade_ca','fornecedor','fabricante','marca','vida_util_dias','estoque_minimo','unidade','custo_unitario','status']);
        $dados['tem_tamanho'] = $r->boolean('tem_tamanho');

        // Consulta CA e preenche dados automaticamente
        if (!empty($dados['numero_ca'])) {
            $ca = $caService->buscar($dados['numero_ca']);
            if ($ca) {
                $dados['ca_situacao'] = $ca['situacao'] ?? null;
                if (empty($dados['validade_ca']) && !empty($ca['data_validade'])) {
                    $dados['validade_ca'] = $ca['data_validade'];
                }
                if (empty($dados['fabricante']) && !empty($ca['razao_social'])) {
                    $dados['fabricante'] = $ca['razao_social'];
                }
            }
        }

        $epi = EPI::create($dados);
        if ($r->tem_tamanho && $r->filled('tamanho_ids')) {
            $epi->tamanhos()->sync($r->tamanho_ids);
        }

        if ($r->expectsJson()) {
            return response()->json($epi->load('tamanhos:id,codigo,descricao,ordem'));
        }
        return redirect()->route('epis.index')->with('success','EPI cadastrado!');
    }
    public function buscar(Request $r) {
        $q = $r->q ?? '';
        $epis = EPI::with('tamanhos:id,codigo,descricao,ordem')
            ->where(fn($sq) =>
                $sq->where('nome', 'ilike', "%{$q}%")
                   ->orWhere('numero_ca', 'ilike', "%{$q}%")
                   ->orWhere('marca', 'ilike', "%{$q}%")
            )
            ->where('status', 'Ativo')
            ->orderBy('nome')
            ->limit(15)
            ->get(['id','nome','tipo','numero_ca','validade_ca','ca_situacao','fabricante','marca','unidade','custo_unitario','tem_tamanho']);
        return response()->json($epis);
    }

    public function edit(EPI $epi) {
        $tamanhos = \App\Models\Tamanho::orderBy('ordem')->get();
        return view('epi.form', compact('epi', 'tamanhos'));
    }
    public function update(Request $r, EPI $epi, CaEpiService $caService) {
        $r->validate(['nome'=>'required','tipo'=>'required']);
        $dados = array_merge(
            $r->only(['nome','descricao','tipo','numero_ca','validade_ca','fornecedor','fabricante','marca','vida_util_dias','estoque_minimo','unidade','custo_unitario','status']),
            ['tem_tamanho' => $r->boolean('tem_tamanho')]
        );

        // Se o CA mudou, rebusca dados
        if (!empty($dados['numero_ca']) && $dados['numero_ca'] !== $epi->numero_ca) {
            $ca = $caService->buscar($dados['numero_ca']);
            if ($ca) {
                $dados['ca_situacao'] = $ca['situacao'] ?? null;
                if (empty($dados['validade_ca']) && !empty($ca['data_validade'])) {
                    $dados['validade_ca'] = $ca['data_validade'];
                }
            }
        }

        $epi->update($dados);
        if ($r->boolean('tem_tamanho') && $r->filled('tamanho_ids')) {
            $epi->tamanhos()->sync($r->tamanho_ids);
        } elseif (!$r->boolean('tem_tamanho')) {
            $epi->tamanhos()->detach();
        }
        return redirect()->route('epis.index')->with('success','EPI atualizado!');
    }
    public function destroy(EPI $epi) { $epi->update(['status'=>'Inativo']); return redirect()->route('epis.index')->with('success','EPI inativado!'); }
    public function show(EPI $epi) { return redirect()->route('epis.index'); }
    public function dashboard() {
        return view('epi.dashboard',['dash'=>$this->calcDash(),'estoquesBaixos'=>EPIEstoque::with(['epi','empresa'])->whereColumn('quantidade','<=',\DB::raw('(SELECT estoque_minimo FROM epis WHERE epis.id=epi_estoques.epi_id)'))->get()]);
    }
    public function movimentar(Request $r, EPI $epi) {
        $r->validate(['empresa_id'=>'required','tipo'=>'required|in:entrada,saida,ajuste','quantidade'=>'required|integer|min:1']);
        $eid      = (int)$r->empresa_id;
        $qty      = (int)$r->quantidade;
        $tamId    = $r->tamanho_id ?: null;
        $est      = EPIEstoque::firstOrCreate(['epi_id'=>$epi->id,'empresa_id'=>$eid,'tamanho_id'=>$tamId],['quantidade'=>0]);
        $novo     = $r->tipo==='entrada' ? $est->quantidade+$qty : max(0,$est->quantidade-$qty);
        $est->update(['quantidade'=>$novo]);
        \App\Models\EpiMovimentacao::create(['epi_id'=>$epi->id,'empresa_id'=>$eid,'tipo'=>$r->tipo,'quantidade'=>$qty,'motivo'=>$r->motivo,'usuario'=>auth()->user()->name]);
        return back()->with('success',"Estoque atualizado! Saldo: {$novo}");
    }
    public function entregas(Request $r) {
        $q = EntregaEPI::with(['colaborador','epi','empresa']);
        if ($r->empresa_id) $q->where('empresa_id',$r->empresa_id);
        if ($r->epi_id)     $q->where('epi_id',$r->epi_id);
        $entregas = $q->orderByDesc('data_entrega')->paginate(20)->withQueryString();
        $epis_list = EPI::ativos()->orderBy('nome')->get();
        $empresas  = auth()->user()->isSuperAdmin() ? Empresa::ativas()->get() : collect([auth()->user()->empresa]);
        return view('epi.entregas',compact('entregas','epis_list','empresas'));
    }
    public function storeEntrega(Request $r) {
        $r->validate(['colaborador_id'=>'required|exists:colaboradores,id','epi_id'=>'required|exists:epis,id','quantidade'=>'required|integer|min:1','data_entrega'=>'required|date']);
        $epi = EPI::find($r->epi_id);
        $troca = $r->data_prevista_troca ?: ($epi->vida_util_dias ? Carbon::parse($r->data_entrega)->addDays($epi->vida_util_dias)->format('Y-m-d') : null);
        $eid = $r->empresa_id ?? auth()->user()->empresa_id;
        // Resolve tamanho text (código) from tamanho_id if provided
        $tamanhoId   = $r->tamanho_id ?: null;
        $tamanhoText = $r->tamanho ?: null;
        if ($tamanhoId) {
            $tam = \App\Models\Tamanho::find($tamanhoId);
            $tamanhoText = $tam?->codigo ?? $tamanhoText;
        }
        EntregaEPI::create(['empresa_id'=>$eid,'colaborador_id'=>$r->colaborador_id,'epi_id'=>$r->epi_id,'quantidade'=>$r->quantidade,'tamanho'=>$tamanhoText,'data_entrega'=>$r->data_entrega,'data_prevista_troca'=>$troca,'responsavel'=>$r->responsavel??auth()->user()->name,'observacoes'=>$r->observacoes,'status'=>'Ativo']);
        // Decrementar estoque (por tamanho se aplicável)
        $estoqueWhere = ['epi_id'=>$r->epi_id,'empresa_id'=>$eid,'tamanho_id'=>$tamanhoId];
        $est = EPIEstoque::firstOrCreate($estoqueWhere, ['quantidade'=>0]);
        $est->decrement('quantidade',$r->quantidade);
        return back()->with('success','Entrega registrada e estoque atualizado!');
    }
    public function grade(Request $r) {
        $q = EPI::with(['tamanhos:id,codigo,descricao,ordem','estoques.tamanho','estoques.empresa'])
            ->where('tem_tamanho', true)
            ->where('status','Ativo');
        if ($r->search)     $q->where('nome','ilike',"%{$r->search}%");
        if ($r->tipo)       $q->where('tipo', $r->tipo);
        if ($r->empresa_id) $q->whereHas('estoques', fn($e) => $e->where('empresa_id', $r->empresa_id));
        $epis      = $q->orderBy('nome')->get();
        $tamanhos  = \App\Models\Tamanho::orderBy('ordem')->get();
        $empresas  = auth()->user()->isSuperAdmin() ? Empresa::ativas()->get() : collect([auth()->user()->empresa]);
        return view('epi.grade', compact('epis','tamanhos','empresas'));
    }

    public function validade() {
        $vencidos  = EntregaEPI::with(['colaborador','epi'])->where('status','Ativo')->where('data_prevista_troca','<',today())->orderBy('data_prevista_troca')->paginate(20);
        $aVencer   = EntregaEPI::with(['colaborador','epi'])->where('status','Ativo')->whereBetween('data_prevista_troca',[today(),today()->addDays(60)])->orderBy('data_prevista_troca')->paginate(20);
        return view('epi.validade',compact('vencidos','aVencer'));
    }
    public function ficha(Colaborador $colaborador) {
        $entregas = EntregaEPI::with('epi')->where('colaborador_id',$colaborador->id)->orderByDesc('data_entrega')->get();
        return view('epi.ficha',compact('colaborador','entregas'));
    }
    private function calcDash(): array {
        $hoje = today(); $em60 = today()->addDays(60);
        return ['total_ativos'=>EPI::where('status','Ativo')->count(),'vencidos'=>EntregaEPI::where('status','Ativo')->where('data_prevista_troca','<',$hoje)->count(),'a_vencer_60'=>EntregaEPI::where('status','Ativo')->whereBetween('data_prevista_troca',[$hoje,$em60])->count(),'entregas_mes'=>EntregaEPI::where('created_at','>=',now()->startOfMonth())->count(),'estoque_baixo'=>EPIEstoque::whereColumn('quantidade','<=',\DB::raw('(SELECT estoque_minimo FROM epis WHERE epis.id=epi_estoques.epi_id)'))->count()];
    }
}
