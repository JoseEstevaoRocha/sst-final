<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{ASO, Colaborador, Clinica, Empresa, WhatsappMensagem, Setor, Funcao, Alerta};
use Carbon\Carbon;

class ASOController extends Controller {

    // ── INDEX — Gestão de ASOs (visualização + status) ──────────────────
    public function index(Request $r) {
        $hoje = today();
        $em40 = today()->addDays(40);
        $user = auth()->user();
        $cW   = $user->isSuperAdmin() ? [] : ['empresa_id' => $user->empresa_id];

        $q = ASO::with(['colaborador.funcao','colaborador.setor','empresa','clinica'])->where($cW);

        if ($r->search)    $q->whereHas('colaborador', fn($sq) => $sq->where('nome','ilike',"%{$r->search}%")->orWhere('cpf','ilike',"%{$r->search}%"));
        if ($r->empresa_id) $q->where('empresa_id', $r->empresa_id);
        if ($r->tipo)       $q->where('tipo', $r->tipo);
        if ($r->status)     $q->where('status_logistico', $r->status);
        if ($r->resultado)  $q->where('resultado', $r->resultado);
        if ($r->mes)        $q->whereMonth('data_vencimento', $r->mes);
        if ($r->setor_id)   $q->whereHas('colaborador', fn($sq) => $sq->where('setor_id', $r->setor_id));
        if ($r->funcao_id)  $q->whereHas('colaborador', fn($sq) => $sq->where('funcao_id', $r->funcao_id));

        match($r->situacao) {
            'vencidos'    => $q->where('data_vencimento','<',$hoje),
            'a_vencer_40' => $q->whereBetween('data_vencimento',[$hoje,$em40]),
            'em_dia'      => $q->where('data_vencimento','>',$em40),
            'agendados'   => $q->where('status_logistico','agendado'),
            default       => null,
        };

        $asos  = $q->orderBy('data_vencimento')->paginate(25)->withQueryString();
        $stats = [
            'total'       => ASO::where($cW)->count(),
            'vencidos'    => ASO::where($cW)->where('data_vencimento','<',$hoje)->count(),
            'a_vencer_40' => ASO::where($cW)->whereBetween('data_vencimento',[$hoje,$em40])->count(),
            'agendados'   => ASO::where($cW)->where('status_logistico','agendado')->count(),
            'em_dia'      => ASO::where($cW)->where('data_vencimento','>',$em40)->count(),
        ];
        $empresas = $user->isSuperAdmin() ? Empresa::ativas()->get() : collect();
        $setores  = Setor::when(!$user->isSuperAdmin(), fn($q) => $q->where('empresa_id',$user->empresa_id))->orderBy('nome')->get();
        $clinicas = Clinica::ativas()->orderBy('nome')->get();
        return view('aso.index', compact('asos','stats','empresas','clinicas','setores'));
    }

    // ── CREATE / STORE ───────────────────────────────────────────────────
    public function create(Request $r) {
        $clinicas       = Clinica::ativas()->orderBy('nome')->get();
        $empresas       = auth()->user()->isSuperAdmin() ? Empresa::ativas()->get() : collect([auth()->user()->empresa]);
        $setores        = Setor::when(!auth()->user()->isSuperAdmin(), fn($q) => $q->where('empresa_id',auth()->user()->empresa_id))->orderBy('nome')->get();
        $preColaborador = $r->colaborador_id ? Colaborador::with(['empresa','funcao','setor'])->find($r->colaborador_id) : null;
        return view('aso.form', ['aso'=>null,'clinicas'=>$clinicas,'empresas'=>$empresas,'setores'=>$setores,'preColaborador'=>$preColaborador]);
    }

    // ── STORE — Passo 1: Criar agendamento (sem resultado ainda) ────────
    public function store(Request $r) {
        $r->validate([
            'colaborador_id' => 'required|exists:colaboradores,id',
            'tipo'           => 'required',
            'data_agendada'  => 'required|date',
            'clinica_id'     => 'nullable|exists:clinicas,id',
        ]);

        $empresaId = $r->empresa_id ?? auth()->user()->empresa_id;

        $aso = ASO::create([
            'empresa_id'            => $empresaId,
            'colaborador_id'        => $r->colaborador_id,
            'tipo'                  => $r->tipo,
            'clinica_id'            => $r->clinica_id,
            'data_agendada'         => $r->data_agendada,
            'horario_agendado'      => $r->horario_agendado,
            'local_exame'           => $r->local_exame ?? 'clinica',
            'exames_complementares' => $r->exames_complementares,
            'observacoes'           => $r->observacoes,
            'novo_setor_id'         => $r->novo_setor_id,
            'nova_funcao_id'        => $r->nova_funcao_id,
            'status_logistico'      => 'agendado',
            'resultado'             => 'pendente',
        ]);

        if ($r->enviar_whatsapp && $r->clinica_id) {
            return $this->enviarWhatsapp(
                $aso->load(['colaborador','empresa','clinica']),
                redirect()->route('asos.agendamento')->with('success', 'Agendamento criado! Abrindo WhatsApp...')
            );
        }

        return redirect()->route('asos.agendamento')->with('success', 'Agendamento criado com sucesso!');
    }

    public function edit(ASO $aso) {
        $clinicas = Clinica::ativas()->get();
        $empresas = auth()->user()->isSuperAdmin() ? Empresa::ativas()->get() : collect([auth()->user()->empresa]);
        $setores  = Setor::when(!auth()->user()->isSuperAdmin(), fn($q) => $q->where('empresa_id',auth()->user()->empresa_id))->orderBy('nome')->get();
        return view('aso.form', compact('aso','clinicas','empresas','setores'));
    }

    public function update(Request $r, ASO $aso) {
        $r->validate(['colaborador_id'=>'required','tipo'=>'required']);
        $isDemissional   = $r->tipo === 'demissional';
        $isMudancaFuncao = $r->tipo === 'mudanca_funcao';

        $aso->update(array_merge(
            $r->only(['empresa_id','colaborador_id','clinica_id','tipo','data_exame','data_agendada','horario_agendado','exames_complementares','resultado','clinica_nome','medico_responsavel','status_logistico','observacoes','novo_setor_id','nova_funcao_id','local_exame']),
            ['data_vencimento' => $isDemissional ? null : $r->data_vencimento]
        ));

        if ($isDemissional && $r->data_exame) {
            Colaborador::where('id', $r->colaborador_id)->update(['status' => 'Demitido', 'data_demissao' => $r->data_exame]);
        }

        $ref   = request()->headers->get('referer', '');
        $route = str_contains($ref, 'agendamento') ? 'asos.agendamento' : 'asos.index';
        return redirect()->route($route)->with('success', 'ASO atualizado!' . ($isDemissional ? ' Colaborador atualizado para Demitido.' : ''));
    }

    public function destroy(ASO $aso) {
        $aso->delete();
        $ref = request()->headers->get('referer', '');
        $route = str_contains($ref, 'agendamento') ? 'asos.agendamento' : 'asos.index';
        return redirect()->route($route)->with('success', 'Agendamento excluído!');
    }

    public function show(ASO $aso) { return redirect()->route('asos.index'); }

    // ── TELA DE AGENDAMENTO ──────────────────────────────────────────────
    public function agendamento(Request $r) {
        $user  = auth()->user();
        $cW    = $user->isSuperAdmin() ? [] : ['empresa_id' => $user->empresa_id];
        $hoje  = today();
        $em30  = today()->addDays(30);
        $em60  = today()->addDays(60);

        // Filtros
        $empresaId = $r->empresa_id ?: ($user->isSuperAdmin() ? null : $user->empresa_id);
        $mesVenc   = $r->mes_venc;
        $anoVenc   = $r->ano_venc ?: $hoje->year;
        $situacao  = $r->situacao ?: 'pendentes'; // pendentes|vencidos|a_vencer|agendados|todos

        $q = ASO::with(['colaborador.funcao','colaborador.setor','empresa','clinica'])
            ->where($cW)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->when($r->setor_id, fn($q) => $q->whereHas('colaborador', fn($sq) => $sq->where('setor_id', $r->setor_id)))
            ->when($r->search, fn($q) => $q->whereHas('colaborador', fn($sq) =>
                $sq->where('nome','ilike',"%{$r->search}%")->orWhere('cpf','ilike',"%{$r->search}%")
            ));

        // Filtro por mês de vencimento
        if ($mesVenc) {
            $q->whereMonth('data_vencimento', $mesVenc)->whereYear('data_vencimento', $anoVenc);
        }

        // Filtro por situação
        match($situacao) {
            'vencidos'  => $q->where('data_vencimento','<',$hoje)->where('status_logistico','!=','agendado'),
            'a_vencer'  => $q->whereBetween('data_vencimento',[$hoje,$em60])->where('status_logistico','!=','agendado'),
            'agendados' => $q->where('status_logistico','agendado'),
            'pendentes' => $q->where(fn($q) => $q->where('data_vencimento','<',$em60)->orWhereNull('data_vencimento'))->where('status_logistico','!=','agendado'),
            default     => null,
        };

        $asos = $q->orderBy('data_vencimento')->paginate(30)->withQueryString();

        $stats = [
            'vencidos'  => ASO::where($cW)->where('data_vencimento','<',$hoje)->where('status_logistico','!=','agendado')->count(),
            'a_vencer'  => ASO::where($cW)->whereBetween('data_vencimento',[$hoje,$em30])->where('status_logistico','!=','agendado')->count(),
            'agendados' => ASO::where($cW)->where('status_logistico','agendado')->count(),
        ];

        // Meses com vencimentos para o filtro rápido
        $mesesVenc = ASO::where($cW)
            ->whereNotNull('data_vencimento')
            ->selectRaw("DATE_TRUNC('month', data_vencimento) as mes, COUNT(*) as total")
            ->groupByRaw("DATE_TRUNC('month', data_vencimento)")
            ->orderByRaw("DATE_TRUNC('month', data_vencimento)")
            ->get();

        $clinicas = Clinica::ativas()->orderBy('nome')->get();
        $empresas = $user->isSuperAdmin() ? Empresa::ativas()->get() : collect();
        $setores  = Setor::when($empresaId, fn($q) => $q->where('empresa_id',$empresaId))->orderBy('nome')->get();
        $funcoes  = Funcao::when($empresaId, fn($q) => $q->where('empresa_id',$empresaId))->orderBy('nome')->get();

        // Médicos agrupados por clínica para o modal "Registrar Resultado"
        $medicosPorClinica = \App\Models\Medico::ativos()
            ->whereNotNull('clinica_id')
            ->orderBy('nome')
            ->get(['id','nome','crm','clinica_id'])
            ->groupBy('clinica_id')
            ->map(fn($g) => $g->map(fn($m) => [
                'id'          => $m->id,
                'nome_com_crm'=> $m->crm ? "{$m->nome} — CRM: {$m->crm}" : $m->nome,
            ])->values());

        return view('aso.agendamento', compact(
            'asos','stats','clinicas','empresas','setores','funcoes','mesesVenc',
            'situacao','mesVenc','anoVenc','empresaId','medicosPorClinica'
        ));
    }

    // ── AGENDAMENTO INDIVIDUAL ───────────────────────────────────────────
    public function agendar(Request $r, ASO $aso) {
        $r->validate([
            'data_agendada'    => 'required|date',
            'horario_agendado' => 'nullable|date_format:H:i',
            'clinica_id'       => 'nullable|exists:clinicas,id',
        ]);

        $aso->update([
            'data_agendada'         => $r->data_agendada,
            'horario_agendado'      => $r->horario_agendado,
            'exames_complementares' => $r->exames_complementares,
            'local_exame'           => $r->local_exame ?? 'clinica',
            'status_logistico'      => 'agendado',
            'clinica_id'            => $r->clinica_id ?? $aso->clinica_id,
        ]);

        if ($r->enviar_whatsapp && $r->clinica_id) {
            return $this->enviarWhatsapp($aso->fresh(['colaborador','empresa','clinica']),
                back()->with('success','ASO agendado! Abrindo WhatsApp...'));
        }

        return back()->with('success','Agendado para '.Carbon::parse($r->data_agendada)->format('d/m/Y').'!');
    }

    // ── AGENDAMENTO EM LOTE ──────────────────────────────────────────────
    public function agendarLote(Request $r) {
        $r->validate([
            'ids'              => 'required|array|min:1',
            'ids.*'            => 'exists:asos,id',
            'data_agendada'    => 'required|date',
            'horario_agendado' => 'nullable|date_format:H:i',
            'clinica_id'       => 'nullable|exists:clinicas,id',
        ]);

        ASO::whereIn('id', $r->ids)->update([
            'data_agendada'         => $r->data_agendada,
            'horario_agendado'      => $r->horario_agendado,
            'exames_complementares' => $r->exames_complementares,
            'local_exame'           => $r->local_exame ?? 'clinica',
            'clinica_id'            => $r->clinica_id,
            'status_logistico'      => 'agendado',
        ]);

        $count = count($r->ids);

        if ($r->gerar_relatorio) {
            return redirect()->route('asos.relatorio-clinica', [
                'ids'          => implode(',', $r->ids),
                'data_agendada'=> $r->data_agendada,
                'cols'         => $r->cols ?? [],
            ]);
        }

        return back()->with('success', "{$count} ASO(s) agendados para ".Carbon::parse($r->data_agendada)->format('d/m/Y').'!');
    }

    // ── RELATÓRIO PARA CLÍNICA ───────────────────────────────────────────
    public function relatorioClinica(Request $r) {
        $ids = $r->ids ? explode(',', $r->ids) : [];

        $asos = ASO::with(['colaborador.funcao','colaborador.setor','colaborador.empresa','empresa','clinica','novoSetor','novaFuncao'])
            ->when($ids, fn($q) => $q->whereIn('id', $ids))
            ->when(!$ids && $r->empresa_id, fn($q) => $q->where('empresa_id',$r->empresa_id)->where('data_vencimento','<',today()))
            ->orderBy('data_agendada')
            ->orderBy('horario_agendado')
            ->get();

        $empresa = $asos->first()?->empresa;

        // Colunas selecionáveis — padrão todas ativas
        $colsDisponiveis = ['nome','cpf','nascimento','setor','funcao','tipo','data_agendada','horario','local','exames','observacoes'];
        $colsSelecionadas = $r->cols ? (is_array($r->cols) ? $r->cols : explode(',', $r->cols)) : $colsDisponiveis;

        return view('aso.relatorio_clinica', compact('asos','empresa','colsSelecionadas','colsDisponiveis'));
    }

    // ── HELPERS ──────────────────────────────────────────────────────────
    public function vencidos(Request $r) {
        $user = auth()->user();
        $cW   = $user->isSuperAdmin() ? [] : ['empresa_id' => $user->empresa_id];
        $q    = ASO::with(['colaborador.funcao','colaborador.setor','colaborador.empresa','empresa','clinica'])
            ->where($cW)->where('data_vencimento','<',today());
        if ($r->mes) $q->whereMonth('data_vencimento',$r->mes)->whereYear('data_vencimento',$r->ano ?? today()->year);
        if ($r->search) $q->whereHas('colaborador',fn($sq)=>$sq->where('nome','ilike',"%{$r->search}%"));
        $asos     = $q->orderBy('data_vencimento')->paginate(50)->withQueryString();
        $clinicas = Clinica::ativas()->orderBy('nome')->get();
        $empresas = $user->isSuperAdmin() ? Empresa::ativas()->get() : collect();
        return view('aso.vencidos', compact('asos','clinicas','empresas'));
    }

    public function aVencer() {
        $user = auth()->user();
        $cW   = $user->isSuperAdmin() ? [] : ['empresa_id' => $user->empresa_id];
        $asos = ASO::with(['colaborador.funcao','empresa'])->where($cW)
            ->whereBetween('data_vencimento',[today(),today()->addDays(30)])
            ->orderBy('data_vencimento')->paginate(25);
        return view('aso.a_vencer', compact('asos'));
    }

    public function historico() {
        $user = auth()->user();
        $cW   = $user->isSuperAdmin() ? [] : ['empresa_id' => $user->empresa_id];
        $asos = ASO::with(['colaborador','empresa'])->where($cW)->orderByDesc('created_at')->paginate(25);
        return view('aso.historico', compact('asos'));
    }

    public function updateLogistica(Request $r, ASO $aso) {
        $r->validate(['status_logistico'=>'required']);
        $aso->update(['status_logistico' => $r->status_logistico]);
        if (request()->expectsJson()) return response()->json(['ok'=>true]);
        return back()->with('success','Status atualizado!');
    }

    // ── WHATSAPP DIRETO DA TABELA ────────────────────────────────────────
    public function whatsappAso(ASO $aso) {
        if (!$aso->clinica_id) {
            return back()->with('error', 'Este agendamento não possui clínica vinculada.');
        }
        return $this->enviarWhatsapp($aso, back()->with('success', 'Mensagem criada! Abrindo WhatsApp...'));
    }

    // ── REGISTRAR RESULTADO DO ASO (passo 2, após exame) ────────────────
    public function registrarResultado(Request $r, ASO $aso) {
        $r->validate([
            'data_exame'         => 'required|date',
            'resultado'          => 'required|in:Apto,Inapto,Apto com Restrições',
            'medico_responsavel' => 'nullable|string|max:200',
            'data_vencimento'    => 'nullable|date',
        ]);

        $updateData = [
            'data_exame'         => $r->data_exame,
            'resultado'          => $r->resultado,
            'medico_responsavel' => $r->medico_responsavel,
            'data_vencimento'    => $r->data_vencimento,
            'status_logistico'   => 'realizado',
        ];
        if ($r->observacoes) $updateData['observacoes'] = $r->observacoes;

        $aso->update($updateData);

        // Demissional: atualizar status do colaborador
        if ($aso->tipo === 'demissional') {
            Colaborador::where('id', $aso->colaborador_id)->update([
                'status'        => 'Demitido',
                'data_demissao' => $r->data_exame,
            ]);
            $colab = Colaborador::find($aso->colaborador_id);
            $jaExiste = Alerta::where('colaborador_id', $aso->colaborador_id)
                ->where('tipo', 'ppp')->where('status', 'pendente')->exists();
            if (!$jaExiste) {
                Alerta::create([
                    'empresa_id'     => $aso->empresa_id,
                    'colaborador_id' => $aso->colaborador_id,
                    'tipo'           => 'ppp',
                    'titulo'         => 'Gerar PPP',
                    'descricao'      => "Colaborador: {$colab?->nome} | CPF: {$colab?->cpf}",
                    'status'         => 'pendente',
                    'data_prevista'  => $r->data_exame,
                    'criado_por'     => auth()->id(),
                ]);
            }
        }

        // Mudança de função: atualizar setor/função do colaborador
        if ($aso->tipo === 'mudanca_funcao' && ($aso->novo_setor_id || $aso->nova_funcao_id)) {
            $updates = array_filter([
                'setor_id'  => $aso->novo_setor_id,
                'funcao_id' => $aso->nova_funcao_id,
            ]);
            if ($updates) Colaborador::where('id', $aso->colaborador_id)->update($updates);
        }

        return back()->with('success', 'Resultado do ASO registrado com sucesso!');
    }

    private function enviarWhatsapp(ASO $aso, $redirect) {
        $aso->loadMissing(['colaborador.setor','colaborador.funcao','empresa','clinica','novoSetor','novaFuncao']);
        $colab   = $aso->colaborador;
        $clinica = $aso->clinica;
        if (!$clinica) return $redirect;

        // Carregar configuração de templates da empresa
        $cfg = \DB::table('whatsapp_configs')->where('empresa_id', $aso->empresa_id)->first();

        $tipos      = ['admissional'=>'Admissional','periodico'=>'Periódico','demissional'=>'Demissional','retorno'=>'Retorno ao Trabalho','mudanca_funcao'=>'Mudança de Função'];
        $isMudanca  = $aso->tipo === 'mudanca_funcao';
        $novoSetor  = $aso->novoSetor?->nome  ?? '—';
        $novaFuncao = $aso->novaFuncao?->nome ?? '—';

        // Dados para substituição de variáveis
        $cpfFmt = $colab?->cpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $colab->cpf) : '—';
        $vars = [
            '{nome}'        => strtoupper($colab?->nome ?? '—'),
            '{empresa}'     => strtoupper($aso->empresa?->razao_social ?? $aso->empresa?->nome_display ?? '—'),
            '{cpf}'         => $cpfFmt,
            '{rg}'          => $colab?->rg ?? '—',
            '{nasc}'        => $colab?->data_nascimento?->format('d/m/Y') ?? '—',
            '{tipo}'        => strtoupper($tipos[$aso->tipo] ?? $aso->tipo),
            '{setor}'       => strtoupper($colab?->setor?->nome ?? '—'),
            '{funcao}'      => $colab?->funcao?->nome . ($colab?->funcao?->cbo ? ' '.$colab->funcao->cbo : '') ?: '—',
            '{novo_setor}'  => strtoupper($novoSetor),
            '{nova_funcao}' => $novaFuncao,
            '{data}'        => $aso->data_agendada?->format('d/m/Y') ?? '—',
            '{horario}'     => $aso->horario_agendado ? substr($aso->horario_agendado, 0, 5) : '—',
            '{local}'       => $aso->local_exame === 'in_company' ? 'Na empresa (In Company)' : 'Na clínica: '.$clinica->nome,
            '{clinica}'     => strtoupper($clinica->nome),
        ];

        // Escolhe o template: mudança de função usa template específico se configurado
        $templateMudanca = $cfg->modelo_mudanca_funcao ?? null;
        $templatePadrao  = $cfg->modelo_mensagem ?? null;

        if ($isMudanca && $templateMudanca) {
            $modelo = $templateMudanca;
        } elseif ($templatePadrao) {
            $modelo = $templatePadrao;
            // Se for mudança de função e não tiver template específico,
            // append automático dos campos de mudança ao modelo padrão
            if ($isMudanca) {
                $modelo .= "\nNovo Setor: {novo_setor}\nNova Função: {nova_funcao}";
            }
        } else {
            // Fallback hardcoded se não houver configuração
            $modelo = "*SOLICITAÇÃO DE AGENDAMENTO*\nEmpresa: {empresa}\nColaborador: {nome}\nCPF: {cpf}\nNasc: {nasc}\nExame: {tipo}\nSetor: {setor}\nFunção: {funcao}"
                    . ($isMudanca ? "\nNovo Setor: {novo_setor}\nNova Função: {nova_funcao}" : "")
                    . "\nData: {data} às {horario}\nLocal: {local}";
        }

        $msg = str_replace(array_keys($vars), array_values($vars), $modelo);

        if ($aso->exames_complementares) $msg .= "\nExames: {$aso->exames_complementares}";
        if ($aso->observacoes)           $msg .= "\nObs: {$aso->observacoes}";

        WhatsappMensagem::create([
            'empresa_id'       => $aso->empresa_id,
            'colaborador_id'   => $aso->colaborador_id,
            'clinica_id'       => $aso->clinica_id,
            'aso_id'           => $aso->id,
            'tipo_exame'       => $aso->tipo,
            'mensagem_texto'   => $msg,
            'status'           => 'pendente',
            'data_agendada'    => $aso->data_agendada,
            'horario_agendado' => $aso->horario_agendado,
            'usuario_envio'    => auth()->id(),
        ]);

        $mensagem = WhatsappMensagem::where('aso_id',$aso->id)->latest()->first();
        if (!$mensagem) return $redirect;

        return redirect()->route('whatsapp.url', $mensagem->id)
            ->with('success','Abrindo WhatsApp...');
    }
}
