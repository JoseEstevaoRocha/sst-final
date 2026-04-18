@extends('layouts.app')
@section('title','Colaboradores')
@push('styles')
<style>
.adv-filter{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r);padding:16px 20px;margin-bottom:16px}
.adv-filter-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;align-items:end}
.adv-filter-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);margin-bottom:4px}
.adv-filter-actions{display:flex;gap:8px;align-items:end;grid-column:span 2}
.active-filters{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.filter-tag{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;background:rgba(37,99,235,.1);color:var(--brand);border-radius:20px;font-size:11px;font-weight:600}
.filter-tag a{color:var(--brand);text-decoration:none;font-size:12px;opacity:.7}
.filter-tag a:hover{opacity:1}
</style>
@endpush
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Colaboradores</h1>
        <p class="page-sub">{{ $cols->total() }} colaborador(es) encontrado(s)</p>
    </div>
    <div class="flex gap-8">
        <a href="{{ route('importacao.index') }}" class="btn btn-secondary"><i class="fas fa-file-import"></i> Importar</a>
        <a href="{{ route('colaboradores.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Colaborador</a>
    </div>
</div>

{{-- FILTROS AVANÇADOS ────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('colaboradores.index') }}" id="filterForm">
<div class="adv-filter">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <span style="font-size:13px;font-weight:700;color:var(--text-1)"><i class="fas fa-filter" style="color:var(--brand);margin-right:6px"></i>Filtros de Busca</span>
        <div style="display:flex;gap:6px">
            <button type="button" id="toggleFilters" class="btn btn-ghost btn-sm"><i class="fas fa-sliders-h"></i> Avançado</button>
            @if(request()->hasAny(['search','empresa_id','setor_id','funcao_id','status','sexo','admissao_de','admissao_ate','jovem_aprendiz']))
            <a href="{{ route('colaboradores.index') }}" class="btn btn-ghost btn-sm" style="color:var(--danger)"><i class="fas fa-times"></i> Limpar tudo</a>
            @endif
        </div>
    </div>

    {{-- LINHA PRINCIPAL --}}
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:220px">
            <div class="adv-filter-label">Busca livre</div>
            <div style="position:relative">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:12px"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, CPF, matrícula ou PIS..." class="filter-input" style="padding-left:32px;width:100%">
            </div>
        </div>

        @if($empresas->count())
        <div style="min-width:200px">
            <div class="adv-filter-label">Empresa</div>
            <select name="empresa_id" class="filter-select" style="width:100%">
                <option value="">Todas as empresas</option>
                @foreach($empresas as $e)<option value="{{ $e->id }}" {{ request('empresa_id')==$e->id?'selected':'' }}>{{ $e->nome_display }}</option>@endforeach
            </select>
        </div>
        @endif

        <div style="min-width:160px">
            <div class="adv-filter-label">Status</div>
            <select name="status" class="filter-select" style="width:100%">
                <option value="">Todos</option>
                @foreach(['Contratado','Demitido','Afastado','INSS'] as $st)<option value="{{ $st }}" {{ request('status')===$st?'selected':'' }}>{{ $st }}</option>@endforeach
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="white-space:nowrap"><i class="fas fa-search"></i> Buscar</button>
        </div>
    </div>

    {{-- FILTROS AVANÇADOS (expandível) --}}
    <div id="advFilters" style="display:{{ request()->hasAny(['setor_id','funcao_id','sexo','admissao_de','admissao_ate','jovem_aprendiz'])?'block':'none' }};margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
        <div class="adv-filter-grid">
            <div>
                <div class="adv-filter-label">Setor</div>
                <select name="setor_id" class="filter-select" style="width:100%">
                    <option value="">Todos os setores</option>
                    @foreach($setores as $s)<option value="{{ $s->id }}" {{ request('setor_id')==$s->id?'selected':'' }}>{{ $s->nome }}</option>@endforeach
                </select>
            </div>
            <div>
                <div class="adv-filter-label">Função</div>
                <select name="funcao_id" class="filter-select" style="width:100%">
                    <option value="">Todas as funções</option>
                    @foreach($funcoes as $f)<option value="{{ $f->id }}" {{ request('funcao_id')==$f->id?'selected':'' }}>{{ $f->nome }}</option>@endforeach
                </select>
            </div>
            <div>
                <div class="adv-filter-label">Sexo</div>
                <select name="sexo" class="filter-select" style="width:100%">
                    <option value="">Todos</option>
                    <option value="M" {{ request('sexo')==='M'?'selected':'' }}>Masculino</option>
                    <option value="F" {{ request('sexo')==='F'?'selected':'' }}>Feminino</option>
                </select>
            </div>
            <div>
                <div class="adv-filter-label">Admissão — De</div>
                <input type="date" name="admissao_de" value="{{ request('admissao_de') }}" class="form-control">
            </div>
            <div>
                <div class="adv-filter-label">Admissão — Até</div>
                <input type="date" name="admissao_ate" value="{{ request('admissao_ate') }}" class="form-control">
            </div>
            <div style="display:flex;align-items:flex-end;padding-bottom:2px">
                <label style="display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--text-2);cursor:pointer">
                    <input type="checkbox" name="jovem_aprendiz" value="1" {{ request('jovem_aprendiz')?'checked':'' }} style="accent-color:var(--brand)">
                    Jovem Aprendiz
                </label>
            </div>
        </div>
    </div>

    {{-- TAGS DOS FILTROS ATIVOS --}}
    @php
        $activeFilters = array_filter([
            'search'       => request('search'),
            'empresa_id'   => request('empresa_id') ? $empresas->find(request('empresa_id'))?->nome_display : null,
            'setor_id'     => request('setor_id') ? $setores->find(request('setor_id'))?->nome : null,
            'funcao_id'    => request('funcao_id') ? $funcoes->find(request('funcao_id'))?->nome : null,
            'status'       => request('status'),
            'sexo'         => request('sexo') ? (request('sexo')==='M'?'Masculino':'Feminino') : null,
            'admissao_de'  => request('admissao_de') ? 'De: '.request('admissao_de') : null,
            'admissao_ate' => request('admissao_ate') ? 'Até: '.request('admissao_ate') : null,
            'jovem_aprendiz'=> request('jovem_aprendiz') ? 'Jovem Aprendiz' : null,
        ]);
    @endphp
    @if(count($activeFilters))
    <div class="active-filters">
        @foreach($activeFilters as $key => $label)
        <span class="filter-tag">
            {{ $label }}
            <a href="{{ route('colaboradores.index', array_merge(request()->except($key))) }}">×</a>
        </span>
        @endforeach
    </div>
    @endif
</div>
</form>

{{-- BULK BAR --}}
<div class="bulk-bar" id="bulkBar">
    <span id="bulkCount">0</span> selecionado(s)
    <button onclick="bulkDelete()" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Excluir selecionados</button>
</div>

{{-- TABELA --}}
<div class="card p-0">
<div class="table-wrap">
<table class="table">
<thead><tr>
    <th style="width:40px"><input type="checkbox" id="selectAll" style="accent-color:var(--brand)"></th>
    <th>COLABORADOR</th>
    <th>CPF</th>
    <th>EMPRESA</th>
    <th>SETOR / FUNÇÃO</th>
    <th>CBO</th>
    <th>SEXO</th>
    <th>ADMISSÃO</th>
    <th>STATUS</th>
    <th style="width:130px">AÇÕES</th>
</tr></thead>
<tbody>
@forelse($cols as $c)
<tr>
    <td><input type="checkbox" class="row-check" value="{{ $c->id }}" style="accent-color:var(--brand)"></td>
    <td>
        <div class="flex gap-10 align-center">
            <div class="avatar-sm">{{ $c->initials }}</div>
            <div>
                <div class="font-bold text-13">{{ $c->nome }}</div>
                @if($c->matricula)<div class="text-11 text-muted">Mat: {{ $c->matricula }}</div>@endif
                @if($c->telefone)<div class="text-11 text-muted"><i class="fas fa-phone" style="font-size:9px"></i> {{ $c->telefone }}</div>@endif
            </div>
        </div>
    </td>
    <td class="font-mono text-12">{{ $c->cpf }}</td>
    <td class="text-12">{{ $c->empresa->nome_display ?? '—' }}</td>
    <td>
        <div class="font-bold text-13">{{ $c->setor->nome ?? '—' }}</div>
        <div class="text-11 text-muted">{{ $c->funcao->nome ?? '—' }}</div>
    </td>
    <td class="font-mono text-11" style="color:var(--text-3)">{{ $c->funcao?->cbo ?? $c->cbo ?? '—' }}</td>
    <td><span class="badge {{ $c->sexo==='M' ? 'badge-info' : 'badge-purple' }}">{{ $c->sexo==='M'?'M':'F' }}</span></td>
    <td class="font-mono text-12">{{ $c->data_admissao?->format('d/m/Y') ?? '—' }}</td>
    <td>
        @php $badgeMap=['Contratado'=>'badge-success','Demitido'=>'badge-danger','Afastado'=>'badge-warning','INSS'=>'badge-secondary']; @endphp
        <span class="badge {{ $badgeMap[$c->status] ?? 'badge-secondary' }}">{{ $c->status }}</span>
        @if(isset($pppPendentes[$c->id]))
        <span class="badge badge-danger" style="margin-top:3px;font-size:9px" title="PPP pendente"><i class="fas fa-file-contract"></i> PPP</span>
        @endif
    </td>
    <td>
        <div class="flex gap-4">
            <a href="{{ route('ficha.show',$c->id) }}" class="btn btn-ghost btn-icon" title="Ficha"><i class="fas fa-id-card"></i></a>
            <a href="{{ route('asos.create', ['colaborador_id'=>$c->id]) }}" class="btn btn-ghost btn-icon" title="Agendar ASO" style="color:#0891b2"><i class="fas fa-calendar-plus"></i></a>
            <a href="{{ route('colaboradores.edit',$c->id) }}" class="btn btn-secondary btn-icon" title="Editar"><i class="fas fa-pencil-alt"></i></a>
            @if($c->status === 'Contratado')
            <button type="button" class="btn btn-ghost btn-icon text-danger"
                title="Demitir"
                onclick="abrirDemissao({{ $c->id }}, '{{ addslashes($c->nome) }}')">
                <i class="fas fa-user-minus"></i>
            </button>
            @endif
            <form method="POST" action="{{ route('colaboradores.destroy',$c->id) }}" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir {{ $c->nome }}?" title="Excluir"><i class="fas fa-trash-alt"></i></button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="10">
    <div class="empty-state" style="padding:40px 0">
        <div class="empty-icon"><i class="fas fa-users"></i></div>
        <h3>Nenhum colaborador encontrado</h3>
        <p>Ajuste os filtros ou <a href="{{ route('colaboradores.create') }}">cadastre um novo</a>.</p>
    </div>
</td></tr>
@endforelse
</tbody>
</table>
</div>

@if($cols->hasPages())
<div class="pagination-bar">
    <span class="pag-info">{{ $cols->firstItem() }}–{{ $cols->lastItem() }} de {{ $cols->total() }}</span>
    <div class="pagination">
        @if(!$cols->onFirstPage())<a href="{{ $cols->previousPageUrl() }}" class="page-btn">‹</a>@else<span class="page-btn disabled">‹</span>@endif
        @foreach($cols->getUrlRange(max(1,$cols->currentPage()-2),min($cols->lastPage(),$cols->currentPage()+2)) as $p=>$u)<a href="{{ $u }}" class="page-btn {{ $p==$cols->currentPage()?'active':'' }}">{{ $p }}</a>@endforeach
        @if($cols->hasMorePages())<a href="{{ $cols->nextPageUrl() }}" class="page-btn">›</a>@else<span class="page-btn disabled">›</span>@endif
    </div>
</div>
@endif
</div>

<form id="bulkForm" method="POST" action="{{ route('colaboradores.bulk-destroy') }}">
    @csrf @method('DELETE')
    <div id="bulkInputs"></div>
</form>

{{-- Modal Demissão Rápida ─────────────────────────────────────────────── --}}
<div id="modalDemissaoRapida" class="modal-overlay" style="display:none">
<div class="modal" style="max-width:460px">
    <div class="modal-header">
        <h3 class="modal-title" style="color:var(--danger)"><i class="fas fa-user-minus"></i> Demitir Colaborador</h3>
        <button class="modal-close" onclick="fecharDemissao()">&times;</button>
    </div>
    <form id="formDemissaoRapida" method="POST" action="">
    @csrf
    <div class="modal-body" style="display:flex;flex-direction:column;gap:16px">
        <div style="background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.2);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--danger)">
            <i class="fas fa-exclamation-triangle"></i>
            Demitir: <strong id="demissaoNome"></strong>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Data da Demissão *</label>
            <input type="date" name="data_demissao" id="demissaoData" class="form-control" required value="{{ date('Y-m-d') }}">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Motivo <span style="font-weight:400;color:var(--text-3)">(opcional)</span></label>
            <textarea name="demissao_motivo" class="form-control" rows="2" maxlength="300"
                placeholder="Ex: Pedido de demissão, dispensa sem justa causa..."></textarea>
        </div>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600">
            <input type="checkbox" name="periodo_experiencia" value="1" style="width:16px;height:16px">
            Período de Experiência (até 90 dias)
        </label>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="fecharDemissao()">Cancelar</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-check"></i> Confirmar Demissão</button>
    </div>
    </form>
</div>
</div>

@endsection
@push('scripts')
<script>
// Avançado toggle
document.getElementById('toggleFilters')?.addEventListener('click', () => {
    const el = document.getElementById('advFilters');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
});

// Demissão rápida
function abrirDemissao(id, nome) {
    document.getElementById('demissaoNome').textContent = nome;
    document.getElementById('formDemissaoRapida').action = `/colaboradores/${id}/demitir`;
    document.getElementById('demissaoData').value = new Date().toISOString().slice(0,10);
    document.getElementById('modalDemissaoRapida').style.display = 'flex';
}
function fecharDemissao() {
    document.getElementById('modalDemissaoRapida').style.display = 'none';
}
document.getElementById('modalDemissaoRapida')?.addEventListener('click', function(e) {
    if (e.target === this) fecharDemissao();
});

// Bulk delete
function bulkDelete(){
    const ids = getSelectedIds();
    if (!ids.length) return;
    if (!confirm(`Excluir ${ids.length} colaborador(es)? Esta ação não pode ser desfeita.`)) return;
    const f = document.getElementById('bulkForm'), c = document.getElementById('bulkInputs');
    c.innerHTML = '';
    ids.forEach(id => { const i = document.createElement('input'); i.type='hidden'; i.name='ids[]'; i.value=id; c.appendChild(i); });
    f.submit();
}
</script>
@endpush
