<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Uniforme, UniformeEstoque, EntregaUniforme, Tamanho, Colaborador, Empresa};
use App\Models\Setor;
use App\Models\Funcao;
use Carbon\Carbon;

class UniformeController extends Controller {

    private function empresasDisponiveis() {
        return auth()->user()->isSuperAdmin()
            ? Empresa::ativas()->orderBy('razao_social')->get()
            : collect([auth()->user()->empresa]);
    }

    public function index(Request $r) {
        $q = Uniforme::with(['empresa','estoques.tamanho']);
        if ($r->search)    $q->where('nome','ilike',"%{$r->search}%");
        if ($r->tipo)      $q->where('tipo',$r->tipo);
        if ($r->empresa_id) $q->where('empresa_id',$r->empresa_id);
        $uniformes = $q->orderBy('nome')->paginate(20)->withQueryString();
        $tamanhos  = Tamanho::orderBy('ordem')->get();
        $empresas  = $this->empresasDisponiveis();
        return view('uniformes.index',compact('uniformes','tamanhos','empresas'));
    }

    public function create() {
        $tamanhos = Tamanho::orderBy('ordem')->get();
        $empresas = $this->empresasDisponiveis();
        return view('uniformes.form',['uniforme'=>null,'tamanhos'=>$tamanhos,'empresas'=>$empresas]);
    }

    public function store(Request $r) {
        $r->validate(['nome'=>'required','tipo'=>'required']);
        $uni = Uniforme::create($r->only(['nome','tipo','descricao','fornecedor','custo_unitario','status','empresa_id','estoque_minimo_padrao']));
        // auto-create grade padrão
        if ($r->grade_tamanhos) {
            foreach ($r->grade_tamanhos as $tamId => $minimo) {
                UniformeEstoque::create([
                    'uniforme_id' => $uni->id,
                    'tamanho_id'  => $tamId,
                    'quantidade'  => 0,
                    'minimo'      => (int)($minimo ?? $r->estoque_minimo_padrao ?? 0),
                ]);
            }
        }
        return redirect()->route('uniformes.index')->with('success','Uniforme cadastrado com grade!');
    }

    public function edit(Uniforme $uniforme) {
        $tamanhos = Tamanho::orderBy('ordem')->get();
        $empresas = $this->empresasDisponiveis();
        return view('uniformes.form',compact('uniforme','tamanhos','empresas'));
    }

    public function update(Request $r, Uniforme $uniforme) {
        $r->validate(['nome'=>'required','tipo'=>'required']);
        $uniforme->update($r->only(['nome','tipo','descricao','fornecedor','custo_unitario','status','empresa_id','estoque_minimo_padrao']));
        return redirect()->route('uniformes.index')->with('success','Uniforme atualizado!');
    }

    public function destroy(Uniforme $uniforme) {
        $uniforme->delete();
        return redirect()->route('uniformes.index')->with('success','Uniforme excluído!');
    }

    public function show(Uniforme $uniforme) { return redirect()->route('uniformes.index'); }

    public function updateEstoque(Request $r, Uniforme $uniforme) {
        $r->validate(['tamanho_id'=>'required','quantidade'=>'required|integer|min:0']);
        UniformeEstoque::updateOrCreate(
            ['uniforme_id'=>$uniforme->id,'tamanho_id'=>$r->tamanho_id],
            ['quantidade'=>$r->quantidade,'minimo'=>$r->minimo??0]
        );
        return back()->with('success','Estoque atualizado!');
    }

    public function grade(Request $r) {
        $q = Uniforme::with(['estoques.tamanho','empresa'])->where('status','Ativo');
        if ($r->empresa_id) $q->where('empresa_id',$r->empresa_id);
        if ($r->tipo)       $q->where('tipo',$r->tipo);
        if ($r->search)     $q->where('nome','ilike',"%{$r->search}%");
        $uniformes = $q->orderBy('nome')->get();
        $tamanhos  = Tamanho::orderBy('ordem')->get();
        $empresas  = $this->empresasDisponiveis();
        return view('uniformes.grade',compact('uniformes','tamanhos','empresas'));
    }

    public function entregas(Request $r) {
        $q = EntregaUniforme::with(['colaborador','uniforme','tamanho','empresa']);
        if ($r->empresa_id)  $q->where('empresa_id',$r->empresa_id);
        if ($r->uniforme_id) $q->where('uniforme_id',$r->uniforme_id);
        if ($r->setor_id)    $q->whereHas('colaborador',fn($c)=>$c->where('setor_id',$r->setor_id));
        if ($r->funcao_id)   $q->whereHas('colaborador',fn($c)=>$c->where('funcao_id',$r->funcao_id));
        if ($r->nome)        $q->whereHas('colaborador',fn($c)=>$c->where('nome','ilike',"%{$r->nome}%"));
        if ($r->de)          $q->where('data_entrega','>=',$r->de);
        if ($r->ate)         $q->where('data_entrega','<=',$r->ate);
        $entregas       = $q->orderByDesc('data_entrega')->paginate(30)->withQueryString();
        $uniformes_list = Uniforme::ativos()->orderBy('nome')->get();
        $tamanhos       = Tamanho::orderBy('ordem')->get();
        $empresas       = $this->empresasDisponiveis();
        $setores        = Setor::orderBy('nome')->get();
        $funcoes        = Funcao::orderBy('nome')->get();
        return view('uniformes.entregas',compact('entregas','uniformes_list','tamanhos','empresas','setores','funcoes'));
    }

    public function storeEntrega(Request $r) {
        $r->validate([
            'colaborador_id'           => 'required',
            'data_entrega'             => 'required|date',
            'items'                    => 'required|array|min:1',
            'items.*.uniforme_id'      => 'required',
            'items.*.tamanho_id'       => 'required',
            'items.*.quantidade'       => 'required|integer|min:1',
        ]);
        $eid = $r->empresa_id ?? auth()->user()->empresa_id;
        foreach ($r->items as $item) {
            EntregaUniforme::create([
                'empresa_id'          => $eid,
                'colaborador_id'      => $r->colaborador_id,
                'uniforme_id'         => $item['uniforme_id'],
                'tamanho_id'          => $item['tamanho_id'],
                'quantidade'          => $item['quantidade'],
                'data_entrega'        => $r->data_entrega,
                'data_prevista_troca' => $r->data_prevista_troca ?? null,
                'motivo'              => $r->motivo ?? 'admissao',
                'responsavel'         => $r->responsavel ?? auth()->user()->name,
                'observacoes'         => $r->observacoes ?? null,
            ]);
            $est = UniformeEstoque::where(['uniforme_id'=>$item['uniforme_id'],'tamanho_id'=>$item['tamanho_id']])->first();
            if ($est) $est->decrement('quantidade',$item['quantidade']);
        }
        $n = count($r->items);
        return back()->with('success', $n.' item'.($n>1?'s':'').' registrado'.($n>1?'s':'').'!');
    }

    public function ficha(Colaborador $colaborador) {
        $entregas = EntregaUniforme::with(['uniforme','tamanho'])
            ->where('colaborador_id',$colaborador->id)
            ->orderByDesc('data_entrega')->get();
        return view('uniformes.ficha',compact('colaborador','entregas'));
    }
}
