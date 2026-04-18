<?php
namespace App\Http\Controllers;
use Illuminate\Http\{Request, JsonResponse};
use App\Models\{Setor, Funcao, Colaborador, Clinica, ASO, EPI, EPIEstoque, EntregaEPI, Extintor, WhatsappMensagem, Maquina, Mecanico};
use App\Services\CaEpiService;
use Carbon\Carbon;

class ApiController extends Controller {
    public function setores(Request $r): JsonResponse {
        $empresaId = $r->empresa_id ?? auth()->user()->empresa_id;
        return response()->json(Setor::where('empresa_id',$empresaId)->orderBy('nome')->select('id','nome')->get());
    }

    public function criarSetor(Request $r): JsonResponse {
        $r->validate(['nome' => 'required|string|max:100']);
        $empresaId = $r->empresa_id ?? auth()->user()->empresa_id;
        $setor = Setor::create(['nome' => $r->nome, 'empresa_id' => $empresaId]);
        return response()->json(['id' => $setor->id, 'nome' => $setor->nome]);
    }

    public function funcoes(Request $r): JsonResponse {
        $q = Funcao::orderBy('nome')->select('id','nome','cbo','periodicidade_aso_dias');
        if ($r->setor_id)   $q->where('setor_id',  $r->setor_id);
        if ($r->empresa_id) $q->where('empresa_id',$r->empresa_id);
        return response()->json($q->get());
    }

    public function criarFuncao(Request $r): JsonResponse {
        $r->validate(['nome' => 'required|string|max:150']);
        $empresaId = $r->empresa_id ?? auth()->user()->empresa_id;
        $funcao = Funcao::create([
            'nome'       => $r->nome,
            'cbo'        => $r->cbo,
            'setor_id'   => $r->setor_id ?: null,
            'empresa_id' => $empresaId,
        ]);
        return response()->json(['id' => $funcao->id, 'nome' => $funcao->nome]);
    }

    public function colaboradores(Request $r): JsonResponse {
        $q = Colaborador::with(['setor:id,nome','funcao:id,nome'])
            ->where('status','Contratado')
            ->orderBy('nome');
        $empresaId = $r->empresa_id ?? (!auth()->user()->isSuperAdmin() ? auth()->user()->empresa_id : null);
        if ($empresaId)  $q->where('empresa_id', $empresaId);
        if ($r->setor_id) $q->where('setor_id', $r->setor_id);
        if ($r->nome)    $q->where('nome','ilike',"%{$r->nome}%");
        return response()->json($q->get()->map(fn($c) => [
            'id'       => $c->id,
            'nome'     => $c->nome,
            'cpf'      => $c->cpf,
            'setor'    => $c->setor->nome ?? null,
            'funcao'   => $c->funcao->nome ?? null,
            'funcao_id'=> $c->funcao_id,
        ]));
    }

    public function mecanicos(Request $r): JsonResponse {
        $user = auth()->user();
        $empresaId = $r->empresa_id ?? (!$user->isSuperAdmin() ? $user->empresa_id : null);
        $q = Mecanico::with(['colaborador:id,nome,setor_id'])
            ->whereHas('colaborador', fn($c) => $c->where('status','Contratado'));
        if ($empresaId) $q->where('empresa_id', $empresaId);
        if ($r->setor_id) $q->whereHas('colaborador', fn($c) => $c->where('setor_id', $r->setor_id));
        return response()->json($q->get()->map(fn($m) => [
            'id'   => $m->colaborador->id,
            'nome' => $m->colaborador->nome,
        ]));
    }

    public function maquinas(Request $r): JsonResponse {
        $user = auth()->user();
        $q = Maquina::orderBy('nome')->select('id','nome','numero_serie','status');
        if ($r->empresa_id) $q->where('empresa_id', $r->empresa_id);
        elseif (!$user->isSuperAdmin()) $q->where('empresa_id', $user->empresa_id);
        return response()->json($q->get());
    }

    public function clinicas(Request $r): JsonResponse {
        return response()->json(Clinica::ativas()->orderBy('nome')->select('id','nome','whatsapp')->get());
    }

    public function criarColaborador(Request $r): JsonResponse {
        $r->validate([
            'nome'            => 'required|string|min:3',
            'cpf'             => 'required|string|size:11|unique:colaboradores,cpf',
            'data_nascimento' => 'required|date',
            'sexo'            => 'required|in:M,F',
            'data_admissao'   => 'required|date',
            'empresa_id'      => 'required|exists:empresas,id',
            'setor_id'        => 'required|exists:setores,id',
            'funcao_id'       => 'required|exists:funcoes,id',
        ]);

        $colaborador = \App\Models\Colaborador::create([
            'nome'            => $r->nome,
            'cpf'             => preg_replace('/\D/', '', $r->cpf),
            'rg'              => $r->rg,
            'data_nascimento' => $r->data_nascimento,
            'sexo'            => $r->sexo,
            'data_admissao'   => $r->data_admissao,
            'empresa_id'      => $r->empresa_id,
            'setor_id'        => $r->setor_id,
            'funcao_id'       => $r->funcao_id,
            'status'          => 'Contratado',
        ]);

        return response()->json([
            'id'       => $colaborador->id,
            'nome'     => $colaborador->nome,
            'funcao_id'=> $colaborador->funcao_id,
        ]);
    }

    public function funcaoExames(Request $r): JsonResponse {
        $funcao = \App\Models\Funcao::with('exames:id,nome')->find($r->route('funcao'));
        if (!$funcao) return response()->json([]);
        return response()->json($funcao->exames->pluck('nome'));
    }

    public function consultarCa(string $ca, CaEpiService $service): JsonResponse {
        $dados = $service->buscar($ca);
        if (!$dados) {
            return response()->json(['encontrado' => false, 'mensagem' => 'CA não encontrado na base CAEPI.'], 404);
        }
        return response()->json(array_merge($dados, ['encontrado' => true]));
    }

    public function epiTamanhos(Request $r): JsonResponse {
        $epi = \App\Models\EPI::with('tamanhos:id,codigo,descricao,ordem')->find($r->epi_id);
        if (!$epi) return response()->json([]);
        return response()->json([
            'tem_tamanho' => (bool)$epi->tem_tamanho,
            'tamanhos'    => $epi->tamanhos->sortBy('ordem')->values(),
        ]);
    }

    public function search(Request $r): JsonResponse {
        $q = $r->q;
        if (strlen($q) < 2) return response()->json(['results'=>[]]);

        $user = auth()->user();
        $query = Colaborador::with(['setor','funcao','empresa'])
            ->where('status','Contratado')
            ->where(fn($sq) => $sq->where('nome','ilike',"%$q%")->orWhere('cpf','ilike',"%$q%")->orWhere('matricula','ilike',"%$q%"));

        if (!$user->isSuperAdmin()) $query->where('empresa_id',$user->empresa_id);

        return response()->json([
            'results' => $query->limit(10)->get()->map(fn($c) => [
                'id'       => $c->id,
                'nome'     => $c->nome,
                'url'      => route('ficha.show',$c->id),
                'initials' => $c->initials,
                'meta'     => ($c->funcao?->nome ?? '') . ' · ' . ($c->setor?->nome ?? '') . ' · ' . ($c->empresa?->nome_display ?? ''),
            ])
        ]);
    }

    public function notificacoes(): JsonResponse {
        $user      = auth()->user();
        $empresaId = $user->isSuperAdmin() ? null : $user->empresa_id;
        $items     = [];

        $q = fn($m) => $empresaId ? $m::where('empresa_id',$empresaId) : $m::query();

        $asoVenc = $q(new ASO)->where('data_vencimento','<',today())->count();
        if ($asoVenc > 0) $items[] = ['nivel'=>'danger','icon'=>'fas fa-clipboard-list','titulo'=>'ASOs Vencidos','descricao'=>"$asoVenc ASO(s) com vencimento ultrapassado",'tag'=>'URGENTE'];

        $extVenc = $q(new Extintor)->where('proxima_recarga','<',today())->count();
        if ($extVenc > 0) $items[] = ['nivel'=>'danger','icon'=>'fas fa-fire-extinguisher','titulo'=>'Extintores Vencidos','descricao'=>"$extVenc extintor(es) com recarga vencida",'tag'=>'URGENTE'];

        $epiVenc = $q(new EntregaEPI)->where('data_prevista_troca','<',today())->where('status','Ativo')->count();
        if ($epiVenc > 0) $items[] = ['nivel'=>'warning','icon'=>'fas fa-hard-hat','titulo'=>'EPIs Vencidos','descricao'=>"$epiVenc EPI(s) necessitam substituição",'tag'=>'EPI'];

        $asoAVencer = $q(new ASO)->whereBetween('data_vencimento',[today(),today()->addDays(30)])->count();
        if ($asoAVencer > 0) $items[] = ['nivel'=>'warning','icon'=>'fas fa-clock','titulo'=>'ASOs a Vencer','descricao'=>"$asoAVencer ASO(s) vencem em 30 dias",'tag'=>'ATENÇÃO'];

        return response()->json(['total'=>count($items),'items'=>$items]);
    }

    public function dashboardStats(Request $r): JsonResponse {
        $user = auth()->user();
        $eid  = $user->isSuperAdmin() ? $r->empresa_id : $user->empresa_id;
        $hoje = today();
        $em30 = today()->addDays(30);

        $cW = $eid ? ['empresa_id'=>$eid] : [];
        return response()->json([
            'totalColabs'    => Colaborador::where($cW)->where('status','Contratado')->count(),
            'asoVencidos'    => ASO::where($cW)->where('data_vencimento','<',$hoje)->count(),
            'asoAVencer'     => ASO::where($cW)->whereBetween('data_vencimento',[$hoje,$em30])->count(),
            'asoAgendados'   => ASO::where($cW)->where('status_logistico','agendado')->count(),
            'epiAtivos'      => EPI::where('status','Ativo')->count(),
            'epiVencidos'    => EntregaEPI::where($cW)->where('status','Ativo')->where('data_prevista_troca','<',$hoje)->count(),
            'estoquesBaixos' => EPIEstoque::when($eid,fn($q)=>$q->where('empresa_id',$eid))->whereColumn('quantidade','<=', \DB::raw('(SELECT estoque_minimo FROM epis WHERE epis.id = epi_estoques.epi_id)'))->count(),
            'extintVencidos' => Extintor::where($cW)->where('proxima_recarga','<',$hoje)->count(),
        ]);
    }

    public function dashboardCharts(Request $r): JsonResponse {
        $user = auth()->user();
        $eid  = $user->isSuperAdmin() ? $r->empresa_id : $user->empresa_id;

        $tendencia = collect(range(5,0))->map(function($i) use($eid) {
            $m = now()->subMonths($i);
            $cW = $eid ? ['empresa_id'=>$eid] : [];
            return [
                'mes'       => $m->locale('pt_BR')->isoFormat('MMM'),
                'asos'      => ASO::where($cW)->whereYear('created_at',$m->year)->whereMonth('created_at',$m->month)->count(),
                'epis'      => EntregaEPI::where($cW)->whereYear('created_at',$m->year)->whereMonth('created_at',$m->month)->count(),
                'uniformes' => \App\Models\EntregaUniforme::where($cW)->whereYear('created_at',$m->year)->whereMonth('created_at',$m->month)->count(),
            ];
        });

        $porSetor = Colaborador::where('status','Contratado')
            ->when($eid, fn($q)=>$q->where('empresa_id',$eid))
            ->join('setores','colaboradores.setor_id','=','setores.id')
            ->selectRaw('setores.nome as setor, count(*) as total')
            ->groupBy('setores.nome')->orderByDesc('total')->limit(6)->get();

        return response()->json(['tendencia'=>$tendencia,'porSetor'=>$porSetor]);
    }

    public function marcas(Request $r): JsonResponse {
        $q = $r->q ?? '';
        $marcas = EPI::whereNotNull('marca')
            ->where('marca', '!=', '')
            ->when($q, fn($query) => $query->where('marca', 'ilike', "%{$q}%"))
            ->distinct()
            ->orderBy('marca')
            ->limit(10)
            ->pluck('marca');
        return response()->json($marcas);
    }
}
