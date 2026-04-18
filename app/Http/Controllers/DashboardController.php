<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Colaborador, ASO, EPI, EPIEstoque, EntregaEPI, EntregaUniforme, Extintor, Maquina, WhatsappMensagem, Setor, Funcao, Empresa, Brigadista, Alerta};
use App\Helpers\CnaeRiscoHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller {
    public function index(Request $r) {
        $user = auth()->user();

        $activeTab = 'sst';
        if ($user->isSuperAdmin()) {
            // Persiste a empresa selecionada na sessão
            if ($r->has('empresa_id')) {
                $r->empresa_id
                    ? session(['dashboard_empresa_id' => $r->empresa_id])
                    : session()->forget('dashboard_empresa_id');
            }
            $eid = session('dashboard_empresa_id') ?: null;
            if ($r->_tab) $activeTab = $r->_tab;
        } else {
            $eid = $user->empresa_id;
        }

        $cW   = $eid ? ['empresa_id' => $eid] : [];
        $hoje = today();
        $em30 = today()->addDays(30);

        // ── EMPRESA ──────────────────────────────────────────────────
        $empresa = $eid ? Empresa::find($eid) : null;

        // ── COLABORADORES ─────────────────────────────────────────────
        $totalAtivos     = Colaborador::where($cW)->where('status', 'Contratado')->count();
        $totalAfastados  = Colaborador::where($cW)->where('status', 'Afastado')->count();
        $totalDemitidos  = Colaborador::where($cW)->where('status', 'Demitido')->count();

        // Admissões e demissões nos últimos 30 dias
        $admissoesRecentes = Colaborador::where($cW)
            ->where('data_admissao', '>=', $hoje->copy()->subDays(30))
            ->count();
        $demissoesRecentes = Colaborador::where($cW)
            ->where('data_demissao', '>=', $hoje->copy()->subDays(30))
            ->count();

        // Por setor
        $porSetor = DB::table('colaboradores')
            ->join('setores', 'colaboradores.setor_id', '=', 'setores.id')
            ->when($eid, fn($q) => $q->where('colaboradores.empresa_id', $eid))
            ->where('colaboradores.status', 'Contratado')
            ->whereNull('colaboradores.deleted_at')
            ->selectRaw('setores.nome, COUNT(*) as total')
            ->groupBy('setores.nome')
            ->orderByDesc('total')
            ->get();

        // Por função
        $porFuncao = DB::table('colaboradores')
            ->join('funcoes', 'colaboradores.funcao_id', '=', 'funcoes.id')
            ->when($eid, fn($q) => $q->where('colaboradores.empresa_id', $eid))
            ->where('colaboradores.status', 'Contratado')
            ->whereNull('colaboradores.deleted_at')
            ->selectRaw('funcoes.nome, COUNT(*) as total')
            ->groupBy('funcoes.nome')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // ── ASOs ──────────────────────────────────────────────────────
        $asoTotal    = ASO::where($cW)->count();
        $asoValidos  = ASO::where($cW)->where('data_vencimento', '>=', $hoje)->count();
        $asoVencidos = ASO::where($cW)->where('data_vencimento', '<', $hoje)->count();
        $asoAVencer  = ASO::where($cW)->whereBetween('data_vencimento', [$hoje, $em30])->count();
        $asoConformidade = $asoTotal > 0 ? round(($asoValidos / $asoTotal) * 100, 1) : 100;

        // Colaboradores sem ASO
        $semAso = Colaborador::where($cW)->where('status', 'Contratado')
            ->whereDoesntHave('asos')
            ->count();

        // ── RISCOS ────────────────────────────────────────────────────
        $totalRiscos = DB::table('riscos')->count();
        $riscosPorTipo = DB::table('riscos')
            ->selectRaw('categoria, COUNT(*) as total')
            ->groupBy('categoria')
            ->get()
            ->keyBy('categoria');

        // GHEs por setor (setores com maior exposição)
        $setoresRisco = DB::table('ghe_setores')
            ->join('setores', 'ghe_setores.setor_id', '=', 'setores.id')
            ->join('ghes', 'ghe_setores.ghe_id', '=', 'ghes.id')
            ->join('ghe_riscos', 'ghes.id', '=', 'ghe_riscos.ghe_id')
            ->when($eid, fn($q) => $q->where('ghes.empresa_id', $eid))
            ->selectRaw('setores.nome, COUNT(ghe_riscos.id) as total_riscos')
            ->groupBy('setores.nome')
            ->orderByDesc('total_riscos')
            ->limit(5)
            ->get();

        // ── CIPA ──────────────────────────────────────────────────────
        $cipaAtiva = DB::table('cipa_membros')
            ->when($eid, fn($q) => $q->where('empresa_id', $eid))
            ->where('ativo', true)
            ->where('mandato_fim', '>=', $hoje)
            ->count();
        $cipaMandatoVencido = DB::table('cipa_membros')
            ->when($eid, fn($q) => $q->where('empresa_id', $eid))
            ->where('ativo', true)
            ->where('mandato_fim', '<', $hoje)
            ->count();

        // ── EPIs ──────────────────────────────────────────────────────
        $epiVencidos    = EntregaEPI::where($cW)->where('status', 'Ativo')->where('data_prevista_troca', '<', $hoje)->count();
        $epiAVencer     = EntregaEPI::where($cW)->where('status', 'Ativo')->whereBetween('data_prevista_troca', [$hoje, $em30])->count();
        $epiEntregues   = EntregaEPI::where($cW)->count();
        $estoquesBaixos = EPIEstoque::where($cW)->where('quantidade', '<=', 0)->count();

        // ── EXTINTORES ────────────────────────────────────────────────
        $extintVencidos = Extintor::where($cW)->where('proxima_recarga', '<', $hoje)->count();
        $extintTotal    = Extintor::where($cW)->count();

        // ── ALERTAS ───────────────────────────────────────────────────
        $alertas = [];
        // PPP pendentes
        $pppPendentes = Alerta::where('tipo','ppp')->where('status','pendente')
            ->when($eid, fn($q) => $q->where('empresa_id', $eid))
            ->with('colaborador:id,nome')
            ->orderBy('data_prevista')
            ->get();
        foreach ($pppPendentes as $ppp) {
            $alertas[] = [
                'nivel'  => 'danger',
                'icon'   => 'fa-file-contract',
                'msg'    => "PPP pendente — {$ppp->colaborador?->nome} (demissão: {$ppp->data_prevista?->format('d/m/Y')})",
                'link'   => route('ficha.show', $ppp->colaborador_id),
            ];
        }

        if ($asoVencidos   > 0) $alertas[] = ['nivel'=>'danger',  'icon'=>'fa-clipboard-times', 'msg'=>"{$asoVencidos} ASO(s) VENCIDO(S) — risco de auto de infração", 'link'=>route('asos.vencidos')];
        if ($semAso        > 0) $alertas[] = ['nivel'=>'danger',  'icon'=>'fa-user-slash',       'msg'=>"{$semAso} colaborador(es) SEM nenhum ASO cadastrado", 'link'=>route('asos.index')];
        if ($asoAVencer    > 0) $alertas[] = ['nivel'=>'warning', 'icon'=>'fa-calendar-times',   'msg'=>"{$asoAVencer} ASO(s) vencendo nos próximos 30 dias", 'link'=>route('asos.a-vencer')];
        if ($epiVencidos   > 0) $alertas[] = ['nivel'=>'danger',  'icon'=>'fa-hard-hat',         'msg'=>"{$epiVencidos} EPI(s) com prazo de troca vencido", 'link'=>route('epis.entregas')];
        if ($extintVencidos> 0) $alertas[] = ['nivel'=>'danger',  'icon'=>'fa-fire-extinguisher','msg'=>"{$extintVencidos} extintor(es) com recarga vencida", 'link'=>route('extintores.index')];
        if ($estoquesBaixos> 0) $alertas[] = ['nivel'=>'warning', 'icon'=>'fa-box-open',         'msg'=>"{$estoquesBaixos} EPI(s) com estoque zerado", 'link'=>route('epis.index')];
        if ($cipaMandatoVencido > 0) $alertas[] = ['nivel'=>'warning','icon'=>'fa-users-cog',    'msg'=>"CIPA com mandato vencido — necessita renovação", 'link'=>route('cipa.index')];

        // ── EXAMES OCUPACIONAIS ───────────────────────────────────────
        $examesAgendados     = ASO::where($cW)->where('status_logistico','agendado')->count();
        $examesSemana        = ASO::where($cW)->where('status_logistico','agendado')
            ->whereBetween('data_exame', [$hoje, $hoje->copy()->addDays(7)])->count();
        $examesRealizadosMes = ASO::where($cW)->where('status_logistico','realizado')
            ->whereYear('data_exame', $hoje->year)->whereMonth('data_exame', $hoje->month)->count();
        $examesAdmissionaisMes = ASO::where($cW)->where('tipo','admissional')
            ->whereYear('created_at', $hoje->year)->whereMonth('created_at', $hoje->month)->count();
        $examesPendentes     = ASO::where($cW)->where('status_logistico','pendente')->count();

        // ── PRÓXIMOS ASOs ────────────────────────────────────────────
        $proximosAsos = ASO::with(['colaborador.funcao', 'colaborador.empresa'])
            ->where($cW)
            ->where(fn($q) => $q->where('data_vencimento', '<', $hoje)->orWhereBetween('data_vencimento', [$hoje, $em30]))
            ->where('data_vencimento', '>=', $hoje->copy()->subDays(60))
            ->orderBy('data_vencimento')
            ->limit(10)
            ->get();

        // ── TENDÊNCIA (últimos 6 meses) ───────────────────────────────
        $tendencia = collect(range(5, 0))->map(function($i) use($cW) {
            $mes = today()->subMonths($i);
            return [
                'mes'   => $mes->locale('pt_BR')->isoFormat('MMM/YY'),
                'asos'  => ASO::where($cW)->whereYear('created_at', $mes->year)->whereMonth('created_at', $mes->month)->count(),
                'admissoes' => Colaborador::where($cW)->whereYear('data_admissao', $mes->year)->whereMonth('data_admissao', $mes->month)->count(),
            ];
        });

        // ── ADMISSÕES RECENTES ────────────────────────────────────────
        $admissoesLista = Colaborador::with(['setor', 'funcao'])
            ->where($cW)
            ->where('data_admissao', '>=', $hoje->copy()->subDays(60))
            ->orderByDesc('data_admissao')
            ->limit(5)
            ->get();

        $empresas = $user->isSuperAdmin()
            ? Empresa::ativas()->orderBy('razao_social')->get()
            : collect();

        // ── ABA: DADOS DA EMPRESA ─────────────────────────────────────
        $homens   = Colaborador::where($cW)->where('status','Contratado')->where('sexo','M')->count();
        $mulheres = Colaborador::where($cW)->where('status','Contratado')->where('sexo','F')->count();

        $colaboradoresComIdade = Colaborador::where($cW)->where('status','Contratado')
            ->whereNotNull('data_nascimento')->get(['nome','data_nascimento']);

        $idades = $colaboradoresComIdade->map(fn($c) => $c->data_nascimento->age);
        $mediaIdade = $idades->count() ? round($idades->avg(), 1) : null;

        $maisJovem = $colaboradoresComIdade->sortByDesc('data_nascimento')->first();
        $maisVelho = $colaboradoresComIdade->sortBy('data_nascimento')->first();

        $faixaEtaria = ['18–25' => 0, '26–35' => 0, '36–45' => 0, '46–55' => 0, '56+' => 0];
        foreach ($idades as $idade) {
            if      ($idade <= 25) $faixaEtaria['18–25']++;
            elseif  ($idade <= 35) $faixaEtaria['26–35']++;
            elseif  ($idade <= 45) $faixaEtaria['36–45']++;
            elseif  ($idade <= 55) $faixaEtaria['46–55']++;
            else                   $faixaEtaria['56+']++;
        }

        $tempoEmpresa = ['< 1 ano' => 0, '1–3 anos' => 0, '3–5 anos' => 0, '5+ anos' => 0];
        Colaborador::where($cW)->where('status','Contratado')
            ->whereNotNull('data_admissao')->get(['data_admissao'])
            ->each(function($c) use (&$tempoEmpresa, $hoje) {
                $anos = $c->data_admissao->diffInYears($hoje);
                if      ($anos < 1) $tempoEmpresa['< 1 ano']++;
                elseif  ($anos < 3) $tempoEmpresa['1–3 anos']++;
                elseif  ($anos < 5) $tempoEmpresa['3–5 anos']++;
                else                $tempoEmpresa['5+ anos']++;
            });

        // ── BRIGADA (widget) ──────────────────────────────────────────
        $brigadaEid     = $r->brigada_eid ?? $eid;
        $brigadaEmpresa = $brigadaEid ? Empresa::find($brigadaEid) : null;
        $brigGrupo      = $brigadaEmpresa?->grupo_risco_efetivo ?? 'B';
        $brigPctMin     = CnaeRiscoHelper::pctMinimo($brigGrupo);
        $brigLabel      = CnaeRiscoHelper::labelGrupo($brigGrupo);
        $brigCor        = CnaeRiscoHelper::corGrupo($brigGrupo);
        $brigTotal      = Brigadista::where('ativo', true)->when($brigadaEid, fn($q) => $q->where('empresa_id', $brigadaEid))->count();
        $brigAtivos     = Colaborador::where('status','Contratado')->when($brigadaEid, fn($q) => $q->where('empresa_id', $brigadaEid))->count();
        $brigPct        = $brigAtivos > 0 ? round($brigTotal / $brigAtivos * 100, 1) : 0;
        $brigConforme   = $brigPct >= $brigPctMin;
        $brigFaltam     = max(0, (int)ceil($brigAtivos * $brigPctMin / 100) - $brigTotal);

        return view('dashboard.index', compact(
            'empresa', 'empresas',
            'totalAtivos', 'totalAfastados', 'totalDemitidos',
            'admissoesRecentes', 'demissoesRecentes',
            'porSetor', 'porFuncao',
            'asoTotal', 'asoValidos', 'asoVencidos', 'asoAVencer', 'asoConformidade', 'semAso',
            'totalRiscos', 'riscosPorTipo', 'setoresRisco',
            'cipaAtiva', 'cipaMandatoVencido',
            'epiVencidos', 'epiAVencer', 'epiEntregues', 'estoquesBaixos',
            'extintVencidos', 'extintTotal',
            'alertas', 'proximosAsos', 'tendencia', 'admissoesLista',
            'homens', 'mulheres', 'mediaIdade', 'maisJovem', 'maisVelho',
            'faixaEtaria', 'tempoEmpresa',
            'examesAgendados', 'examesSemana', 'examesRealizadosMes', 'examesAdmissionaisMes', 'examesPendentes',
            'brigadaEid', 'brigadaEmpresa', 'brigGrupo', 'brigPctMin', 'brigLabel', 'brigCor',
            'brigTotal', 'brigAtivos', 'brigPct', 'brigConforme', 'brigFaltam',
            'activeTab', 'eid'
        ));
    }

    public function data(Request $r) {
        return (new ApiController)->dashboardCharts($r);
    }

    public function alertas() {
        $user = auth()->user();
        $cW   = $user->isSuperAdmin() ? [] : ['empresa_id' => $user->empresa_id];
        $hoje = today();
        $alertas = ASO::with(['colaborador', 'colaborador.empresa'])
            ->where($cW)->where('data_vencimento', '<', $hoje)->orderBy('data_vencimento')->limit(50)->get();
        return view('dashboard.alertas', compact('alertas'));
    }
}
