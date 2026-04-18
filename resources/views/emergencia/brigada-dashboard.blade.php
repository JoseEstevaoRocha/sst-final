@extends('layouts.app')
@section('title','Dashboard Brigada de Incêndio')

@push('styles')
<style>
/* ── Gauge ring ─────────────────────────────────────────────── */
.gauge-ring { position:relative; display:inline-flex; align-items:center; justify-content:center; }
.gauge-ring svg { transform: rotate(-90deg); }
.gauge-ring .gauge-text { position:absolute; text-align:center; line-height:1.2; }

/* ── Setor cards ────────────────────────────────────────────── */
.setor-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 16px;
    transition: box-shadow .2s, border-color .2s;
}
.setor-card:hover { box-shadow: var(--shadow-md); border-color: var(--brand); }
.setor-pct-bar {
    height: 8px;
    background: var(--bg-secondary);
    border-radius: 99px;
    overflow: hidden;
    margin-top: 8px;
}
.setor-pct-fill { height: 100%; border-radius: 99px; transition: width .7s ease; }

/* ── Planta ─────────────────────────────────────────────────── */
#plantaWrap {
    position: relative;
    background: #f0f4f8;
    border: 2px solid var(--border);
    border-radius: var(--r);
    overflow: hidden;
    min-height: 500px;
    cursor: default;
    background-image:
        linear-gradient(rgba(0,0,0,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,.04) 1px, transparent 1px);
    background-size: 40px 40px;
}
#plantaWrap.modo-edicao { cursor: crosshair; }

.planta-setor {
    position: absolute;
    border: 2px solid;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    user-select: none;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
    transition: box-shadow .15s;
    min-width: 100px;
    min-height: 80px;
}
.planta-setor:hover { box-shadow: 0 4px 16px rgba(0,0,0,.18); }
.planta-setor.dragging { opacity: .85; box-shadow: 0 8px 24px rgba(0,0,0,.25); z-index: 999; }

.planta-setor-header {
    padding: 5px 8px 4px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4px;
}
.planta-setor-body {
    flex: 1;
    padding: 6px 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    align-content: flex-start;
    background: rgba(255,255,255,.6);
}
.planta-pessoa {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    cursor: pointer;
    position: relative;
}
.planta-pessoa svg { display: block; }
.planta-pessoa .pp-num {
    font-size: 8px;
    font-weight: 800;
    background: var(--brand);
    color: #fff;
    border-radius: 99px;
    padding: 0px 4px;
    line-height: 14px;
    position: absolute;
    top: -4px;
    right: -4px;
    z-index: 2;
}
.planta-pessoa .pp-tooltip {
    display: none;
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: #fff;
    font-size: 11px;
    padding: 5px 9px;
    border-radius: 6px;
    white-space: nowrap;
    z-index: 10;
    pointer-events: none;
    box-shadow: 0 2px 8px rgba(0,0,0,.3);
}
.planta-pessoa .pp-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: #1e293b;
}
.planta-pessoa:hover .pp-tooltip { display: block; }

.planta-resize-handle {
    position: absolute;
    right: 3px;
    bottom: 3px;
    width: 12px;
    height: 12px;
    cursor: se-resize;
    opacity: .4;
}
.planta-resize-handle:hover { opacity: 1; }
.planta-del-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: inherit;
    opacity: .5;
    padding: 0;
    font-size: 11px;
    line-height: 1;
}
.planta-del-btn:hover { opacity: 1; }

/* Legenda */
#plantaLegenda { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.leg-item {
    display: flex; align-items: center; gap: 6px;
    background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px;
    padding: 4px 10px; font-size: 11px; cursor: pointer;
}
.leg-item .leg-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.leg-num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 16px; height: 16px; border-radius: 50%;
    background: var(--brand); color: #fff;
    font-size: 8px; font-weight: 800; flex-shrink: 0;
}

/* Toolbar */
#plantaToolbar {
    display: flex; gap: 8px; align-items: center; flex-wrap: wrap;
    padding: 10px 12px; background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--r-sm); margin-bottom: 10px;
}
.toolbar-sep { width: 1px; height: 24px; background: var(--border); }
</style>
@endpush

@section('content')
@php
$cores = ['#2563eb','#16a34a','#dc2626','#d97706','#7c3aed','#0891b2','#db2777','#65a30d','#ea580c','#0d9488'];
$brig_por_setor = $brigadistas->groupBy(fn($b) => $b->colaborador?->setor_id ?? 0);
@endphp

{{-- Cabeçalho --}}
<div class="page-header">
    <div>
        <h1 class="page-title">Planta da Brigada</h1>
        <p class="page-sub">Visão geográfica e estatísticas por setor</p>
    </div>
    <div class="flex gap-8 align-center">
        @if(auth()->user()->isSuperAdmin())
        <form method="GET" action="{{ route('brigada.dashboard') }}" style="display:flex;gap:8px;align-items:center">
            <select name="empresa_id" class="filter-select" style="width:220px" onchange="this.form.submit()">
                <option value="">Todas as empresas</option>
                @foreach($empresas as $emp)
                <option value="{{ $emp->id }}" {{ $empresaId == $emp->id ? 'selected' : '' }}>{{ $emp->nome_display }}</option>
                @endforeach
            </select>
        </form>
        @endif
        <a href="{{ route('brigada.index') }}" class="btn btn-ghost">
            <i class="fas fa-list"></i> Lista de Brigadistas
        </a>
    </div>
</div>

@if(!empty($requireEmpresa))
<div class="card" style="padding:60px 40px;text-align:center;border:2px dashed var(--border)">
    <div style="font-size:48px;margin-bottom:16px;opacity:.3"><i class="fas fa-building"></i></div>
    <h3 style="font-size:20px;font-weight:700;margin-bottom:8px">Selecione uma empresa</h3>
    <p style="color:var(--text-3);font-size:14px;margin-bottom:24px">Use o seletor no cabeçalho para filtrar a planta por empresa antes de visualizar os dados.</p>
    <form method="GET" action="{{ route('brigada.dashboard') }}" style="display:flex;justify-content:center;gap:10px">
        <select name="empresa_id" class="filter-select" style="width:260px" required>
            <option value="">— Selecione a empresa —</option>
            @foreach($empresas as $emp)
            <option value="{{ $emp->id }}">{{ $emp->nome_display }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Ver Planta</button>
    </form>
</div>
@else
{{-- ── KPIs ─────────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:220px 1fr;gap:16px;margin-bottom:20px;align-items:start">

    {{-- Gauge geral --}}
    <div class="card" style="padding:20px;text-align:center">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:12px">
            Cobertura Geral
        </div>
        @php
        $conforme = $pctGeral >= $pctMinimo;
        $pctColor = $conforme ? '#16a34a' : ($pctGeral >= $pctMinimo * 0.5 ? '#d97706' : '#dc2626');
        $dash   = 2 * M_PI * 52;
        $filled = $dash * min($pctGeral / 100, 1);
        @endphp
        <div class="gauge-ring" style="margin:0 auto 12px">
            <svg width="130" height="130" viewBox="0 0 130 130">
                <circle cx="65" cy="65" r="52" fill="none" stroke="var(--bg-secondary)" stroke-width="12"/>
                <circle cx="65" cy="65" r="52" fill="none" stroke="{{ $pctColor }}" stroke-width="12"
                    stroke-dasharray="{{ round($filled,2) }} {{ round($dash,2) }}"
                    stroke-linecap="round"/>
            </svg>
            <div class="gauge-text">
                <div style="font-size:26px;font-weight:900;color:var(--text-1);line-height:1">{{ $pctGeral }}%</div>
                <div style="font-size:10px;color:var(--text-3)">cobertura</div>
            </div>
        </div>
        <div style="font-size:28px;font-weight:800;color:var(--text-1)">{{ $totalBrigadistas }}</div>
        <div style="font-size:12px;color:var(--text-3)">brigadistas / {{ $totalColaboradores }} colaboradores</div>

        {{-- Badge grupo de risco --}}
        <div style="margin-top:8px;font-size:11px;padding:4px 10px;border-radius:6px;
            background:{{ $corGrupo }}1a; color:{{ $corGrupo }}; font-weight:700; display:inline-block">
            {{ $labelGrupo }}
        </div>
        @if($empresa?->cnae)
        <div style="font-size:10px;color:var(--text-3);margin-top:2px">CNAE {{ $empresa->cnae }}</div>
        @endif

        {{-- Mínimo NBR 14276 com marcador na barra --}}
        <div style="margin-top:10px;width:100%">
            <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text-3);margin-bottom:4px">
                <span>Cobertura</span>
                <span style="color:{{ $pctColor }};font-weight:700">{{ $pctGeral }}% / mín. {{ $pctMinimo }}%</span>
            </div>
            <div style="position:relative;height:8px;background:var(--bg-secondary);border-radius:99px">
                <div style="height:100%;width:{{ min($pctGeral,100) }}%;background:{{ $pctColor }};border-radius:99px"></div>
                <div style="position:absolute;top:-3px;left:{{ min($pctMinimo,100) }}%;transform:translateX(-50%);width:2px;height:14px;background:{{ $corGrupo }};border-radius:2px" title="Mínimo: {{ $pctMinimo }}%"></div>
            </div>
        </div>

        <div style="margin-top:8px;font-size:10px;padding:4px 8px;border-radius:6px;
            background:{{ $conforme ? 'rgba(22,163,74,.1)' : 'rgba(220,38,38,.1)' }};
            color:{{ $pctColor }}">
            @if($conforme) ✓ Conforme NBR 14276
            @else ✗ Abaixo do mínimo — NBR 14276 exige {{ $pctMinimo }}%
            @endif
        </div>
        @php $faltam = max(0, (int)ceil($totalColaboradores * $pctMinimo / 100) - $totalBrigadistas); @endphp
        @if($faltam > 0)
        <div style="font-size:11px;color:var(--danger);margin-top:8px;font-weight:600">
            <i class="fas fa-exclamation-circle" style="font-size:10px"></i>
            Faltam <strong>{{ $faltam }}</strong> brigadista(s) para atingir a meta
        </div>
        @else
        <div style="font-size:11px;color:var(--success);margin-top:8px;font-weight:600">
            <i class="fas fa-check-circle" style="font-size:10px"></i> Meta atingida
        </div>
        @endif
    </div>

    {{-- Cards por setor --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px">
        @foreach($setores as $i => $s)
        @php
        $cor = $cores[$i % count($cores)];
        $pctS = $s['pct'];
        $corS = $pctS >= 20 ? '#16a34a' : ($pctS >= 10 ? '#d97706' : '#dc2626');
        @endphp
        <div class="setor-card">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <div style="width:10px;height:10px;border-radius:50%;background:{{ $cor }};flex-shrink:0"></div>
                <div style="font-size:12px;font-weight:700;color:var(--text-1);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="{{ $s['nome'] }}">
                    {{ $s['nome'] }}
                </div>
            </div>
            <div style="font-size:24px;font-weight:800;color:{{ $corS }};line-height:1">{{ $s['total_brig'] }}</div>
            <div style="font-size:11px;color:var(--text-3)">de {{ $s['total_col'] }} · {{ $pctS }}%</div>
            <div class="setor-pct-bar">
                <div class="setor-pct-fill" style="width:{{ min($pctS,100) }}%;background:{{ $corS }}"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── PLANTA INTERATIVA ───────────────────────────────────────────────── --}}
<div class="card" style="padding:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px">
        <div>
            <div class="card-title" style="margin:0"><i class="fas fa-map-marked-alt"></i> Planta da Fábrica</div>
            <div style="font-size:11px;color:var(--text-3);margin-top:2px">
                Arraste os setores para organizar o layout · Redimensione pelo canto inferior direito · Dados salvos automaticamente no navegador
            </div>
        </div>
        <div id="plantaToolbar">
            <button class="btn btn-sm btn-secondary" onclick="plantaToggleEdit()" id="btnModoEdit">
                <i class="fas fa-pencil-alt"></i> Modo Edição
            </button>
            <div class="toolbar-sep"></div>
            <button class="btn btn-sm btn-ghost" onclick="plantaAdicionarTodosSetores()" title="Adicionar todos os setores ao mapa">
                <i class="fas fa-layer-group"></i> Adicionar todos
            </button>
            <button class="btn btn-sm btn-ghost" onclick="plantaLimpar()" title="Limpar layout">
                <i class="fas fa-trash"></i> Limpar
            </button>
            <div class="toolbar-sep"></div>
            <button class="btn btn-sm btn-ghost" onclick="plantaExportar()" title="Exportar como imagem">
                <i class="fas fa-image"></i> Exportar
            </button>
        </div>
    </div>

    {{-- Toolbar: adicionar setor individualmente --}}
    <div id="addSetorBar" style="display:none;background:var(--bg-secondary);border-radius:var(--r-sm);padding:10px 14px;margin-bottom:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:600">Adicionar setor:</span>
        @foreach($setores as $i => $s)
        <button class="btn btn-sm" onclick="plantaAdicionarSetor({{ $s['id'] }},'{{ addslashes($s['nome']) }}','{{ $cores[$i % count($cores)] }}')"
            style="background:{{ $cores[$i % count($cores)] }}1a;border:1px solid {{ $cores[$i % count($cores)] }};color:{{ $cores[$i % count($cores)] }};font-size:11px"
            id="btnAddSetor{{ $s['id'] }}">
            + {{ $s['nome'] }}
        </button>
        @endforeach
    </div>

    {{-- A planta --}}
    <div id="plantaWrap"></div>

    {{-- Legenda --}}
    <div style="margin-top:14px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-3);margin-bottom:8px">
            <i class="fas fa-list"></i> Legenda de brigadistas
        </div>
        <div id="plantaLegenda">
            @foreach($brigadistas as $idx => $b)
            @php $setorId = $b->colaborador?->setor_id ?? 0; $sIdx = $setores->search(fn($s) => $s['id'] === $setorId); $cor = $sIdx !== false ? $cores[$sIdx % count($cores)] : '#64748b'; @endphp
            <div class="leg-item" id="legItem{{ $b->id }}" title="Clique para destacar">
                <span class="leg-num" style="background:{{ $cor }}">{{ $idx+1 }}</span>
                <span class="leg-dot" style="background:{{ $cor }}"></span>
                <span style="font-weight:600">{{ $b->colaborador?->nome ?? '—' }}</span>
                <span style="color:var(--text-3)">· {{ $b->funcao_brigada }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endif

@php
$brigadistasJs = isset($requireEmpresa) ? collect() : $brigadistas->map(function($b) {
    return [
        'id'             => $b->id,
        'nome'           => $b->colaborador?->nome ?? '—',
        'funcao_brigada' => $b->funcao_brigada,
        'setor_id'       => $b->colaborador?->setor_id,
        'setor_nome'     => $b->colaborador?->setor?->nome ?? '—',
    ];
})->values();
@endphp

@push('scripts')
<script>
// ── Dados PHP → JS ──────────────────────────────────────────────────────────
const SETORES_DATA      = @json($setores);
const BRIGADISTAS_DATA  = @json($brigadistasJs);
const CORES             = @json($cores);

const STORAGE_KEY = 'brigada_planta_layout_{{ $empresaId ?? auth()->user()->empresa_id ?? "sa" }}';

// ── Estado ──────────────────────────────────────────────────────────────────
let modoEdicao   = false;
let plantaLayout = {}; // { setorId: {x,y,w,h} }
let dragging     = null;
let resizing     = null;
let offsetX = 0, offsetY = 0;

// ── Init ────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadLayout();
    renderPlanta();
    bindPlantaEvents();
});

function loadLayout() {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) plantaLayout = JSON.parse(saved);
    } catch(e) {}
}

function saveLayout() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(plantaLayout));
}

// ── Renderiza todos os setores presentes no layout ───────────────────────────
function renderPlanta() {
    const wrap = document.getElementById('plantaWrap');
    // Mantém apenas elementos já existentes que ainda estão no layout
    // Remove os que saíram
    wrap.querySelectorAll('.planta-setor').forEach(el => {
        if (!plantaLayout[el.dataset.setorId]) el.remove();
    });
    // Adiciona ou atualiza
    Object.entries(plantaLayout).forEach(([sid, pos]) => {
        sid = parseInt(sid);
        const setorData = SETORES_DATA.find(s => s.id === sid);
        if (!setorData) return;
        const sIdx  = SETORES_DATA.findIndex(s => s.id === sid);
        const cor   = CORES[sIdx % CORES.length];
        const brigs = BRIGADISTAS_DATA.filter(b => b.setor_id === sid);

        let el = wrap.querySelector(`.planta-setor[data-setor-id="${sid}"]`);
        if (!el) {
            el = document.createElement('div');
            el.className = 'planta-setor';
            el.dataset.setorId = sid;
            wrap.appendChild(el);
            bindSetorEl(el);
        }

        el.style.cssText = `
            left:${pos.x}px; top:${pos.y}px;
            width:${pos.w}px; height:${pos.h}px;
            border-color:${cor}; background:${cor}1a;
        `;

        const pct = setorData.pct ?? 0;
        const pctColor = pct >= 20 ? '#16a34a' : (pct >= 10 ? '#d97706' : '#dc2626');

        el.innerHTML = `
            <div class="planta-setor-header" style="background:${cor}22;color:${cor}">
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:80%">${setorData.nome}</span>
                <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
                    <span style="font-size:10px;color:${pctColor};font-weight:900">${brigs.length} brg</span>
                    ${modoEdicao ? `<button class="planta-del-btn" onclick="plantaRemoverSetor(${sid})" title="Remover setor do mapa">✕</button>` : ''}
                </div>
            </div>
            <div class="planta-setor-body">
                ${brigs.map((b, i) => `
                    <div class="planta-pessoa" title="">
                        <svg width="28" height="36" viewBox="0 0 28 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="14" cy="8" r="7" fill="${cor}" stroke="white" stroke-width="1.5"/>
                            <path d="M2 34 C2 24 6 20 14 20 C22 20 26 24 26 34" fill="${cor}" stroke="white" stroke-width="1.5"/>
                        </svg>
                        <span class="pp-num" style="background:${cor}">${BRIGADISTAS_DATA.indexOf(b)+1}</span>
                        <div class="pp-tooltip">${b.nome}<br><span style="opacity:.7;font-size:10px">${b.funcao_brigada}</span></div>
                    </div>
                `).join('')}
                ${brigs.length === 0 ? `<span style="font-size:11px;color:#94a3b8;align-self:center">Sem brigadistas</span>` : ''}
            </div>
            ${modoEdicao ? `<div class="planta-resize-handle" data-setor-id="${sid}">
                <svg width="12" height="12" viewBox="0 0 12 12"><path d="M2 10L10 2M6 10L10 6M10 10V10" stroke="#64748b" stroke-width="2" stroke-linecap="round"/></svg>
            </div>` : ''}
        `;
    });

    // Atualiza botões "adicionar setor"
    document.querySelectorAll('[id^="btnAddSetor"]').forEach(btn => {
        const sid = parseInt(btn.id.replace('btnAddSetor',''));
        btn.disabled = !!plantaLayout[sid];
        btn.style.opacity = btn.disabled ? '.4' : '1';
    });
}

// ── Bind drag/resize ─────────────────────────────────────────────────────────
function bindSetorEl(el) {
    el.addEventListener('mousedown', e => {
        if (!modoEdicao) return;
        // Ignorar cliques em botões e resize handle
        if (e.target.closest('.planta-del-btn, .planta-resize-handle, button')) return;
        e.preventDefault();
        const rect = el.getBoundingClientRect();
        const wrapRect = document.getElementById('plantaWrap').getBoundingClientRect();
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
        dragging = el;
        el.classList.add('dragging');
    });
}

function bindPlantaEvents() {
    const wrap = document.getElementById('plantaWrap');

    document.addEventListener('mousemove', e => {
        if (dragging) {
            const wRect = wrap.getBoundingClientRect();
            const x = Math.max(0, Math.min(e.clientX - wRect.left - offsetX, wRect.width  - dragging.offsetWidth));
            const y = Math.max(0, Math.min(e.clientY - wRect.top  - offsetY, wRect.height - dragging.offsetHeight));
            dragging.style.left = x + 'px';
            dragging.style.top  = y + 'px';
        }
        if (resizing) {
            const wRect = wrap.getBoundingClientRect();
            const el  = resizing.el;
            const sid = parseInt(resizing.sid);
            const pos = plantaLayout[sid];
            const w = Math.max(120, e.clientX - wRect.left - pos.x);
            const h = Math.max(90,  e.clientY - wRect.top  - pos.y);
            el.style.width  = w + 'px';
            el.style.height = h + 'px';
            plantaLayout[sid].w = w;
            plantaLayout[sid].h = h;
        }
    });

    document.addEventListener('mouseup', e => {
        if (dragging) {
            const wRect = wrap.getBoundingClientRect();
            const sid = parseInt(dragging.dataset.setorId);
            plantaLayout[sid].x = parseInt(dragging.style.left);
            plantaLayout[sid].y = parseInt(dragging.style.top);
            dragging.classList.remove('dragging');
            dragging = null;
            saveLayout();
        }
        if (resizing) {
            resizing = null;
            saveLayout();
        }
    });

    // Resize handle via event delegation
    wrap.addEventListener('mousedown', e => {
        const handle = e.target.closest('.planta-resize-handle');
        if (!handle || !modoEdicao) return;
        e.preventDefault();
        e.stopPropagation();
        const sid = parseInt(handle.dataset.setorId);
        const el  = wrap.querySelector(`.planta-setor[data-setor-id="${sid}"]`);
        resizing = { el, sid };
    });
}

// ── Controles ────────────────────────────────────────────────────────────────
function plantaToggleEdit() {
    modoEdicao = !modoEdicao;
    const wrap = document.getElementById('plantaWrap');
    const btn  = document.getElementById('btnModoEdit');
    const bar  = document.getElementById('addSetorBar');
    wrap.classList.toggle('modo-edicao', modoEdicao);
    btn.innerHTML  = modoEdicao
        ? '<i class="fas fa-check"></i> Concluir Edição'
        : '<i class="fas fa-pencil-alt"></i> Modo Edição';
    btn.className  = modoEdicao ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary';
    bar.style.display = modoEdicao ? 'flex' : 'none';
    renderPlanta();
}

function plantaAdicionarSetor(sid, nome, cor) {
    if (plantaLayout[sid]) return;
    const wrap    = document.getElementById('plantaWrap');
    const wW = wrap.offsetWidth || 800;
    const wH = wrap.offsetHeight || 500;
    const keys = Object.keys(plantaLayout);
    // Posição automática em grade
    const col = keys.length % 4;
    const row = Math.floor(keys.length / 4);
    plantaLayout[sid] = { x: 20 + col*210, y: 20 + row*160, w: 190, h: 140 };
    saveLayout();
    renderPlanta();
}

function plantaAdicionarTodosSetores() {
    SETORES_DATA.forEach((s, i) => {
        if (plantaLayout[s.id]) return;
        const col = i % 4, row = Math.floor(i / 4);
        plantaLayout[s.id] = { x: 20 + col*210, y: 20 + row*170, w: 190, h: 150 };
    });
    saveLayout();
    renderPlanta();
}

function plantaRemoverSetor(sid) {
    delete plantaLayout[sid];
    saveLayout();
    renderPlanta();
}

function plantaLimpar() {
    if (!confirm('Limpar toda a planta?')) return;
    plantaLayout = {};
    saveLayout();
    renderPlanta();
}

function plantaExportar() {
    if (typeof html2canvas !== 'undefined') {
        html2canvas(document.getElementById('plantaWrap')).then(canvas => {
            const a = document.createElement('a');
            a.download = 'planta-brigada.png';
            a.href = canvas.toDataURL();
            a.click();
        });
    } else {
        alert('Para exportar, inclua a biblioteca html2canvas no projeto.');
    }
}
</script>
@endpush
@endsection
