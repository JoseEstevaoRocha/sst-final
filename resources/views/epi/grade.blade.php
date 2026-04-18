@extends('layouts.app')
@section('title','Grade de Estoque EPI')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Grade de Estoque — EPI</h1>
        <p class="page-sub">Visão de estoque por EPI e tamanho</p>
    </div>
    <div class="flex gap-8">
        <a href="{{ route('epis.entregas') }}" class="btn btn-secondary"><i class="fas fa-box-open"></i> Entregas</a>
        <a href="{{ route('epis.index') }}"    class="btn btn-secondary"><i class="fas fa-hard-hat"></i> Catálogo</a>
    </div>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:1rem">
    <form method="GET" class="flex gap-8 flex-wrap" style="padding:.75rem 1rem;align-items:flex-end">
        <div class="form-group" style="flex:1;min-width:180px;margin:0">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar EPI…">
        </div>
        <div class="form-group" style="min-width:160px;margin:0">
            <select name="tipo" class="form-select">
                <option value="">Todos os tipos</option>
                @foreach(['Calçado de Segurança','Luva','Óculos','Capacete','Protetor Auricular','Respirador','Cinto de Segurança','Colete','Uniforme','Outros'] as $t)
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
            <select name="alerta" class="form-select" id="filtroAlerta">
                <option value="">Todos os níveis</option>
                <option value="critico" {{ request('alerta')==='critico'?'selected':'' }}>Crítico (zerado)</option>
                <option value="baixo"   {{ request('alerta')==='baixo'?'selected':'' }}>Abaixo do mínimo</option>
                <option value="ok"      {{ request('alerta')==='ok'?'selected':'' }}>Estoque OK</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
        @if(request()->hasAny(['search','tipo','empresa_id','alerta']))
        <a href="{{ route('epis.grade') }}" class="btn btn-ghost">Limpar</a>
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

@if($epis->isEmpty())
<div class="card">
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-hard-hat"></i></div>
        <h3>Nenhum EPI com grade de tamanhos</h3>
        <p class="text-muted">Edite um EPI e marque "Requer tamanho" para ativá-lo na grade.</p>
        <a href="{{ route('epis.index') }}" class="btn btn-primary" style="margin-top:.5rem">
            <i class="fas fa-hard-hat"></i> Ir para Catálogo de EPIs
        </a>
    </div>
</div>
@else

{{-- Cards de grade por EPI --}}
<div class="grade-cards-grid">
@foreach($epis as $epi)
@php
    $tamEpi      = $epi->tamanhos->sortBy('ordem');
    $estoquesMap = $epi->estoques->keyBy('tamanho_id');
    $totalQty    = $epi->estoques->sum('quantidade');
    $minEstoque  = $epi->estoque_minimo ?? 0;

    // Determina alerta geral do card
    $temCritico = $tamEpi->contains(fn($t) => ($estoquesMap[$t->id]?->quantidade ?? 0) <= 0);
    $temBaixo   = !$temCritico && $tamEpi->contains(fn($t) => ($q = ($estoquesMap[$t->id]?->quantidade ?? 0)) > 0 && $minEstoque > 0 && $q < $minEstoque);
    $cardClass  = $temCritico ? 'grade-card-critico' : ($temBaixo ? 'grade-card-baixo' : '');
@endphp
<div class="grade-card {{ $cardClass }}" data-alerta="{{ $temCritico ? 'critico' : ($temBaixo ? 'baixo' : 'ok') }}">
    <div class="grade-card-header">
        <div>
            <div class="grade-card-nome">{{ $epi->nome }}</div>
            <div class="grade-card-meta">
                <span class="badge badge-secondary badge-sm">{{ $epi->tipo }}</span>
                @if($epi->numero_ca)
                <span class="text-11 text-muted">CA {{ $epi->numero_ca }}</span>
                @endif
            </div>
        </div>
        <div class="grade-card-total">
            <span class="grade-card-total-num {{ $temCritico ? 'text-danger' : ($temBaixo ? 'text-warning' : 'text-success') }}">{{ $totalQty }}</span>
            <span class="text-10 text-muted">total</span>
        </div>
    </div>

    <div class="grade-card-chips">
        @forelse($tamEpi as $tam)
        @php
            $est   = $estoquesMap[$tam->id] ?? null;
            $qty   = $est?->quantidade ?? 0;
            $nivel = $qty <= 0 ? 'danger' : ($minEstoque > 0 && $qty < $minEstoque ? 'warn' : 'ok');
            $icon  = $qty <= 0 ? 'fas fa-times-circle' : ($nivel === 'warn' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle');
        @endphp
        <div class="grade-cell grade-cell-{{ $nivel }}"
            title="{{ $tam->codigo }}{{ $tam->descricao ? ' — '.$tam->descricao : '' }} | Qtd: {{ $qty }}{{ $minEstoque > 0 ? ' / Mín: '.$minEstoque : '' }}"
            onclick="abrirMovimentacao({{ $epi->id }}, '{{ $epi->nome }}', {{ $tam->id }}, '{{ $tam->codigo }}', {{ $qty }})">
            <div class="grade-cell-code">{{ $tam->codigo }}</div>
            <div class="grade-cell-qty">{{ $qty }}</div>
            @if($minEstoque > 0)
            <div class="grade-cell-min">mín: {{ $minEstoque }}</div>
            @endif
            <i class="{{ $icon }} grade-cell-icon"></i>
        </div>
        @empty
        <div class="text-13 text-muted" style="padding:.5rem">
            Nenhum tamanho configurado.
            <a href="{{ route('epis.edit', $epi->id) }}">Configurar</a>
        </div>
        @endforelse
    </div>

    <div class="grade-card-footer">
        <a href="{{ route('epis.edit', $epi->id) }}" class="btn btn-ghost btn-sm"><i class="fas fa-edit"></i> Editar</a>
        <a href="{{ route('epis.entregas') }}?epi_id={{ $epi->id }}" class="btn btn-ghost btn-sm"><i class="fas fa-box-open"></i> Entregas</a>
    </div>
</div>
@endforeach
</div>
@endif

{{-- Modal movimentação de estoque --}}
<div class="modal-overlay" id="modalMovEpi">
<div class="modal">
<div class="modal-header">
    <div class="modal-title"><i class="fas fa-boxes"></i> Movimentar Estoque</div>
    <button class="modal-close" onclick="closeModal('modalMovEpi')"><i class="fas fa-times"></i></button>
</div>
<div class="modal-body">
    <div id="movEpiInfo" style="background:var(--bg-secondary);border-radius:var(--r-sm);padding:10px 14px;margin-bottom:16px;font-size:13px"></div>
    <form method="POST" id="formMovEpi" action="">@csrf
        <input type="hidden" name="tamanho_id" id="movTamanhoId">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Empresa *</label>
                <select name="empresa_id" class="form-select" required>
                    @foreach($empresas as $emp)
                    <option value="{{ $emp->id }}" {{ (!auth()->user()->isSuperAdmin() || $empresas->count()===1) ? 'selected' : '' }}>
                        {{ $emp->nome_display }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo *</label>
                <select name="tipo" class="form-select" required>
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                    <option value="ajuste">Ajuste</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Quantidade *</label>
                <input type="number" name="quantidade" class="form-control" min="1" value="1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Motivo</label>
                <input type="text" name="motivo" class="form-control" placeholder="Ex: compra, uso, inventário">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalMovEpi')">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar</button>
        </div>
    </form>
</div>
</div>
</div>

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
    border-radius: var(--r-sm); padding: 8px 10px;
    min-width: 58px; position: relative; cursor: pointer;
    border: 1.5px solid transparent;
    transition: transform .1s, box-shadow .1s;
}
.grade-cell:hover { transform: scale(1.08); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
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
function abrirMovimentacao(epiId, epiNome, tamanhoId, tamCodigo, qtdAtual) {
    document.getElementById('formMovEpi').action = `/epis/${epiId}/movimentar`;
    document.getElementById('movTamanhoId').value = tamanhoId;
    document.getElementById('movEpiInfo').innerHTML =
        `<strong>${epiNome}</strong> — Tamanho <strong>${tamCodigo}</strong>` +
        `<span style="float:right">Estoque atual: <strong>${qtdAtual}</strong></span>`;
    openModal('modalMovEpi');
}

const alertaFilter = '{{ request("alerta") }}';
if (alertaFilter) {
    document.querySelectorAll('.grade-card').forEach(card => {
        if (card.dataset.alerta !== alertaFilter) card.style.display = 'none';
    });
}
</script>
@endpush
