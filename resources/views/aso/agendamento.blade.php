@extends('layouts.app')
@section('title','Agendamento de Exames')

@push('styles')
<style>
.ag-stat { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r);padding:14px 18px;cursor:pointer;transition:border-color .15s,box-shadow .15s;text-decoration:none;display:block; }
.ag-stat:hover { border-color:var(--brand);box-shadow:var(--shadow-md); }
.ag-stat.active { border-color:var(--brand);background:rgba(var(--brand-rgb),.06); }
.ag-stat .val { font-size:26px;font-weight:800;line-height:1; }
.ag-stat .lbl { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-3);margin-top:3px; }

#batchPanel { display:none;position:sticky;bottom:0;background:var(--bg-card);border-top:2px solid var(--brand);padding:12px 20px;z-index:50;box-shadow:0 -4px 20px rgba(0,0,0,.1); }
#batchPanel.show { display:block; }

.local-toggle { display:flex;gap:0;border:1px solid var(--border);border-radius:var(--r-sm);overflow:hidden; }
.local-toggle label { flex:1;text-align:center;padding:6px 12px;font-size:12px;cursor:pointer;font-weight:600;transition:all .15s; }
.local-toggle input[type=radio] { display:none; }
.local-toggle input[type=radio]:checked + label { background:var(--brand);color:#fff; }

.s-vencido  { background:#fee2e2;color:#dc2626;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700; }
.s-avencer  { background:#fef3c7;color:#d97706;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700; }
.s-emdia    { background:#dcfce7;color:#16a34a;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700; }
.s-agendado { background:#dbeafe;color:#2563eb;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700; }
.s-realizado{ background:#f3e8ff;color:#7c3aed;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700; }
.s-semvenc  { background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700; }

.step-badge { display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:3px 8px;border-radius:10px; }
.step-1 { background:#dbeafe;color:#1d4ed8; }
.step-2 { background:#dcfce7;color:#16a34a; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Agendamento de Exames</h1>
        <p class="page-sub">Agende exames e registre resultados de ASOs</p>
    </div>
    <div class="flex gap-8">
        <a href="{{ route('asos.relatorio-clinica') }}" class="btn btn-secondary" target="_blank">
            <i class="fas fa-print"></i> Relatório
        </a>
        <button class="btn btn-primary" onclick="abrirNovoAgendamento()">
            <i class="fas fa-plus"></i> Novo Agendamento
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-16">{{ session('success') }}</div>
@endif

{{-- ── Stats ──────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:16px">
    <a href="{{ request()->fullUrlWithQuery(['situacao'=>'vencidos']) }}" class="ag-stat {{ $situacao==='vencidos'?'active':'' }}">
        <div class="val" style="color:#dc2626">{{ $stats['vencidos'] }}</div>
        <div class="lbl">Vencidos</div>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['situacao'=>'a_vencer']) }}" class="ag-stat {{ $situacao==='a_vencer'?'active':'' }}">
        <div class="val" style="color:#d97706">{{ $stats['a_vencer'] }}</div>
        <div class="lbl">A Vencer 30d</div>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['situacao'=>'agendados']) }}" class="ag-stat {{ $situacao==='agendados'?'active':'' }}">
        <div class="val" style="color:#2563eb">{{ $stats['agendados'] }}</div>
        <div class="lbl">Agendados</div>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['situacao'=>'pendentes']) }}" class="ag-stat {{ $situacao==='pendentes'?'active':'' }}">
        <div class="val" style="color:var(--text-1)">{{ $stats['vencidos'] + $stats['a_vencer'] }}</div>
        <div class="lbl">Pendentes</div>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['situacao'=>'todos']) }}" class="ag-stat {{ $situacao==='todos'?'active':'' }}">
        <div class="val" style="color:var(--text-3)">Todos</div>
        <div class="lbl">Ver tudo</div>
    </a>
</div>

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<form method="GET" id="formFiltros">
<div class="filter-bar mb-8" style="flex-wrap:wrap;gap:8px;align-items:center">
    @if(auth()->user()->isSuperAdmin())
    <select name="empresa_id" class="filter-select" style="width:190px" onchange="this.form.submit()">
        <option value="">Todas as empresas</option>
        @foreach($empresas as $e)
        <option value="{{ $e->id }}" {{ request('empresa_id')==$e->id?'selected':'' }}>{{ $e->nome_display }}</option>
        @endforeach
    </select>
    @endif

    <select name="setor_id" class="filter-select" style="width:160px" onchange="this.form.submit()">
        <option value="">Todos os setores</option>
        @foreach($setores as $s)
        <option value="{{ $s->id }}" {{ request('setor_id')==$s->id?'selected':'' }}>{{ $s->nome }}</option>
        @endforeach
    </select>

    <select name="mes_venc" class="filter-select" style="width:170px" onchange="this.form.submit()">
        <option value="">Todos os meses</option>
        @foreach($mesesVenc as $mv)
        @php $d = \Carbon\Carbon::parse($mv->mes); @endphp
        <option value="{{ $d->month }}" data-ano="{{ $d->year }}"
            {{ request('mes_venc')==$d->month && request('ano_venc')==$d->year ?'selected':'' }}>
            {{ $d->locale('pt_BR')->isoFormat('MMMM/YY') }} ({{ $mv->total }})
        </option>
        @endforeach
    </select>
    <input type="hidden" name="ano_venc" id="anoVencHidden" value="{{ request('ano_venc') }}">

    <input type="text" name="search" class="filter-select" style="width:200px" placeholder="Buscar nome ou CPF..."
        value="{{ request('search') }}" onchange="this.form.submit()">

    <input type="hidden" name="situacao" value="{{ $situacao }}">

    @if(request()->hasAny(['empresa_id','setor_id','mes_venc','search']))
    <a href="{{ route('asos.agendamento') }}" class="btn btn-ghost btn-sm">✕ Limpar</a>
    @endif
</div>
</form>

{{-- ── Tabela com checkboxes ───────────────────────────────────────────── --}}
<form method="POST" action="{{ route('asos.agendar-lote') }}" id="formLote">
@csrf
<div class="card p-0">
    <div class="table-wrap">
        <table class="table" id="tabelaAso">
            <thead>
                <tr>
                    <th style="width:36px">
                        <input type="checkbox" id="checkAll" onchange="toggleAll(this)" style="cursor:pointer">
                    </th>
                    <th>COLABORADOR</th>
                    <th>SETOR / FUNÇÃO</th>
                    <th>TIPO</th>
                    <th>VENCIMENTO</th>
                    <th>SITUAÇÃO</th>
                    <th>AGENDADO PARA</th>
                    <th>AÇÕES</th>
                </tr>
            </thead>
            <tbody>
            @forelse($asos as $a)
            @php
            $dias  = $a->dias_restantes;
            $sit   = $a->situacao;
            $sitCss = match(true) {
                $a->status_logistico === 'realizado' => 's-realizado',
                $a->status_logistico === 'agendado'  => 's-agendado',
                $sit === 'Vencido' => 's-vencido',
                $sit === 'A Vencer' => 's-avencer',
                $sit === 'Em Dia'  => 's-emdia',
                default => 's-semvenc',
            };
            $sitLabel = match(true) {
                $a->status_logistico === 'realizado' => 'Realizado',
                $a->status_logistico === 'agendado'  => 'Agendado',
                default => $sit,
            };
            $tipos = ['admissional'=>'Admissional','periodico'=>'Periódico','demissional'=>'Demissional','retorno'=>'Retorno','mudanca_funcao'=>'Mud. Função'];
            @endphp
            <tr id="row_{{ $a->id }}">
                <td><input type="checkbox" name="ids[]" value="{{ $a->id }}" class="row-check" onchange="updateBatch()" style="cursor:pointer"></td>
                <td>
                    <div class="font-bold text-13">{{ $a->colaborador?->nome ?? '—' }}</div>
                    <div class="text-11 text-muted">{{ $a->colaborador?->cpf ?? '' }}</div>
                </td>
                <td class="text-12">
                    <div>{{ $a->colaborador?->setor?->nome ?? '—' }}</div>
                    <div class="text-11 text-muted">{{ $a->colaborador?->funcao?->nome ?? '' }}</div>
                </td>
                <td><span class="badge badge-secondary" style="font-size:10px">{{ $tipos[$a->tipo] ?? ucfirst($a->tipo) }}</span></td>
                <td class="font-mono text-12">
                    {{ $a->data_vencimento?->format('d/m/Y') ?? '—' }}
                    @if($dias !== null && $dias < 0)
                    <div class="text-11" style="color:#dc2626">{{ abs($dias) }}d atrás</div>
                    @elseif($dias !== null && $dias <= 60)
                    <div class="text-11" style="color:#d97706">em {{ $dias }}d</div>
                    @endif
                </td>
                <td><span class="{{ $sitCss }}">{{ $sitLabel }}</span></td>
                <td class="font-mono text-12">
                    @if($a->data_agendada)
                    <div>{{ $a->data_agendada->format('d/m/Y') }}{{ $a->horario_agendado ? ' '.substr($a->horario_agendado,0,5) : '' }}</div>
                    <div class="text-11 text-muted">{{ $a->local_exame === 'in_company' ? 'In Company' : ($a->clinica?->nome ?? 'Clínica') }}</div>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td style="white-space:nowrap;display:flex;gap:4px;align-items:center;flex-wrap:wrap">
                    @if($a->status_logistico === 'agendado')
                    {{-- Passo 2: Registrar resultado do ASO --}}
                    <button type="button" class="btn btn-success btn-sm"
                        onclick="abrirRegistrarResultado({{ $a->id }}, '{{ addslashes($a->colaborador?->nome ?? '') }}', '{{ $tipos[$a->tipo] ?? ucfirst($a->tipo) }}', '{{ $a->data_agendada?->format('d/m/Y') ?? '' }}', {{ $a->colaborador?->funcao?->periodicidade_aso_dias ?? 365 }}, {{ $a->clinica_id ?? 'null' }})"
                        title="Registrar Resultado do ASO" style="font-size:11px;padding:4px 10px">
                        <i class="fas fa-clipboard-check"></i> Registrar ASO
                    </button>
                    @else
                    <button type="button" class="btn btn-ghost btn-icon btn-sm"
                        onclick="abrirAgendamentoRapido({{ $a->id }})" title="Agendar">
                        <i class="fas fa-calendar-plus"></i>
                    </button>
                    @endif
                    {{-- WhatsApp: aparece quando tem clínica e data agendada --}}
                    @if($a->clinica?->whatsapp && $a->data_agendada)
                    <button type="button" class="btn btn-ghost btn-icon btn-sm"
                        onclick="enviarWhatsappAso({{ $a->id }})"
                        title="Enviar confirmação via WhatsApp para {{ $a->clinica->nome }}"
                        style="color:#25d366">
                        <i class="fab fa-whatsapp"></i>
                    </button>
                    @endif
                    {{-- Editar --}}
                    <a href="{{ route('asos.edit', $a->id) }}" class="btn btn-ghost btn-icon btn-sm" title="Editar agendamento">
                        <i class="fas fa-pencil-alt"></i>
                    </a>
                    {{-- Excluir --}}
                    <button type="button" class="btn btn-ghost btn-icon btn-sm text-danger"
                        onclick="excluirAso({{ $a->id }}, '{{ addslashes($a->colaborador?->nome ?? 'este agendamento') }}')"
                        title="Excluir agendamento">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="8">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3>Nenhum registro encontrado</h3>
                </div>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($asos->hasPages())
    <div style="padding:12px 20px">{{ $asos->links() }}</div>
    @endif
</div>

{{-- ── Painel de lote (sticky) ───────────────────────────────────────── --}}
<div id="batchPanel">
    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:12px">
        <div style="font-weight:700;font-size:13px;white-space:nowrap">
            <span id="batchCount">0</span> selecionados
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;flex:1">
            <input type="date" name="data_agendada" class="form-control" style="width:150px" required>
            <input type="time" name="horario_agendado" class="form-control" style="width:110px">
            <select name="clinica_id" class="form-select" style="width:200px">
                <option value="">Sem clínica</option>
                @foreach($clinicas as $c)
                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                @endforeach
            </select>
            <div class="local-toggle">
                <input type="radio" name="local_exame" id="loc1" value="clinica" checked>
                <label for="loc1"><i class="fas fa-hospital"></i> Clínica</label>
                <input type="radio" name="local_exame" id="loc2" value="in_company">
                <label for="loc2"><i class="fas fa-industry"></i> In Company</label>
            </div>
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer">
                <input type="checkbox" name="gerar_relatorio" value="1"> Gerar relatório
            </label>
        </div>
        <button type="submit" class="btn btn-primary" style="white-space:nowrap">
            <i class="fas fa-calendar-check"></i> Agendar selecionados
        </button>
        <button type="button" class="btn btn-ghost btn-sm" onclick="deselectAll()">Limpar</button>
    </div>
</div>
</form>

{{-- ══════════════════════════════════════════════════════════════════════
     PASSO 1 — Modal: Agendamento rápido individual (editar agendamento)
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalAgendar">
<div class="modal modal-md">
    <div class="modal-header">
        <div class="modal-title"><i class="fas fa-calendar-plus"></i> Agendar Exame</div>
        <button class="modal-close" onclick="closeModal('modalAgendar')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
        <div id="asoInfo" style="background:var(--bg-secondary);border-radius:var(--r-sm);padding:10px 14px;margin-bottom:14px;font-size:13px"></div>
        <form method="POST" id="formAgendarRapido">@csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Data do Agendamento *</label>
                <input type="date" name="data_agendada" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Horário</label>
                <input type="time" name="horario_agendado" class="form-control">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Clínica</label>
                <select name="clinica_id" id="agClinica" class="form-select">
                    <option value="">Sem clínica</option>
                    @foreach($clinicas as $c)
                    <option value="{{ $c->id }}" data-whatsapp="{{ $c->whatsapp }}">{{ $c->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Local do Exame</label>
                <div class="local-toggle">
                    <input type="radio" name="local_exame" id="agLoc1" value="clinica" checked>
                    <label for="agLoc1"><i class="fas fa-hospital"></i> Na Clínica</label>
                    <input type="radio" name="local_exame" id="agLoc2" value="in_company">
                    <label for="agLoc2"><i class="fas fa-industry"></i> In Company</label>
                </div>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Exames Complementares</label>
                <input type="text" name="exames_complementares" class="form-control" placeholder="Ex: Audiometria, Espirometria...">
            </div>
            <div class="form-group form-full" id="whatsappRow" style="display:none">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="enviar_whatsapp" value="1">
                    <span><i class="fab fa-whatsapp" style="color:#25d366"></i> Enviar confirmação via WhatsApp para a clínica</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalAgendar')">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Agendar</button>
        </div>
        </form>
    </div>
</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     PASSO 1 — Modal: NOVO AGENDAMENTO (criar do zero)
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalNovoAgendamento">
<div class="modal modal-lg">
    <div class="modal-header">
        <div class="modal-title">
            <i class="fas fa-calendar-plus"></i>
            Novo Agendamento
            <span class="step-badge step-1">Passo 1 de 2</span>
        </div>
        <button class="modal-close" onclick="closeModal('modalNovoAgendamento')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
        <div style="background:rgba(var(--brand-rgb),.06);border:1px solid rgba(var(--brand-rgb),.2);border-radius:var(--r-sm);padding:10px 14px;margin-bottom:16px;font-size:12px;color:var(--text-2)">
            <i class="fas fa-info-circle" style="color:var(--brand)"></i>
            <strong>Passo 1:</strong> Agende o exame (data, clínica, local). Após a realização, registre o resultado do ASO (médico, resultado, vencimento).
        </div>
    <form method="POST" action="{{ route('asos.store') }}" id="formNovoAgendamento">
    @csrf
    <div class="form-grid">

        @if(auth()->user()->isSuperAdmin())
        <div class="form-group form-full">
            <label class="form-label">Empresa *</label>
            <select name="empresa_id" id="novoEmpresa" class="form-select" required onchange="novoLoadSetores(this.value)">
                <option value="">Selecione a empresa</option>
                @foreach($empresas as $e)
                <option value="{{ $e->id }}">{{ $e->nome_display }}</option>
                @endforeach
            </select>
        </div>
        @else
        <input type="hidden" name="empresa_id" value="{{ auth()->user()->empresa_id }}">
        @endif

        <div class="form-group">
            <label class="form-label">Setor (filtro)</label>
            <div style="display:flex;gap:6px">
                <select id="novoSetor" class="form-select" onchange="novoLoadColaboradores()">
                    <option value="">Todos os setores</option>
                    @foreach($setores as $s)
                    <option value="{{ $s->id }}">{{ $s->nome }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-ghost btn-icon" onclick="openModal('modalNovoSetor')" title="Novo setor"><i class="fas fa-plus"></i></button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Colaborador *</label>
            <div style="display:flex;gap:6px">
                <select name="colaborador_id" id="novoColaborador" class="form-select" required>
                    <option value="">{{ auth()->user()->isSuperAdmin() ? 'Selecione a empresa primeiro' : 'Carregando...' }}</option>
                </select>
                <button type="button" class="btn btn-ghost btn-icon" onclick="openModal('modalNovoColaborador')"
                    title="Cadastrar novo colaborador" style="flex-shrink:0">
                    <i class="fas fa-user-plus"></i>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tipo de Exame *</label>
            <select name="tipo" id="novoTipo" class="form-select" required onchange="novoOnTipo(this.value)">
                <option value="">Selecione</option>
                <option value="admissional">Admissional</option>
                <option value="periodico">Periódico</option>
                <option value="retorno">Retorno ao Trabalho</option>
                <option value="mudanca_funcao">Mudança de Função</option>
                <option value="demissional">Demissional</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Data do Agendamento *</label>
            <input type="date" name="data_agendada" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Horário</label>
            <input type="time" name="horario_agendado" class="form-control">
        </div>

        <div class="form-group">
            <label class="form-label">Clínica</label>
            <select name="clinica_id" id="novoClinica" class="form-select" onchange="novoOnClinica(this)">
                <option value="">Sem clínica</option>
                @foreach($clinicas as $c)
                <option value="{{ $c->id }}" data-whatsapp="{{ $c->whatsapp }}">{{ $c->nome }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Local do Exame</label>
            <div class="local-toggle">
                <input type="radio" name="local_exame" id="novLoc1" value="clinica" checked>
                <label for="novLoc1"><i class="fas fa-hospital"></i> Clínica</label>
                <input type="radio" name="local_exame" id="novLoc2" value="in_company">
                <label for="novLoc2"><i class="fas fa-industry"></i> In Company</label>
            </div>
        </div>

        {{-- Campos Mudança de Função (ocultos) --}}
        <div class="form-group form-full" id="mudancaFuncaoFields" style="display:none">
            <div style="background:rgba(var(--brand-rgb),.06);border:1px solid var(--brand);border-radius:var(--r-sm);padding:14px">
                <div style="font-size:12px;font-weight:700;color:var(--brand);margin-bottom:10px">
                    <i class="fas fa-exchange-alt"></i> Dados da Mudança de Função
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Novo Setor *</label>
                        <div style="display:flex;gap:6px">
                            <select name="novo_setor_id" id="novoSetorMudanca" class="form-select" onchange="novoLoadFuncoesMudanca(this.value)">
                                <option value="">Selecione</option>
                                @foreach($setores as $s)
                                <option value="{{ $s->id }}">{{ $s->nome }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-ghost btn-icon" onclick="openModal('modalNovoSetor')" title="Criar setor"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Nova Função *</label>
                        <div style="display:flex;gap:6px">
                            <select name="nova_funcao_id" id="novaFuncaoMudanca" class="form-select">
                                <option value="">Selecione o setor</option>
                            </select>
                            <button type="button" class="btn btn-ghost btn-icon" onclick="openModal('modalNovaFuncao')" title="Criar função"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group form-full">
            <label class="form-label">
                Exames Complementares
                <span id="novoExamesTag" style="display:none;font-size:10px;font-weight:600;color:var(--brand);margin-left:6px">
                    <i class="fas fa-magic"></i> preenchido pela função
                </span>
            </label>
            <input type="text" name="exames_complementares" id="novoExamesCompl" class="form-control"
                placeholder="Audiometria, Espirometria, Acuidade Visual...">
            <div style="font-size:11px;color:var(--text-3);margin-top:4px">
                <i class="fas fa-info-circle"></i> Preenchido automaticamente pelos exames da função. Edite se necessário.
            </div>
        </div>
        <div class="form-group form-full">
            <label class="form-label">Observações</label>
            <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações para a clínica..."></textarea>
        </div>

        <div class="form-group form-full" id="novoWhatsappRow" style="display:none">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                <input type="checkbox" name="enviar_whatsapp" value="1">
                <i class="fab fa-whatsapp" style="color:#25d366;font-size:16px"></i>
                <span>Enviar confirmação via WhatsApp para a clínica</span>
            </label>
        </div>

        <input type="hidden" name="status_logistico" value="agendado">
        <input type="hidden" name="resultado" value="pendente">
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalNovoAgendamento')">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Salvar Agendamento</button>
    </div>
    </form>
    </div>
</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     PASSO 2 — Modal: Registrar Resultado do ASO (após exame realizado)
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalRegistrarResultado">
<div class="modal modal-md">
    <div class="modal-header">
        <div class="modal-title">
            <i class="fas fa-clipboard-check"></i>
            Registrar Resultado do ASO
            <span class="step-badge step-2">Passo 2 de 2</span>
        </div>
        <button class="modal-close" onclick="closeModal('modalRegistrarResultado')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
        <div id="rr_info" style="background:var(--bg-secondary);border-radius:var(--r-sm);padding:10px 14px;margin-bottom:14px;font-size:13px"></div>
        <form method="POST" id="formRegistrarResultado">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Data do Exame Realizado *</label>
                <input type="date" name="data_exame" id="rr_data_exame" class="form-control" required
                    onchange="rrAutoVencimento(this.value)">
            </div>
            <div class="form-group">
                <label class="form-label">Resultado *</label>
                <select name="resultado" id="rr_resultado" class="form-select" required>
                    <option value="">Selecione</option>
                    <option value="Apto">Apto</option>
                    <option value="Inapto">Inapto</option>
                    <option value="Apto com Restrições">Apto com Restrições</option>
                </select>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Médico Responsável
                    <span id="rr_medico_hint" style="font-size:10px;color:var(--brand);margin-left:6px;display:none">
                        <i class="fas fa-magic"></i> da clínica
                    </span>
                </label>
                <select id="rr_medico_select" class="form-select" style="margin-bottom:6px" onchange="rrOnMedicoSelect(this)">
                    <option value="">— Selecionar médico da clínica —</option>
                </select>
                <input type="text" name="medico_responsavel" id="rr_medico_texto" class="form-control" placeholder="Ou preencha manualmente: Dr(a). Nome CRM...">
            </div>
            <div class="form-group">
                <label class="form-label">Data de Vencimento do ASO
                    <span id="rr_venc_hint" style="font-size:10px;color:var(--text-3);margin-left:4px"></span>
                </label>
                <input type="date" name="data_vencimento" id="rr_data_vencimento" class="form-control">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Observações</label>
                <textarea name="observacoes" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalRegistrarResultado')">Cancelar</button>
            <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Registrar ASO</button>
        </div>
        </form>
    </div>
</div>
</div>

{{-- Forms ocultos fora do formLote para evitar form aninhado --}}
<form method="POST" id="formWhatsappAso" style="display:none">@csrf</form>
<form method="POST" id="formExcluirAso" style="display:none">
    @csrf @method('DELETE')
</form>

{{-- Modal criar Setor rápido --}}
<div class="modal-overlay" id="modalNovoSetor">
<div class="modal modal-sm">
    <div class="modal-header">
        <div class="modal-title"><i class="fas fa-plus"></i> Novo Setor</div>
        <button class="modal-close" onclick="closeModal('modalNovoSetor')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
        <div class="form-group">
            <label class="form-label">Nome do Setor *</label>
            <input type="text" id="novoSetorNome" class="form-control" required>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalNovoSetor')">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="criarSetor()">Criar Setor</button>
        </div>
    </div>
</div>
</div>

{{-- Modal criar Função rápida --}}
<div class="modal-overlay" id="modalNovaFuncao">
<div class="modal modal-sm">
    <div class="modal-header">
        <div class="modal-title"><i class="fas fa-plus"></i> Nova Função</div>
        <button class="modal-close" onclick="closeModal('modalNovaFuncao')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
        <div class="form-group">
            <label class="form-label">Nome da Função *</label>
            <input type="text" id="novaFuncaoNome" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">CBO</label>
            <input type="text" id="novaFuncaoCbo" class="form-control" placeholder="0000-00">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalNovaFuncao')">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="criarFuncao()">Criar Função</button>
        </div>
    </div>
</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     Modal: Cadastro Rápido de Colaborador
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalNovoColaborador">
<div class="modal modal-lg">
    <div class="modal-header">
        <div class="modal-title"><i class="fas fa-user-plus"></i> Cadastro Rápido de Colaborador</div>
        <button class="modal-close" onclick="closeModal('modalNovoColaborador')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
        <div style="background:rgba(var(--brand-rgb),.06);border:1px solid rgba(var(--brand-rgb),.2);border-radius:var(--r-sm);padding:10px 14px;margin-bottom:16px;font-size:12px">
            <i class="fas fa-info-circle" style="color:var(--brand)"></i>
            Após salvar, o colaborador será selecionado automaticamente no agendamento.
        </div>
        <div class="form-grid" id="formNovoColabGrid">

            @if(auth()->user()->isSuperAdmin())
            <div class="form-group form-full">
                <label class="form-label">Empresa *</label>
                <select id="ncEmpresa" class="form-select" required onchange="ncLoadSetores(this.value)">
                    <option value="">Selecione</option>
                    @foreach($empresas as $e)
                    <option value="{{ $e->id }}">{{ $e->nome_display }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="form-group form-full">
                <label class="form-label">Nome Completo *</label>
                <input type="text" id="ncNome" class="form-control" required placeholder="Nome completo">
            </div>

            <div class="form-group">
                <label class="form-label">CPF * <span style="font-size:10px;color:var(--text-3)">(apenas números)</span></label>
                <input type="text" id="ncCpf" class="form-control" required maxlength="11" placeholder="00000000000">
            </div>
            <div class="form-group">
                <label class="form-label">RG</label>
                <input type="text" id="ncRg" class="form-control" placeholder="Opcional">
            </div>

            <div class="form-group">
                <label class="form-label">Data de Nascimento *</label>
                <input type="date" id="ncNasc" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Sexo *</label>
                <select id="ncSexo" class="form-select" required>
                    <option value="">Selecione</option>
                    <option value="M">Masculino</option>
                    <option value="F">Feminino</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Data de Admissão *</label>
                <input type="date" id="ncAdmissao" class="form-control" required>
            </div>
            <div class="form-group">
                {{-- espaço --}}
            </div>

            <div class="form-group">
                <label class="form-label">Setor *</label>
                <div style="display:flex;gap:6px">
                    <select id="ncSetor" class="form-select" required onchange="ncLoadFuncoes(this.value)">
                        <option value="">Selecione</option>
                        @foreach($setores as $s)
                        <option value="{{ $s->id }}">{{ $s->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Função *</label>
                <div style="display:flex;gap:6px">
                    <select id="ncFuncao" class="form-select" required>
                        <option value="">Selecione o setor</option>
                    </select>
                </div>
            </div>

        </div>

        <div id="ncErro" style="display:none;background:#fee2e2;color:#dc2626;border-radius:var(--r-sm);padding:10px 14px;font-size:12px;margin-top:12px"></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalNovoColaborador')">Cancelar</button>
        <button type="button" class="btn btn-primary" id="ncSalvarBtn" onclick="salvarNovoColaborador()">
            <i class="fas fa-save"></i> Salvar e Selecionar
        </button>
    </div>
</div>
</div>

@push('scripts')
<script>
const isSuperAdmin        = {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }};
const wppBaseRoute        = '{{ url('/asos') }}';
const empresaAtual        = {{ auth()->user()->empresa_id ?? 'null' }};
const csrfToken           = document.querySelector('meta[name=csrf-token]')?.content ?? '';
const MEDICOS_POR_CLINICA = @json($medicosPorClinica);

// ── WhatsApp direto da tabela ─────────────────────────────────────────────────
function enviarWhatsappAso(asoId) {
    const form = document.getElementById('formWhatsappAso');
    form.action = `${wppBaseRoute}/${asoId}/whatsapp`;
    form.submit();
}

// ── Excluir agendamento ───────────────────────────────────────────────────────
function excluirAso(asoId, nome) {
    if (!confirm(`Excluir o agendamento de "${nome}"?\n\nEsta ação não pode ser desfeita.`)) return;
    const form = document.getElementById('formExcluirAso');
    form.action = `${wppBaseRoute}/${asoId}`;
    form.submit();
}

// ── Batch select ─────────────────────────────────────────────────────────────
function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => { c.checked = cb.checked; });
    updateBatch();
}
function updateBatch() {
    const checked = document.querySelectorAll('.row-check:checked').length;
    document.getElementById('batchCount').textContent = checked;
    document.getElementById('batchPanel').classList.toggle('show', checked > 0);
    const all = document.querySelectorAll('.row-check').length;
    document.getElementById('checkAll').checked = checked > 0 && checked === all;
    document.getElementById('checkAll').indeterminate = checked > 0 && checked < all;
}
function deselectAll() {
    document.querySelectorAll('.row-check, #checkAll').forEach(c => c.checked = false);
    updateBatch();
}

// ── Mês de vencimento com ano ────────────────────────────────────────────────
document.querySelector('select[name="mes_venc"]')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('anoVencHidden').value = opt.dataset.ano ?? '';
});

// ── Agendamento rápido individual (editar agendamento existente) ─────────────
function abrirAgendamentoRapido(asoId) {
    const form = document.getElementById('formAgendarRapido');
    form.action = `/asos/${asoId}/agendar`;
    const row = document.getElementById('row_' + asoId);
    const nome = row.querySelector('td:nth-child(2) .font-bold')?.textContent?.trim() ?? '—';
    const tipo = row.querySelector('.badge')?.textContent?.trim() ?? '—';
    const venc = row.querySelector('td:nth-child(5)')?.textContent?.trim() ?? '—';
    document.getElementById('asoInfo').innerHTML =
        `<strong>${nome}</strong> · ${tipo} · Vence: <span style="color:#dc2626">${venc.split('\n')[0]}</span>`;
    openModal('modalAgendar');
}

document.getElementById('agClinica')?.addEventListener('change', function() {
    const wpp = this.options[this.selectedIndex]?.dataset?.whatsapp;
    document.getElementById('whatsappRow').style.display = wpp ? '' : 'none';
});

// ── Novo Agendamento (passo 1) ───────────────────────────────────────────────
function abrirNovoAgendamento() {
    if (!isSuperAdmin) novoLoadColaboradores();
    openModal('modalNovoAgendamento');
}

async function novoLoadSetores(empresaId) {
    const setorSel = document.getElementById('novoSetor');
    const colSel   = document.getElementById('novoColaborador');
    setorSel.innerHTML = '<option value="">Todos os setores</option>';
    colSel.innerHTML   = '<option value="">Selecione</option>';
    if (!empresaId) return;
    const r = await fetch(`/api/setores?empresa_id=${empresaId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await r.json();
    data.forEach(s => {
        const o = document.createElement('option');
        o.value = s.id; o.textContent = s.nome;
        setorSel.appendChild(o);
    });
    novoLoadColaboradores();
}

async function novoLoadColaboradores() {
    const colSel    = document.getElementById('novoColaborador');
    const setorId   = document.getElementById('novoSetor')?.value ?? '';
    const empresaId = isSuperAdmin ? (document.getElementById('novoEmpresa')?.value ?? '') : empresaAtual;
    if (isSuperAdmin && !empresaId) {
        colSel.innerHTML = '<option value="">Selecione a empresa primeiro</option>';
        return;
    }
    colSel.innerHTML = '<option value="">Carregando...</option>';
    const params = new URLSearchParams();
    if (empresaId) params.set('empresa_id', empresaId);
    if (setorId)   params.set('setor_id', setorId);
    const r = await fetch(`/api/colaboradores?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await r.json();
    colSel.innerHTML = '<option value="">Selecione o colaborador</option>' +
        data.map(c => `<option value="${c.id}" data-funcao-id="${c.funcao_id ?? ''}">${c.nome}${c.funcao ? ' — '+c.funcao : ''}</option>`).join('');
}

// ── Exames complementares: regra por tipo ────────────────────────────────────
// - mudanca_funcao  → usa a nova função selecionada
// - demais tipos    → usa a função atual do colaborador
async function preencherExames(funcaoId) {
    const campo = document.getElementById('novoExamesCompl');
    const tag   = document.getElementById('novoExamesTag');
    if (!campo) return;
    if (!funcaoId) {
        campo.value = '';
        campo.placeholder = 'Audiometria, Espirometria...';
        if (tag) tag.style.display = 'none';
        return;
    }
    campo.placeholder = 'Buscando exames da função...';
    try {
        const r    = await fetch(`/api/funcoes/${funcaoId}/exames`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await r.json();
        campo.value = data.join(', ');
        campo.placeholder = data.length ? '' : 'Nenhum exame cadastrado para esta função';
        if (tag) tag.style.display = data.length ? '' : 'none';
    } catch {
        campo.value = '';
        campo.placeholder = 'Audiometria, Espirometria...';
        if (tag) tag.style.display = 'none';
    }
}

// Ao selecionar colaborador → só preenche exames se NÃO for mudança de função
document.getElementById('novoColaborador')?.addEventListener('change', function() {
    const tipo = document.getElementById('novoTipo')?.value;
    if (tipo === 'mudanca_funcao') return; // exames virão da nova função
    const funcaoId = this.options[this.selectedIndex]?.dataset?.funcaoId;
    preencherExames(funcaoId);
});

// Ao selecionar nova função (mudança de função) → preenche exames da nova função
document.getElementById('novaFuncaoMudanca')?.addEventListener('change', function() {
    const tipo = document.getElementById('novoTipo')?.value;
    if (tipo !== 'mudanca_funcao') return;
    preencherExames(this.value || null);
});

function novoOnTipo(tipo) {
    const div = document.getElementById('mudancaFuncaoFields');
    div.style.display = tipo === 'mudanca_funcao' ? '' : 'none';
    div.querySelectorAll('select').forEach(s => {
        tipo === 'mudanca_funcao' ? s.setAttribute('required','') : s.removeAttribute('required');
    });

    // Limpa exames ao trocar tipo e reavalia fonte correta
    const campo = document.getElementById('novoExamesCompl');
    const tag   = document.getElementById('novoExamesTag');
    if (campo) { campo.value = ''; campo.placeholder = 'Audiometria, Espirometria...'; }
    if (tag)   tag.style.display = 'none';

    if (tipo !== 'mudanca_funcao') {
        // Reavalia exames pela função atual do colaborador já selecionado
        const colSel   = document.getElementById('novoColaborador');
        const funcaoId = colSel?.options[colSel.selectedIndex]?.dataset?.funcaoId;
        if (funcaoId) preencherExames(funcaoId);
    }
}

function novoOnClinica(sel) {
    const wpp = sel.options[sel.selectedIndex]?.dataset?.whatsapp;
    document.getElementById('novoWhatsappRow').style.display = wpp ? '' : 'none';
}

async function novoLoadFuncoesMudanca(setorId) {
    const sel = document.getElementById('novaFuncaoMudanca');
    sel.innerHTML = '<option value="">Carregando...</option>';
    if (!setorId) { sel.innerHTML = '<option value="">Selecione o setor</option>'; return; }
    const r = await fetch(`/api/funcoes?setor_id=${setorId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await r.json();
    sel.innerHTML = '<option value="">Selecione a função</option>' +
        data.map(f => `<option value="${f.id}">${f.nome}</option>`).join('');
    // Após carregar funções, limpa exames (aguarda seleção da nova função)
    preencherExames(null);
}

// ── Passo 2: Registrar Resultado do ASO ─────────────────────────────────────
function abrirRegistrarResultado(asoId, nome, tipo, dataAgendada, periodicidade, clinicaId) {
    const form = document.getElementById('formRegistrarResultado');
    form.action = `/asos/${asoId}/registrar-resultado`;
    form.dataset.periodicidade = periodicidade || 365;

    document.getElementById('rr_info').innerHTML =
        `<strong>${nome}</strong> · <span class="badge badge-secondary" style="font-size:10px">${tipo}</span> · Agendado: <strong>${dataAgendada}</strong>`;

    // Converte dd/mm/yyyy → yyyy-mm-dd e pré-preenche data exame
    const dateInput = document.getElementById('rr_data_exame');
    if (dateInput && dataAgendada) {
        const parts = dataAgendada.split('/');
        if (parts.length === 3) {
            const isoDate = `${parts[2]}-${parts[1]}-${parts[0]}`;
            dateInput.value = isoDate;
            // Auto-calcula vencimento imediatamente
            rrAutoVencimento(isoDate, periodicidade);
        }
    }

    // Limpa resultado e observações
    document.getElementById('rr_resultado').value = '';
    const obs = form.querySelector('textarea[name="observacoes"]');
    if (obs) obs.value = '';

    // Preenche médicos da clínica
    rrCarregarMedicos(clinicaId);

    openModal('modalRegistrarResultado');
}

function rrAutoVencimento(dataExame, periodicidadeOverride) {
    const form = document.getElementById('formRegistrarResultado');
    const periodicidade = parseInt(periodicidadeOverride ?? form?.dataset?.periodicidade ?? 365);
    const vencInput = document.getElementById('rr_data_vencimento');
    const hint      = document.getElementById('rr_venc_hint');
    if (!dataExame || !vencInput) return;
    const d = new Date(dataExame + 'T00:00:00');
    d.setDate(d.getDate() + periodicidade);
    vencInput.value = d.toISOString().split('T')[0];
    if (hint) hint.textContent = `(+${periodicidade} dias da função)`;
}

function rrCarregarMedicos(clinicaId) {
    const sel   = document.getElementById('rr_medico_select');
    const texto = document.getElementById('rr_medico_texto');
    const hint  = document.getElementById('rr_medico_hint');
    const medicos = clinicaId ? (MEDICOS_POR_CLINICA[clinicaId] ?? []) : [];

    sel.innerHTML = '<option value="">— Selecionar médico da clínica —</option>' +
        medicos.map(m => `<option value="${m.nome_com_crm}">${m.nome_com_crm}</option>`).join('');

    if (medicos.length === 1) {
        // Só um médico → pré-seleciona automaticamente
        sel.value   = medicos[0].nome_com_crm;
        texto.value = medicos[0].nome_com_crm;
        if (hint) hint.style.display = '';
    } else {
        texto.value = '';
        if (hint) hint.style.display = medicos.length > 0 ? '' : 'none';
    }
}

function rrOnMedicoSelect(sel) {
    const texto = document.getElementById('rr_medico_texto');
    if (sel.value) texto.value = sel.value;
}

// ── Cadastro rápido de colaborador ───────────────────────────────────────────
async function ncLoadSetores(empresaId) {
    const sel = document.getElementById('ncSetor');
    sel.innerHTML = '<option value="">Carregando...</option>';
    if (!empresaId) { sel.innerHTML = '<option value="">Selecione a empresa</option>'; return; }
    const r = await fetch(`/api/setores?empresa_id=${empresaId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await r.json();
    sel.innerHTML = '<option value="">Selecione</option>' + data.map(s => `<option value="${s.id}">${s.nome}</option>`).join('');
    document.getElementById('ncFuncao').innerHTML = '<option value="">Selecione o setor</option>';
}

async function ncLoadFuncoes(setorId) {
    const sel = document.getElementById('ncFuncao');
    sel.innerHTML = '<option value="">Carregando...</option>';
    if (!setorId) { sel.innerHTML = '<option value="">Selecione o setor</option>'; return; }
    const r = await fetch(`/api/funcoes?setor_id=${setorId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await r.json();
    sel.innerHTML = '<option value="">Selecione</option>' + data.map(f => `<option value="${f.id}">${f.nome}</option>`).join('');
}

async function salvarNovoColaborador() {
    const btn  = document.getElementById('ncSalvarBtn');
    const erro = document.getElementById('ncErro');
    erro.style.display = 'none';

    const empresaId = isSuperAdmin
        ? (document.getElementById('ncEmpresa')?.value ?? '')
        : empresaAtual;

    const payload = {
        nome:            document.getElementById('ncNome').value.trim(),
        cpf:             document.getElementById('ncCpf').value.replace(/\D/g,''),
        rg:              document.getElementById('ncRg').value.trim(),
        data_nascimento: document.getElementById('ncNasc').value,
        sexo:            document.getElementById('ncSexo').value,
        data_admissao:   document.getElementById('ncAdmissao').value,
        empresa_id:      empresaId,
        setor_id:        document.getElementById('ncSetor').value,
        funcao_id:       document.getElementById('ncFuncao').value,
    };

    // Validação básica
    if (!payload.nome || !payload.cpf || !payload.data_nascimento || !payload.sexo ||
        !payload.data_admissao || !payload.empresa_id || !payload.setor_id || !payload.funcao_id) {
        erro.textContent = 'Preencha todos os campos obrigatórios.';
        erro.style.display = '';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    try {
        const resp = await fetch('/api/colaboradores/criar', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     csrfToken,
            },
            body: JSON.stringify(payload),
        });

        const data = await resp.json();

        if (!resp.ok) {
            const msgs = data.errors ? Object.values(data.errors).flat().join(' | ') : (data.message ?? 'Erro ao salvar.');
            erro.textContent = msgs;
            erro.style.display = '';
            return;
        }

        // Adiciona ao select de colaboradores e seleciona
        const colSel = document.getElementById('novoColaborador');
        const opt    = document.createElement('option');
        opt.value               = data.id;
        opt.textContent         = data.nome;
        opt.dataset.funcaoId    = data.funcao_id ?? '';
        opt.selected            = true;
        colSel.appendChild(opt);
        colSel.dispatchEvent(new Event('change')); // aciona busca de exames

        closeModal('modalNovoColaborador');

        // Limpa o formulário
        ['ncNome','ncCpf','ncRg','ncNasc','ncAdmissao'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        document.getElementById('ncSexo').value  = '';
        document.getElementById('ncSetor').value  = '';
        document.getElementById('ncFuncao').innerHTML = '<option value="">Selecione o setor</option>';

    } catch (e) {
        erro.textContent = 'Erro de comunicação com o servidor.';
        erro.style.display = '';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Salvar e Selecionar';
    }
}

// ── Criar setor rápido ────────────────────────────────────────────────────────
async function criarSetor() {
    const nome = document.getElementById('novoSetorNome').value.trim();
    if (!nome) return;
    const empresaId = isSuperAdmin ? (document.getElementById('novoEmpresa')?.value ?? '') : empresaAtual;
    const r = await fetch('/api/setores/criar', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ nome, empresa_id: empresaId })
    });
    const data = await r.json();
    if (data.id) {
        const addOpt = (sel, id, nome) => {
            if (!sel) return;
            const o = document.createElement('option');
            o.value = id; o.textContent = nome; o.selected = true;
            sel.appendChild(o);
        };
        addOpt(document.getElementById('novoSetor'), data.id, data.nome);
        addOpt(document.getElementById('novoSetorMudanca'), data.id, data.nome);
        closeModal('modalNovoSetor');
        document.getElementById('novoSetorNome').value = '';
        novoLoadColaboradores();
    }
}

// ── Criar função rápida ───────────────────────────────────────────────────────
async function criarFuncao() {
    const nome      = document.getElementById('novaFuncaoNome').value.trim();
    const cbo       = document.getElementById('novaFuncaoCbo').value.trim();
    const setorId   = document.getElementById('novoSetorMudanca')?.value ?? '';
    const empresaId = isSuperAdmin ? (document.getElementById('novoEmpresa')?.value ?? '') : empresaAtual;
    if (!nome) return;
    const r = await fetch('/api/funcoes/criar', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ nome, cbo, setor_id: setorId, empresa_id: empresaId })
    });
    const data = await r.json();
    if (data.id) {
        const sel = document.getElementById('novaFuncaoMudanca');
        if (sel) {
            const o = document.createElement('option');
            o.value = data.id; o.textContent = data.nome; o.selected = true;
            sel.appendChild(o);
        }
        closeModal('modalNovaFuncao');
        document.getElementById('novaFuncaoNome').value = '';
        document.getElementById('novaFuncaoCbo').value = '';
    }
}
</script>
@endpush
@endsection
