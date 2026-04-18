@extends('layouts.app')
@section('title','ASOs Vencidos — Agendamento')
@push('styles')
<style>
.agenda-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
.sel-count{font-size:13px;font-weight:600;color:var(--brand);min-width:120px}
.month-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.month-tab{padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:var(--bg-sec);color:var(--text-2);transition:.15s}
.month-tab:hover,.month-tab.active{background:var(--brand);color:#fff;border-color:var(--brand)}
.cb-col{width:36px}
</style>
@endpush
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-exclamation-triangle text-danger"></i> ASOs Vencidos</h1>
        <p class="page-sub">{{ $asos->total() }} registro(s) — selecione para agendar</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('asos.index') }}" class="btn btn-ghost">← Voltar</a>
        <a href="{{ route('asos.relatorio-clinica') }}?{{ http_build_query(request()->only('ids','empresa_id')) }}" id="btnRelatorio" class="btn btn-secondary" target="_blank">
            <i class="fas fa-file-alt"></i> Relatório Clínica
        </a>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" id="filterForm">
<div class="filter-bar" style="margin-bottom:12px">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar colaborador..." class="filter-input" style="width:220px">
    @if($empresas->count())
    <select name="empresa_id" class="filter-select" style="width:200px" onchange="this.form.submit()">
        <option value="">Todas empresas</option>
        @foreach($empresas as $e)
        <option value="{{ $e->id }}" {{ request('empresa_id')==$e->id?'selected':'' }}>{{ $e->nome_display }}</option>
        @endforeach
    </select>
    @endif
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i></button>
    @if(request()->hasAny(['search','empresa_id','mes']))
    <a href="{{ route('asos.vencidos') }}" class="btn btn-ghost btn-sm">✕ Limpar</a>
    @endif
</div>
</form>

{{-- Filtro por mês --}}
<div class="month-tabs">
    <span class="month-tab {{ !request('mes') ? 'active' : '' }}" onclick="filterMes('')">Todos</span>
    @foreach(range(1,12) as $m)
    <span class="month-tab {{ request('mes')==$m ? 'active' : '' }}" onclick="filterMes({{ $m }})">
        {{ \Carbon\Carbon::create()->month($m)->locale('pt_BR')->isoFormat('MMM') }}
    </span>
    @endforeach
</div>

{{-- Barra de ações em lote --}}
<div class="agenda-bar">
    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="checkAll" onchange="toggleAll(this)"> Selecionar todos
    </label>
    <span class="sel-count" id="selCount">0 selecionado(s)</span>
    <button class="btn btn-primary btn-sm" id="btnAgendarLote" onclick="openLote()" disabled>
        <i class="fas fa-calendar-check"></i> Agendar Selecionados
    </button>
    <button class="btn btn-secondary btn-sm" id="btnRelLote" onclick="gerarRelatorio()" disabled>
        <i class="fas fa-file-alt"></i> Relatório Selecionados
    </button>
</div>

{{-- Tabela --}}
<div class="card p-0">
<div class="table-wrap"><table class="table">
<thead><tr>
    <th class="cb-col"></th>
    <th>COLABORADOR</th>
    <th>EMPRESA</th>
    <th>TIPO</th>
    <th>VENCIMENTO</th>
    <th>AGENDADO PARA</th>
    <th>CLÍNICA</th>
    <th>STATUS</th>
    <th>AÇÕES</th>
</tr></thead>
<tbody>
@forelse($asos as $a)
@php $dias = abs($a->dias_restantes ?? 0); @endphp
<tr class="tr-danger">
    <td class="cb-col"><input type="checkbox" class="aso-cb" value="{{ $a->id }}" onchange="updateSel()"></td>
    <td>
        <div class="font-bold text-13">{{ $a->colaborador?->nome ?? '—' }}</div>
        <div class="text-11 text-muted">{{ $a->colaborador?->funcao?->nome ?? '' }} · {{ $a->colaborador?->setor?->nome ?? '' }}</div>
        <div class="text-11 text-muted">CPF: {{ $a->colaborador?->cpf ?? '—' }}</div>
    </td>
    <td class="text-12">{{ $a->empresa?->razao_social ?? $a->empresa?->nome_display ?? '—' }}</td>
    <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$a->tipo)) }}</span></td>
    <td>
        <span class="font-mono text-12 text-danger">{{ $a->data_vencimento?->format('d/m/Y') ?? '—' }}</span>
        <div class="text-11 text-muted">há {{ $dias }}d</div>
    </td>
    <td class="text-12">
        @if($a->data_agendada)
            <span class="badge badge-success">{{ $a->data_agendada->format('d/m/Y') }}</span>
            @if($a->horario_agendado)<div class="text-11">{{ substr($a->horario_agendado,0,5) }}</div>@endif
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td class="text-12">{{ $a->clinica?->nome ?? $a->clinica_nome ?? '—' }}</td>
    <td>
        <span class="badge {{ $a->status_logistico==='agendado'?'badge-success':($a->status_logistico==='pendente'?'badge-warning':'badge-secondary') }}">
            {{ ucfirst(str_replace('_',' ',$a->status_logistico)) }}
        </span>
    </td>
    <td>
        <div class="flex gap-4">
            <button class="btn btn-primary btn-icon btn-sm" title="Agendar"
                onclick="openAgendar({{ $a->id }}, '{{ addslashes($a->colaborador?->nome ?? '') }}', '{{ $a->data_agendada?->format('Y-m-d') ?? '' }}', '{{ $a->horario_agendado ? substr($a->horario_agendado,0,5) : '' }}', {{ $a->clinica_id ?? 'null' }}, '{{ addslashes($a->exames_complementares ?? '') }}')">
                <i class="fas fa-calendar-plus"></i>
            </button>
            <a href="{{ route('asos.edit', $a->id) }}" class="btn btn-secondary btn-icon btn-sm" title="Editar">
                <i class="fas fa-pencil-alt"></i>
            </a>
            @if($a->clinica_id || $a->clinica_nome)
            <button class="btn btn-ghost btn-icon btn-sm text-success" title="WhatsApp"
                onclick="openAgendarWpp({{ $a->id }}, '{{ addslashes($a->colaborador?->nome ?? '') }}', {{ $a->clinica_id ?? 'null' }})">
                <i class="fab fa-whatsapp"></i>
            </button>
            @endif
        </div>
    </td>
</tr>
@empty
<tr><td colspan="9">
    <div class="empty-state"><div class="empty-icon"><i class="fas fa-check-circle" style="color:var(--success)"></i></div>
    <h3>Nenhum ASO vencido</h3></div>
</td></tr>
@endforelse
</tbody></table></div>
</div>
{{ $asos->links() }}

{{-- MODAL AGENDAMENTO INDIVIDUAL --}}
<div class="modal-overlay" id="modalAgendar">
<div class="modal modal-sm">
    <div class="modal-header">
        <div class="modal-title"><i class="fas fa-calendar-check"></i> Agendar ASO — <span id="agNome"></span></div>
        <button class="modal-close" onclick="closeModal('modalAgendar')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
    <form id="formAgendar" method="POST">
        @csrf
        <div class="flex flex-col gap-14">
            <div class="form-group">
                <label class="form-label">Data do Agendamento *</label>
                <input type="date" name="data_agendada" id="agData" class="form-control" required min="{{ today()->format('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Horário</label>
                <input type="time" name="horario_agendado" id="agHora" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Clínica</label>
                <select name="clinica_id" id="agClinica" class="form-select">
                    <option value="">Selecione a clínica</option>
                    @foreach($clinicas as $c)
                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Exames Complementares</label>
                <textarea name="exames_complementares" id="agExames" class="form-control" rows="3"
                    placeholder="Ex: Audiometria, Espirometria, Acuidade Visual..."></textarea>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="enviar_whatsapp" id="agWpp" value="1">
                    <i class="fab fa-whatsapp text-success"></i>
                    Enviar agendamento para clínica via WhatsApp
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalAgendar')">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Confirmar Agendamento</button>
        </div>
    </form>
    </div>
</div>
</div>

{{-- MODAL AGENDAMENTO EM LOTE --}}
<div class="modal-overlay" id="modalLote">
<div class="modal modal-sm">
    <div class="modal-header">
        <div class="modal-title"><i class="fas fa-calendar-check"></i> Agendar em Lote</div>
        <button class="modal-close" onclick="closeModal('modalLote')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
    <form id="formLote" method="POST" action="{{ route('asos.agendar-lote') }}">
        @csrf
        <div id="loteIds"></div>
        <div class="flex flex-col gap-14">
            <div style="padding:10px;background:var(--bg-sec);border-radius:8px;font-size:13px">
                <i class="fas fa-info-circle text-brand"></i>
                <span id="loteInfo"></span>
            </div>
            <div class="form-group">
                <label class="form-label">Data do Agendamento *</label>
                <input type="date" name="data_agendada" id="loteData" class="form-control" required min="{{ today()->format('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Horário (opcional)</label>
                <input type="time" name="horario_agendado" id="loteHora" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Exames Complementares</label>
                <textarea name="exames_complementares" id="loteExames" class="form-control" rows="3"
                    placeholder="Ex: Audiometria, Espirometria..."></textarea>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="gerar_relatorio" value="1" checked>
                    <i class="fas fa-file-alt"></i>
                    Gerar relatório para clínica após agendar
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalLote')">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Agendar Todos</button>
        </div>
    </form>
    </div>
</div>
</div>

@endsection
@push('scripts')
<script>
// ── SELEÇÃO ────────────────────────────────────────────────────────────────
function toggleAll(cb) {
    document.querySelectorAll('.aso-cb').forEach(c => c.checked = cb.checked);
    updateSel();
}
function updateSel() {
    const sels = [...document.querySelectorAll('.aso-cb:checked')];
    const n = sels.length;
    document.getElementById('selCount').textContent = n + ' selecionado(s)';
    document.getElementById('btnAgendarLote').disabled = n === 0;
    document.getElementById('btnRelLote').disabled = n === 0;
    document.getElementById('checkAll').indeterminate =
        n > 0 && n < document.querySelectorAll('.aso-cb').length;
    document.getElementById('checkAll').checked =
        n === document.querySelectorAll('.aso-cb').length && n > 0;
}

// ── FILTRO MÊS ────────────────────────────────────────────────────────────
function filterMes(m) {
    const url = new URL(window.location.href);
    if (m) { url.searchParams.set('mes', m); url.searchParams.set('ano', new Date().getFullYear()); }
    else   { url.searchParams.delete('mes'); url.searchParams.delete('ano'); }
    window.location = url;
}

// ── MODAL INDIVIDUAL ──────────────────────────────────────────────────────
function openAgendar(id, nome, data, hora, clinicaId, exames) {
    document.getElementById('agNome').textContent = nome;
    document.getElementById('agData').value = data || '';
    document.getElementById('agHora').value = hora || '';
    document.getElementById('agExames').value = exames || '';
    if (clinicaId) document.getElementById('agClinica').value = clinicaId;
    document.getElementById('formAgendar').action = `/asos/${id}/agendar`;
    openModal('modalAgendar');
}
function openAgendarWpp(id, nome, clinicaId) {
    openAgendar(id, nome, '', '', clinicaId, '');
    document.getElementById('agWpp').checked = true;
}

// ── MODAL LOTE ────────────────────────────────────────────────────────────
function openLote() {
    const ids = [...document.querySelectorAll('.aso-cb:checked')].map(c => c.value);
    document.getElementById('loteInfo').textContent = `${ids.length} ASO(s) serão agendados`;
    const cont = document.getElementById('loteIds');
    cont.innerHTML = ids.map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('');
    openModal('modalLote');
}

// ── RELATÓRIO SELECIONADOS ────────────────────────────────────────────────
function gerarRelatorio() {
    const ids = [...document.querySelectorAll('.aso-cb:checked')].map(c => c.value).join(',');
    window.open(`{{ route('asos.relatorio-clinica') }}?ids=${ids}`, '_blank');
}
</script>
@endpush
