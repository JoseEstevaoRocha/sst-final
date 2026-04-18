@extends('layouts.app')
@section('title','Consulta de Grade')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Consulta de Grade</h1>
        <p class="page-sub">Visão geral do estoque por uniforme e tamanho</p>
    </div>
    <a href="{{ route('uniformes.index') }}" class="btn btn-secondary">← Catálogo</a>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:1rem">
    <form method="GET" class="flex gap-8 flex-wrap" style="padding:.75rem 1rem;align-items:flex-end">
        <div class="form-group" style="flex:1;min-width:180px;margin:0">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar uniforme…">
        </div>
        <div class="form-group" style="min-width:140px;margin:0">
            <select name="tipo" class="form-select">
                <option value="">Todos os tipos</option>
                @foreach(['Camisa','Calça','Bota','Jaleco','Colete','Cinto','Boné','Macacão','Luva','Outros'] as $t)
                <option value="{{ $t }}" {{ request('tipo')===$t?'selected':'' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        @if($empresas->count() > 1)
        <div class="form-group" style="min-width:180px;margin:0">
            <select name="empresa_id" class="form-select">
                <option value="">Todas as empresas</option>
                @foreach($empresas as $emp)
                <option value="{{ $emp->id }}" {{ request('empresa_id')==$emp->id?'selected':'' }}>{{ $emp->nome_display }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="form-group" style="min-width:160px;margin:0">
            <select name="alerta" id="filtroAlerta" class="form-select">
                <option value="">Todos os níveis</option>
                <option value="critico" {{ request('alerta')==='critico'?'selected':'' }}>Crítico (zerado)</option>
                <option value="baixo"   {{ request('alerta')==='baixo'?'selected':'' }}>Abaixo do mínimo</option>
                <option value="ok"      {{ request('alerta')==='ok'?'selected':'' }}>Estoque OK</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
        @if(request()->hasAny(['search','tipo','empresa_id','alerta']))
        <a href="{{ route('uniformes.grade') }}" class="btn btn-ghost">Limpar</a>
        @endif
    </form>
</div>

{{-- Legenda --}}
<div class="flex gap-16" style="margin-bottom:1rem;flex-wrap:wrap">
    <div class="flex gap-6 align-center">
        <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:var(--success)"></span>
        <span class="text-12 text-muted">Estoque OK</span>
    </div>
    <div class="flex gap-6 align-center">
        <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:var(--warning)"></span>
        <span class="text-12 text-muted">Abaixo do mínimo</span>
    </div>
    <div class="flex gap-6 align-center">
        <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:var(--danger)"></span>
        <span class="text-12 text-muted">Zerado / Crítico</span>
    </div>
</div>

@if($uniformes->isEmpty())
<div class="card">
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-table"></i></div>
        <h3>Nenhum uniforme ativo encontrado</h3>
        <a href="{{ route('uniformes.create') }}" class="btn btn-primary" style="margin-top:.5rem"><i class="fas fa-plus"></i> Cadastrar Uniforme</a>
    </div>
</div>
@else

{{-- Cards de grade por uniforme --}}
<div class="grade-cards-grid">
@foreach($uniformes as $u)
@php
    $totalQty = $u->estoques->sum('quantidade');
    $temCritico = $u->estoques->contains(fn($e) => $e->quantidade <= 0);
    $temBaixo   = $u->estoques->contains(fn($e) => $e->quantidade > 0 && $e->baixo_estoque);
    $cardClass  = $temCritico ? 'grade-card-critico' : ($temBaixo ? 'grade-card-baixo' : '');
@endphp
<div class="grade-card {{ $cardClass }}" data-alerta="{{ $temCritico ? 'critico' : ($temBaixo ? 'baixo' : 'ok') }}">
    <div class="grade-card-header">
        <div>
            <div class="grade-card-nome">{{ $u->nome }}</div>
            <div class="grade-card-meta">
                <span class="badge badge-secondary badge-sm">{{ $u->tipo }}</span>
                @if($u->empresa)
                <span class="text-11 text-muted">{{ $u->empresa->nome_display }}</span>
                @endif
            </div>
        </div>
        <div class="grade-card-total">
            <span class="grade-card-total-num {{ $temCritico ? 'text-danger' : ($temBaixo ? 'text-warning' : 'text-success') }}">{{ $totalQty }}</span>
            <span class="text-10 text-muted">total</span>
        </div>
    </div>

    <div class="grade-card-chips">
        @forelse($u->estoques->sortBy('tamanho.ordem') as $est)
        @php
            $nivel = $est->quantidade <= 0 ? 'danger' : ($est->baixo_estoque ? 'warn' : 'ok');
            $icon  = $est->quantidade <= 0 ? 'fas fa-times-circle' : ($est->baixo_estoque ? 'fas fa-exclamation-circle' : 'fas fa-check-circle');
        @endphp
        <div class="grade-cell grade-cell-{{ $nivel }}" title="{{ $est->tamanho->descricao }} — Qtd: {{ $est->quantidade }} / Mín: {{ $est->minimo ?? 0 }}">
            <div class="grade-cell-code">{{ $est->tamanho->codigo }}</div>
            <div class="grade-cell-qty">{{ $est->quantidade }}</div>
            @if(($est->minimo ?? 0) > 0)
            <div class="grade-cell-min">mín: {{ $est->minimo }}</div>
            @endif
            <i class="{{ $icon }} grade-cell-icon"></i>
        </div>
        @empty
        <div class="text-13 text-muted" style="padding:.5rem">Sem grade cadastrada.</div>
        @endforelse
    </div>

    <div class="grade-card-footer">
        <a href="{{ route('uniformes.edit',$u->id) }}" class="btn btn-ghost btn-sm"><i class="fas fa-edit"></i> Editar</a>
        <a href="{{ route('uniformes.entregas') }}?uniforme_id={{ $u->id }}" class="btn btn-ghost btn-sm"><i class="fas fa-box-open"></i> Entregas</a>
    </div>
</div>
@endforeach
</div>
@endif

@endsection

@push('styles')
<style>
.grade-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px,1fr));
    gap: 1rem;
}
.grade-card {
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: var(--r);
    overflow: hidden;
    transition: box-shadow .15s;
}
.grade-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.grade-card-critico { border-color: rgba(220,38,38,.4); }
.grade-card-baixo   { border-color: rgba(217,119,6,.35); }
.grade-card-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 1rem 1rem .75rem;
    border-bottom: 1px solid var(--border);
}
.grade-card-nome { font-size: 14px; font-weight: 700; color: var(--text-1); margin-bottom: 4px; }
.grade-card-meta { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.grade-card-total { text-align: center; }
.grade-card-total-num { font-size: 22px; font-weight: 800; display: block; line-height: 1; }
.grade-card-chips {
    display: flex; flex-wrap: wrap; gap: 6px;
    padding: .75rem 1rem;
    min-height: 60px;
}
.grade-cell {
    display: flex; flex-direction: column; align-items: center;
    border-radius: var(--r-sm); padding: 6px 8px;
    min-width: 54px; position: relative; cursor: default;
    border: 1.5px solid transparent;
    transition: transform .1s;
}
.grade-cell:hover { transform: scale(1.06); }
.grade-cell-ok     { background: rgba(22,163,74,.1);  border-color: rgba(22,163,74,.25); }
.grade-cell-warn   { background: rgba(217,119,6,.12); border-color: rgba(217,119,6,.3); }
.grade-cell-danger { background: rgba(220,38,38,.12); border-color: rgba(220,38,38,.3); }
.grade-cell-code { font-size: 13px; font-weight: 700; color: var(--text-1); }
.grade-cell-qty  { font-size: 18px; font-weight: 800; line-height: 1.1; }
.grade-cell-ok   .grade-cell-qty   { color: #16a34a; }
.grade-cell-warn .grade-cell-qty   { color: #d97706; }
.grade-cell-danger .grade-cell-qty { color: #dc2626; }
.grade-cell-min  { font-size: 10px; color: var(--text-3); }
.grade-cell-icon { position: absolute; top: 4px; right: 4px; font-size: 10px; opacity: .6; }
.grade-cell-ok   .grade-cell-icon   { color: #16a34a; }
.grade-cell-warn .grade-cell-icon   { color: #d97706; }
.grade-cell-danger .grade-cell-icon { color: #dc2626; }
.grade-card-footer {
    display: flex; gap: 6px;
    padding: .5rem 1rem;
    border-top: 1px solid var(--border);
    background: rgba(0,0,0,.02);
}
</style>
@endpush

@push('scripts')
<script>
// Client-side alerta filter (after server-side search)
const alertaFilter = '{{ request("alerta") }}';
if (alertaFilter) {
    document.querySelectorAll('.grade-card').forEach(card => {
        if (card.dataset.alerta !== alertaFilter) card.style.display = 'none';
    });
}
</script>
@endpush
