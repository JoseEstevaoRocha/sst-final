@extends('layouts.app')
@section('title','Gestão de ASO')
@push('styles')
<style>
/* ── FILTROS AVANÇADOS ───────────────────────────────────────────── */
.adv-filter{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r);padding:16px 20px;margin-bottom:16px}
.adv-filter-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);margin-bottom:4px}

/* ── BARRA AÇÕES ────────────────────────────────────────────────── */
.agenda-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;background:var(--bg-alt);border:1px solid var(--border);border-radius:var(--r-sm);padding:10px 16px;margin-bottom:12px}
.sel-count{font-size:13px;font-weight:700;color:var(--brand);min-width:100px}

/* ── LEGENDA ────────────────────────────────────────────────────── */
.legend{display:flex;gap:16px;align-items:center;flex-wrap:wrap;font-size:11px;font-weight:600;color:var(--text-2)}
.legend-item{display:flex;align-items:center;gap:6px}
.leg-box{width:14px;height:14px;border-radius:3px;flex-shrink:0}
.leg-ok{background:rgba(22,163,74,.18)}
.leg-warn{background:rgba(217,119,6,.18)}
.leg-danger{background:rgba(220,38,38,.18)}
</style>
@endpush

@section('content')
<div class="page-header" style="margin-bottom:16px">
    <div>
        <h1 class="page-title">Gestão de ASO</h1>
        <p class="page-sub">Atestados de Saúde Ocupacional — visão unificada</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('asos.relatorio-clinica') }}" id="btnRelatorio" class="btn btn-secondary" target="_blank">
            <i class="fas fa-file-alt"></i> Relatório Clínica
        </a>
        <a href="{{ route('asos.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Novo ASO</a>
    </div>
</div>

{{-- KPIs ────────────────────────────────────────────────────────────────── --}}
<div class="kpi-row mb-16" style="grid-template-columns:repeat(5,1fr)">
    @foreach([
        ['Total','total','blue','fas fa-clipboard-list'],
        ['Em Dia','em_dia','green','fas fa-check-circle'],
        ['A Vencer (40d)','a_vencer_40','yellow','fas fa-clock'],
        ['Vencidos','vencidos','red','fas fa-times-circle'],
        ['Agendados','agendados','cyan','fas fa-calendar-check'],
    ] as [$l,$k,$c,$i])
    <div class="kpi kpi-{{ $c }} {{ $k==='vencidos'&&$stats[$k]>0?'kpi-pulse':'' }}" style="cursor:pointer"
        onclick="setFiltroSituacao('{{ $k }}')">
        <div class="kpi-icon"><i class="{{ $i }}"></i></div>
        <div class="kpi-label">{{ $l }}</div>
        <div class="kpi-val">{{ $stats[$k] }}</div>
    </div>
    @endforeach
</div>

{{-- FILTROS AVANÇADOS ────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('asos.index') }}" id="filterForm">
<div class="adv-filter">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <span style="font-size:13px;font-weight:700"><i class="fas fa-filter" style="color:var(--brand);margin-right:6px"></i>Filtros</span>
        <div style="display:flex;gap:6px">
            <button type="button" id="toggleAdv" class="btn btn-ghost btn-sm"><i class="fas fa-sliders-h"></i> Avançado</button>
            @if(request()->hasAny(['search','empresa_id','tipo','situacao','resultado','status','mes','setor_id','funcao_id']))
            <a href="{{ route('asos.index') }}" class="btn btn-ghost btn-sm" style="color:var(--danger)"><i class="fas fa-times"></i> Limpar</a>
            @endif
        </div>
    </div>

    {{-- Linha principal --}}
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:1;min-width:200px">
            <div class="adv-filter-label">Colaborador</div>
            <div style="position:relative">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:12px"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, CPF..." class="filter-input" style="padding-left:32px;width:100%">
            </div>
        </div>
        @if($empresas->count())
        <div style="min-width:180px">
            <div class="adv-filter-label">Empresa</div>
            <select name="empresa_id" class="filter-select" style="width:100%">
                <option value="">Todas</option>
                @foreach($empresas as $e)<option value="{{ $e->id }}" {{ request('empresa_id')==$e->id?'selected':'' }}>{{ $e->nome_display }}</option>@endforeach
            </select>
        </div>
        @endif
        <div style="min-width:140px">
            <div class="adv-filter-label">Situação</div>
            <select name="situacao" id="filtroSituacao" class="filter-select" style="width:100%">
                <option value="">Todas</option>
                <option value="em_dia"     {{ request('situacao')==='em_dia'?'selected':'' }}>Em Dia</option>
                <option value="a_vencer_40"{{ request('situacao')==='a_vencer_40'?'selected':'' }}>A Vencer (40d)</option>
                <option value="vencidos"   {{ request('situacao')==='vencidos'?'selected':'' }}>Vencidos</option>
                <option value="agendados"  {{ request('situacao')==='agendados'?'selected':'' }}>Agendados</option>
            </select>
        </div>
        <div style="min-width:130px">
            <div class="adv-filter-label">Tipo</div>
            <select name="tipo" class="filter-select" style="width:100%">
                <option value="">Todos</option>
                @foreach(['admissional'=>'Admissional','periodico'=>'Periódico','demissional'=>'Demissional','retorno'=>'Retorno','mudanca_funcao'=>'Mud. Função'] as $v=>$l)
                <option value="{{ $v }}" {{ request('tipo')===$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        @php
            $user = auth()->user();
            $empresaIdFiltro = request('empresa_id') ?? ($user->isSuperAdmin() ? null : $user->empresa_id);
            $setoresFiltro = $empresaIdFiltro ? \App\Models\Setor::where('empresa_id',$empresaIdFiltro)->orderBy('nome')->get() : collect();
            $funcoesFiltro = $empresaIdFiltro ? \App\Models\Funcao::where('empresa_id',$empresaIdFiltro)->orderBy('nome')->get() : collect();
        @endphp
        @if($setoresFiltro->count())
        <div style="min-width:150px">
            <div class="adv-filter-label">Setor</div>
            <select name="setor_id" class="filter-select" style="width:100%">
                <option value="">Todos os setores</option>
                @foreach($setoresFiltro as $s)
                <option value="{{ $s->id }}" {{ request('setor_id')==$s->id?'selected':'' }}>{{ $s->nome }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($funcoesFiltro->count())
        <div style="min-width:150px">
            <div class="adv-filter-label">Função</div>
            <select name="funcao_id" class="filter-select" style="width:100%">
                <option value="">Todas as funções</option>
                @foreach($funcoesFiltro as $f)
                <option value="{{ $f->id }}" {{ request('funcao_id')==$f->id?'selected':'' }}>{{ $f->nome }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <button type="submit" class="btn btn-primary" style="white-space:nowrap"><i class="fas fa-search"></i> Buscar</button>
        </div>
    </div>

    {{-- Linha avançada --}}
    <div id="advFilters" style="display:{{ request()->hasAny(['resultado','status','mes'])?'block':'none' }};margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px">
            <div>
                <div class="adv-filter-label">Resultado</div>
                <select name="resultado" class="filter-select" style="width:100%">
                    <option value="">Qualquer</option>
                    @foreach(['pendente'=>'Pendente','apto'=>'Apto','apto_restricoes'=>'Apto c/ Restr.','inapto'=>'Inapto'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('resultado')===$v?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="adv-filter-label">Status Logístico</div>
                <select name="status" class="filter-select" style="width:100%">
                    <option value="">Todos</option>
                    @foreach(['pendente'=>'Pendente','agendado'=>'Agendado','em_atendimento'=>'Em Atend.','finalizado'=>'Finalizado','em_transito'=>'Em Trânsito','recebido_empresa'=>'Recebido','entregue_colaborador'=>'Entregue'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('status')===$v?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="adv-filter-label">Mês de Vencimento</div>
                <select name="mes" class="filter-select" style="width:100%">
                    <option value="">Todos os meses</option>
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ request('mes')==$m?'selected':'' }}>
                        {{ \Carbon\Carbon::create()->month($m)->locale('pt_BR')->isoFormat('MMMM') }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
</form>

{{-- LEGENDA + BARRA DE AÇÕES ────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px">
    <div class="legend">
        <span class="legend-item"><span class="leg-box leg-ok"></span> Em dia (&gt;40d)</span>
        <span class="legend-item"><span class="leg-box leg-warn"></span> A vencer (≤40d)</span>
        <span class="legend-item"><span class="leg-box leg-danger"></span> Vencido</span>
    </div>
    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="checkAll" onchange="toggleAll(this)" style="accent-color:var(--brand)"> Selecionar todos
    </label>
</div>

<div class="agenda-bar" id="agendaBar" style="display:none">
    <span class="sel-count" id="selCount">0 selecionado(s)</span>
    <button class="btn btn-primary btn-sm" onclick="openLote()">
        <i class="fas fa-calendar-check"></i> Agendar Selecionados
    </button>
    <button class="btn btn-secondary btn-sm" onclick="gerarRelatorio()">
        <i class="fas fa-file-alt"></i> Relatório Selecionados
    </button>
    <button class="btn btn-ghost btn-sm" onclick="deselectAll()">
        <i class="fas fa-times"></i> Cancelar
    </button>
</div>

{{-- TABELA ───────────────────────────────────────────────────────────────── --}}
<div class="card p-0">
<div class="table-wrap">
<table class="table">
<thead><tr>
    <th style="width:36px"></th>
    <th>COLABORADOR</th>
    <th>TIPO</th>
    <th>DATA EXAME</th>
    <th>VENCIMENTO</th>
    <th>AGENDADO</th>
    <th>RESULTADO</th>
    <th>CLÍNICA / MÉDICO</th>
    <th>STATUS</th>
    <th style="width:120px">AÇÕES</th>
</tr></thead>
<tbody>
@forelse($asos as $a)
@php
    $dias = $a->dias_restantes;
    $rowClass = '';
    if ($dias !== null && $dias < 0) $rowClass = 'tr-danger';
    elseif ($dias !== null && $dias <= 40) $rowClass = 'tr-warning';
@endphp
<tr class="{{ $rowClass }}">
    <td style="text-align:center">
        <input type="checkbox" class="aso-cb" value="{{ $a->id }}" onchange="updateSel()" style="accent-color:var(--brand)">
    </td>
    <td>
        <div class="font-bold text-13">{{ $a->colaborador?->nome ?? '—' }}</div>
        <div class="text-11 text-muted">{{ $a->colaborador?->funcao?->nome ?? '' }} · {{ $a->colaborador?->setor?->nome ?? '' }}</div>
        <div class="text-11 text-muted">{{ $a->empresa?->nome_display ?? '' }}</div>
    </td>
    <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$a->tipo)) }}</span></td>
    <td class="font-mono text-12">{{ $a->data_exame?->format('d/m/Y') ?? '—' }}</td>
    <td>
        @if($a->tipo === 'demissional')
            <span class="text-muted text-11">—</span>
        @else
            <div class="font-mono text-12 {{ $dias !== null && $dias < 0 ? 'text-danger' : ($dias !== null && $dias <= 40 ? 'text-warning' : '') }}">
                {{ $a->data_vencimento?->format('d/m/Y') ?? '—' }}
            </div>
            @if($dias !== null)
            <div class="text-10 text-muted">
                {{ $dias < 0 ? abs($dias).'d vencido' : $dias.'d restantes' }}
            </div>
            @endif
        @endif
    </td>
    <td class="text-12">
        @if($a->data_agendada)
            <span class="badge badge-success">{{ $a->data_agendada->format('d/m/Y') }}</span>
            @if($a->horario_agendado)<div class="text-11">{{ substr($a->horario_agendado,0,5) }}</div>@endif
        @else
            <span class="text-muted text-11">—</span>
        @endif
    </td>
    <td>
        @php $rm=['pendente'=>'badge-secondary','apto'=>'badge-success','apto_restricoes'=>'badge-warning','inapto'=>'badge-danger']; @endphp
        <span class="badge {{ $rm[$a->resultado]??'badge-secondary' }}">{{ ucfirst(str_replace('_',' ',$a->resultado)) }}</span>
    </td>
    <td class="text-11">
        <div>{{ $a->clinica?->nome ?? $a->clinica_nome ?? '—' }}</div>
        @if($a->medico_responsavel)<div class="text-muted">{{ $a->medico_responsavel }}</div>@endif
    </td>
    <td>
        <form method="POST" action="{{ route('asos.logistica',$a->id) }}" style="display:inline">
            @csrf
            <select name="status_logistico" class="filter-select text-11" style="padding:4px 8px;width:100%" onchange="this.form.submit()">
                @foreach(['pendente'=>'Pendente','agendado'=>'Agendado','em_atendimento'=>'Em Atend.','finalizado'=>'Finalizado','em_transito'=>'Em Trânsito','recebido_empresa'=>'Recebido','entregue_colaborador'=>'Entregue'] as $v=>$l)
                <option value="{{ $v }}" {{ $a->status_logistico===$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>
        </form>
    </td>
    <td>
        <div class="flex gap-4">
            <button class="btn btn-primary btn-icon btn-sm" title="Agendar"
                onclick="openAgendar({{ $a->id }},'{{ addslashes($a->colaborador?->nome??'') }}','{{ $a->data_agendada?->format('Y-m-d')??'' }}','{{ $a->horario_agendado?substr($a->horario_agendado,0,5):'' }}',{{ $a->clinica_id??'null' }},'{{ addslashes($a->exames_complementares??'') }}')">
                <i class="fas fa-calendar-plus"></i>
            </button>
            <a href="{{ route('asos.edit',$a->id) }}" class="btn btn-secondary btn-icon btn-sm" title="Editar"><i class="fas fa-pencil-alt"></i></a>
            @if($a->clinica_id||$a->clinica_nome)
            <a href="{{ route('whatsapp.index') }}?aso={{ $a->id }}" class="btn btn-ghost btn-icon btn-sm" title="WhatsApp" style="color:#25d366"><i class="fab fa-whatsapp"></i></a>
            @endif
            <form method="POST" action="{{ route('asos.destroy',$a->id) }}" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-icon btn-sm text-danger" data-confirm="Excluir este ASO?"><i class="fas fa-trash-alt"></i></button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="10">
    <div class="empty-state py-32">
        <div class="empty-icon"><i class="fas fa-clipboard-list"></i></div>
        <h3>Nenhum ASO encontrado</h3>
        <p>Ajuste os filtros ou <a href="{{ route('asos.create') }}">cadastre um novo</a>.</p>
    </div>
</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($asos->hasPages())
<div class="pagination-bar">
    <span class="pag-info">{{ $asos->firstItem() }}–{{ $asos->lastItem() }} de {{ $asos->total() }}</span>
    <div class="pagination">
        @if(!$asos->onFirstPage())<a href="{{ $asos->previousPageUrl() }}" class="page-btn">‹</a>@else<span class="page-btn disabled">‹</span>@endif
        @foreach($asos->getUrlRange(max(1,$asos->currentPage()-2),min($asos->lastPage(),$asos->currentPage()+2)) as $p=>$u)<a href="{{ $u }}" class="page-btn {{ $p==$asos->currentPage()?'active':'' }}">{{ $p }}</a>@endforeach
        @if($asos->hasMorePages())<a href="{{ $asos->nextPageUrl() }}" class="page-btn">›</a>@else<span class="page-btn disabled">›</span>@endif
    </div>
</div>
@endif
</div>

{{-- MODAL AGENDAMENTO INDIVIDUAL ────────────────────────────────────────── --}}
<div class="modal-overlay" id="modalAgendar">
<div class="modal modal-sm">
    <div class="modal-header">
        <div class="modal-title"><i class="fas fa-calendar-check"></i> Agendar — <span id="agNome"></span></div>
        <button class="modal-close" onclick="closeModal('modalAgendar')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
    <form id="formAgendar" method="POST">
        @csrf
        <div class="flex flex-col gap-14">
            <div class="form-group">
                <label class="form-label">Data do Agendamento <span class="required">*</span></label>
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
                    @foreach($clinicas as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Exames Complementares</label>
                <textarea name="exames_complementares" id="agExames" class="form-control" rows="2" placeholder="Ex: Audiometria, Espirometria..."></textarea>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="enviar_whatsapp" id="agWpp" value="1">
                    <i class="fab fa-whatsapp text-success"></i> Enviar via WhatsApp para a clínica
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalAgendar')">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Confirmar</button>
        </div>
    </form>
    </div>
</div>
</div>

{{-- MODAL AGENDAMENTO EM LOTE ────────────────────────────────────────────── --}}
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
            <div style="padding:10px;background:var(--bg-alt);border-radius:8px;font-size:13px">
                <i class="fas fa-info-circle" style="color:var(--brand)"></i> <span id="loteInfo"></span>
            </div>
            <div class="form-group">
                <label class="form-label">Data do Agendamento <span class="required">*</span></label>
                <input type="date" name="data_agendada" id="loteData" class="form-control" required min="{{ today()->format('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Horário (opcional)</label>
                <input type="time" name="horario_agendado" id="loteHora" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Clínica</label>
                <select name="clinica_id" class="form-select">
                    <option value="">Selecione</option>
                    @foreach($clinicas as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Exames Complementares</label>
                <textarea name="exames_complementares" id="loteExames" class="form-control" rows="2" placeholder="Ex: Audiometria..."></textarea>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="gerar_relatorio" value="1" checked>
                    <i class="fas fa-file-alt"></i> Gerar relatório para clínica após agendar
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
// ── FILTRO AVANÇADO TOGGLE ────────────────────────────────────────────────
document.getElementById('toggleAdv')?.addEventListener('click', () => {
    const el = document.getElementById('advFilters');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
});

// ── CLICK NOS KPIs ────────────────────────────────────────────────────────
function setFiltroSituacao(v) {
    document.getElementById('filtroSituacao').value = v;
    document.getElementById('filterForm').submit();
}

// ── SELEÇÃO ───────────────────────────────────────────────────────────────
function toggleAll(cb) {
    document.querySelectorAll('.aso-cb').forEach(c => c.checked = cb.checked);
    updateSel();
}
function deselectAll() {
    document.querySelectorAll('.aso-cb').forEach(c => c.checked = false);
    document.getElementById('checkAll').checked = false;
    updateSel();
}
function updateSel() {
    const sels = [...document.querySelectorAll('.aso-cb:checked')];
    const n = sels.length;
    document.getElementById('selCount').textContent = n + ' selecionado(s)';
    document.getElementById('agendaBar').style.display = n > 0 ? 'flex' : 'none';
    document.getElementById('checkAll').indeterminate =
        n > 0 && n < document.querySelectorAll('.aso-cb').length;
    document.getElementById('checkAll').checked =
        n > 0 && n === document.querySelectorAll('.aso-cb').length;
}

// ── MODAL INDIVIDUAL ──────────────────────────────────────────────────────
function openAgendar(id, nome, data, hora, clinicaId, exames) {
    document.getElementById('agNome').textContent = nome;
    document.getElementById('agData').value    = data || '';
    document.getElementById('agHora').value    = hora || '';
    document.getElementById('agExames').value  = exames || '';
    if (clinicaId) document.getElementById('agClinica').value = clinicaId;
    document.getElementById('formAgendar').action = `/asos/${id}/agendar`;
    openModal('modalAgendar');
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
