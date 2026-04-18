@extends('layouts.app')
@section('title','Dashboard')
@push('styles')
<style>
/* ── GRID ───────────────────────────────────────────────────────── */
.dg{display:grid;gap:16px}
.dg-2{grid-template-columns:1fr 1fr}
.dg-3{grid-template-columns:1fr 1fr 1fr}
.dg-4{grid-template-columns:repeat(4,1fr)}
.dg-6{grid-template-columns:repeat(6,1fr)}

/* ── KPI CARDS ──────────────────────────────────────────────────── */
.kpi2{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r);padding:20px;display:flex;flex-direction:column;gap:6px;position:relative;overflow:hidden;transition:box-shadow .2s,border-color .2s}
.kpi2:hover{box-shadow:var(--shadow-md);border-color:var(--brand)}
.kpi2::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.kpi2.k-blue::before{background:var(--brand)}
.kpi2.k-green::before{background:var(--success)}
.kpi2.k-red::before{background:var(--danger)}
.kpi2.k-yellow::before{background:var(--warning)}
.kpi2.k-cyan::before{background:#0891b2}
.kpi2.k-purple::before{background:#7c3aed}
.kpi2.k-pink::before{background:#db2777}
.kpi2-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:4px}
.k-blue .kpi2-icon{background:rgba(37,99,235,.12);color:var(--brand)}
.k-green .kpi2-icon{background:rgba(22,163,74,.12);color:var(--success)}
.k-red .kpi2-icon{background:rgba(220,38,38,.12);color:var(--danger)}
.k-yellow .kpi2-icon{background:rgba(217,119,6,.12);color:var(--warning)}
.k-cyan .kpi2-icon{background:rgba(8,145,178,.12);color:#0891b2}
.k-purple .kpi2-icon{background:rgba(124,58,237,.12);color:#7c3aed}
.k-pink .kpi2-icon{background:rgba(219,39,119,.12);color:#db2777}
.kpi2-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3)}
.kpi2-val{font-size:30px;font-weight:800;color:var(--text-1);line-height:1}
.kpi2-sub{font-size:11px;color:var(--text-3);margin-top:2px}
.kpi2.pulse-red{animation:kpiPulse 2s infinite}
@keyframes kpiPulse{0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.3)}50%{box-shadow:0 0 0 8px rgba(220,38,38,0)}}

/* ── TABS ───────────────────────────────────────────────────────── */
.dash-tabs{display:flex;gap:4px;background:var(--bg-alt);border-radius:var(--r);padding:4px;margin-bottom:20px;width:fit-content}
.dash-tab{padding:8px 20px;border-radius:calc(var(--r) - 2px);font-size:13px;font-weight:600;cursor:pointer;background:transparent;border:none;color:var(--text-3);transition:all .2s}
.dash-tab.active{background:var(--bg-card);color:var(--text-1);box-shadow:var(--shadow-sm)}

/* ── ALERTAS ────────────────────────────────────────────────────── */
.alert2{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:var(--r-sm);margin-bottom:8px;font-size:13px;font-weight:500;text-decoration:none;transition:opacity .15s}
.alert2:hover{opacity:.85}
.alert2.a-danger{background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);color:var(--danger)}
.alert2.a-warning{background:rgba(217,119,6,.08);border:1px solid rgba(217,119,6,.2);color:var(--warning)}
.alert2-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px}
.a-danger .alert2-icon{background:rgba(220,38,38,.15)}.a-warning .alert2-icon{background:rgba(217,119,6,.15)}

/* ── CONFORMIDADE ───────────────────────────────────────────────── */
.conf-ring-wrap{display:flex;align-items:center;justify-content:center;gap:24px;padding:16px 0}
.conf-ring{position:relative;width:120px;height:120px;flex-shrink:0}
.conf-ring svg{transform:rotate(-90deg)}
.conf-ring-text{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.conf-ring-pct{font-size:26px;font-weight:800;line-height:1}
.conf-ring-lbl{font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.07em;margin-top:2px}
.conf-stats{display:flex;flex-direction:column;gap:10px}
.conf-stat{display:flex;align-items:center;gap:10px}
.conf-stat-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.conf-stat-info{font-size:12px;color:var(--text-2)}
.conf-stat-num{font-size:16px;font-weight:700;color:var(--text-1)}

/* ── BARRA PROGRESSO ────────────────────────────────────────────── */
.prog-bar{height:6px;background:var(--bg-alt);border-radius:3px;overflow:hidden;margin-top:4px}
.prog-fill{height:100%;border-radius:3px;transition:width .6s ease}

/* ── SETOR LISTA ────────────────────────────────────────────────── */
.setor-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.setor-row:last-child{border-bottom:none}
.setor-avatar{width:34px;height:34px;border-radius:8px;background:rgba(37,99,235,.1);color:var(--brand);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
.setor-nome{font-size:13px;font-weight:600;color:var(--text-1)}

/* ── RISCO CHIPS ────────────────────────────────────────────────── */
.risco-chip{display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-radius:var(--r-sm);margin-bottom:6px;font-size:12px;font-weight:600}
.risco-chip.fisico{background:rgba(8,145,178,.1);color:#0891b2}
.risco-chip.quimico{background:rgba(124,58,237,.1);color:#7c3aed}
.risco-chip.biologico{background:rgba(22,163,74,.1);color:var(--success)}
.risco-chip.ergonomico{background:rgba(217,119,6,.1);color:var(--warning)}
.risco-chip.acidente{background:rgba(220,38,38,.1);color:var(--danger)}
.risco-chip-num{font-size:16px;font-weight:800}

/* ── EMPRESA INFO ────────────────────────────────────────────────── */
.emp-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 20px;padding:4px 0}
.emp-info-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);margin-bottom:2px}
.emp-info-val{font-size:13px;font-weight:600;color:var(--text-1)}

/* ── TIMELINE ────────────────────────────────────────────────────── */
.tl-item{display:flex;gap:12px;padding:8px 0}
.tl-dot{width:10px;height:10px;border-radius:50%;background:var(--brand);flex-shrink:0;margin-top:4px}
.tl-content{font-size:12px;color:var(--text-2)}
.tl-name{font-weight:600;color:var(--text-1);font-size:13px}
.tl-date{font-size:11px;color:var(--text-3);margin-top:1px}

/* ── CIPA ────────────────────────────────────────────────────────── */
.status-badge-big{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700}
.status-badge-big.ativa{background:rgba(22,163,74,.12);color:var(--success)}
.status-badge-big.vencida{background:rgba(220,38,38,.12);color:var(--danger)}
.status-badge-big.sem-dados{background:rgba(100,116,139,.12);color:var(--text-3)}

/* ── CHART WRAPPER (fix infinite growing) ───────────────────────── */
.chart-box{position:relative;width:100%;height:220px;padding:0 16px 16px}

/* ── PESSOA CARD (aba empresa) ───────────────────────────────────── */
.pessoa-card{background:var(--bg-alt);border-radius:var(--r-sm);padding:12px 16px;display:flex;align-items:center;gap:12px}
.pessoa-avatar{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;flex-shrink:0}
.pessoa-avatar.m{background:rgba(37,99,235,.15);color:var(--brand)}
.pessoa-avatar.f{background:rgba(219,39,119,.15);color:#db2777}

/* ── SEXO BAR ────────────────────────────────────────────────────── */
.sexo-bar{height:14px;border-radius:7px;overflow:hidden;display:flex;margin-top:8px}
.sexo-bar-m{background:var(--brand);transition:width .6s}
.sexo-bar-f{background:#db2777;flex:1;transition:width .6s}

/* ── RESPONSIVE ──────────────────────────────────────────────────── */
@media(max-width:1200px){.dg-6{grid-template-columns:repeat(3,1fr)}.dg-4{grid-template-columns:1fr 1fr}.dg-3{grid-template-columns:1fr 1fr}}
@media(max-width:800px){.dg-6,.dg-4,.dg-3,.dg-2{grid-template-columns:1fr}.conf-ring-wrap{flex-direction:column}.emp-info-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

{{-- HEADER ──────────────────────────────────────────────────────────────── --}}
<div class="page-header" style="margin-bottom:16px">
    <div>
        <h1 class="page-title">Painel de Controle SST</h1>
        <p class="page-sub">Saúde e Segurança do Trabalho — visão consolidada em tempo real</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        @if(auth()->user()->isSuperAdmin())
        <form method="GET" action="{{ route('dashboard') }}" style="display:flex;gap:8px;align-items:center" id="empFilterForm">
            <select name="empresa_id" class="filter-select" id="empFilter" style="width:220px" onchange="this.form.submit()">
                <option value="">— Todas as empresas —</option>
                @foreach($empresas as $e)
                <option value="{{ $e->id }}" {{ $eid == $e->id ? 'selected' : '' }}>{{ $e->nome_display }}</option>
                @endforeach
            </select>
        </form>
        @if($eid)
        <form method="GET" action="{{ route('dashboard') }}" style="display:inline">
            <input type="hidden" name="empresa_id" value="">
            <button type="submit" class="btn btn-ghost btn-sm" title="Limpar filtro" style="font-size:11px;color:var(--text-3)">
                <i class="fas fa-times"></i> Limpar
            </button>
        </form>
        @endif
        @endif
        <span style="font-size:11px;color:var(--text-3)"><i class="fas fa-circle" style="color:var(--success);font-size:7px"></i> Atualizado agora</span>
    </div>
</div>

{{-- TABS ────────────────────────────────────────────────────────────────── --}}
<div class="dash-tabs">
    <button class="dash-tab active" data-tab="sst"><i class="fas fa-shield-alt" style="margin-right:6px"></i>SST</button>
    <button class="dash-tab" data-tab="empresa"><i class="fas fa-building" style="margin-right:6px"></i>Dados da Empresa</button>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- TAB SST                                                                --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="tab-sst" class="dash-tab-content">

{{-- ALERTAS ──────────────────────────────────────────────────────────────── --}}
@if(count($alertas) > 0)
<div class="card mb-16" style="border-left:3px solid var(--danger)">
    <div class="card-header" style="padding-bottom:12px">
        <div class="card-title" style="color:var(--danger)"><i class="fas fa-exclamation-triangle"></i> {{ count($alertas) }} Alerta(s) Crítico(s)</div>
        <a href="{{ route('dashboard.alertas') }}" class="btn btn-secondary btn-sm">Ver todos</a>
    </div>
    <div style="padding:0 16px 16px">
        @foreach($alertas as $a)
        <a href="{{ $a['link'] }}" class="alert2 a-{{ $a['nivel'] }}">
            <div class="alert2-icon"><i class="fas {{ $a['icon'] }}"></i></div>
            <span>{{ $a['msg'] }}</span>
            <i class="fas fa-chevron-right" style="margin-left:auto;font-size:10px;opacity:.5"></i>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- EXAMES OCUPACIONAIS ─────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;margin-top:4px">
    <i class="fas fa-stethoscope" style="color:var(--brand)"></i>
    <span style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3)">Exames Ocupacionais</span>
    <a href="{{ route('asos.agendamento') }}" style="margin-left:auto;font-size:12px;color:var(--brand);font-weight:600">Ver agendamento →</a>
</div>
<div class="dg dg-4 mb-16" style="grid-template-columns:repeat(5,1fr)">
    <div class="kpi2 k-purple">
        <div class="kpi2-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="kpi2-label">Agendados</div>
        <div class="kpi2-val">{{ $examesAgendados }}</div>
        <div class="kpi2-sub">aguardando realização</div>
    </div>
    <div class="kpi2 k-cyan">
        <div class="kpi2-icon"><i class="fas fa-calendar-week"></i></div>
        <div class="kpi2-label">Esta Semana</div>
        <div class="kpi2-val">{{ $examesSemana }}</div>
        <div class="kpi2-sub">exames nos próximos 7 dias</div>
    </div>
    <div class="kpi2 k-green">
        <div class="kpi2-icon"><i class="fas fa-check-circle"></i></div>
        <div class="kpi2-label">Realizados (mês)</div>
        <div class="kpi2-val">{{ $examesRealizadosMes }}</div>
        <div class="kpi2-sub">{{ now()->locale('pt_BR')->isoFormat('MMMM') }}</div>
    </div>
    <div class="kpi2 k-blue">
        <div class="kpi2-icon"><i class="fas fa-user-plus"></i></div>
        <div class="kpi2-label">Admissionais (mês)</div>
        <div class="kpi2-val">{{ $examesAdmissionaisMes }}</div>
        <div class="kpi2-sub">novos colaboradores</div>
    </div>
    <div class="kpi2 {{ $examesPendentes > 0 ? 'k-yellow' : 'k-green' }}">
        <div class="kpi2-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="kpi2-label">Pendentes</div>
        <div class="kpi2-val">{{ $examesPendentes }}</div>
        <div class="kpi2-sub">sem data agendada</div>
    </div>
</div>

{{-- KPIs PRINCIPAIS ──────────────────────────────────────────────────────── --}}
<div class="dg dg-4 mb-16">
    <div class="kpi2 k-blue">
        <div class="kpi2-icon"><i class="fas fa-users"></i></div>
        <div class="kpi2-label">Colaboradores Ativos</div>
        <div class="kpi2-val">{{ number_format($totalAtivos) }}</div>
        <div class="kpi2-sub">
            @if($totalAfastados > 0)<span style="color:var(--warning)">{{ $totalAfastados }} afastado(s)</span> · @endif
            {{ $admissoesRecentes }} admissão(ões) (30d)
        </div>
    </div>
    <div class="kpi2 {{ $asoVencidos > 0 ? 'k-red pulse-red' : 'k-green' }}">
        <div class="kpi2-icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="kpi2-label">ASOs Vencidos</div>
        <div class="kpi2-val">{{ $asoVencidos }}</div>
        <div class="kpi2-sub">A vencer (30d): <strong>{{ $asoAVencer }}</strong> · Sem ASO: <strong>{{ $semAso }}</strong></div>
    </div>
    <div class="kpi2 {{ $epiVencidos > 0 ? 'k-red' : 'k-green' }}">
        <div class="kpi2-icon"><i class="fas fa-hard-hat"></i></div>
        <div class="kpi2-label">EPIs — Prazo Vencido</div>
        <div class="kpi2-val">{{ $epiVencidos }}</div>
        <div class="kpi2-sub">A vencer (30d): {{ $epiAVencer }} · Estoque zero: {{ $estoquesBaixos }}</div>
    </div>
    <div class="kpi2 {{ $extintVencidos > 0 ? 'k-red pulse-red' : 'k-cyan' }}">
        <div class="kpi2-icon"><i class="fas fa-fire-extinguisher"></i></div>
        <div class="kpi2-label">Extintores Vencidos</div>
        <div class="kpi2-val">{{ $extintVencidos }}</div>
        <div class="kpi2-sub">Total cadastrado: {{ $extintTotal }}</div>
    </div>
</div>

{{-- LINHA 2: EMPRESA + CONFORMIDADE ASO ─────────────────────────────────── --}}
<div class="dg dg-2 mb-16">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-building"></i> Informações da Empresa</div>
            @if($empresa)
            <span class="badge {{ $empresa->status === 'ativa' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($empresa->status) }}</span>
            @endif
        </div>
        @if($empresa)
        <div style="padding:0 16px 16px">
            <div class="emp-info-grid">
                <div><div class="emp-info-label">Razão Social</div><div class="emp-info-val">{{ $empresa->razao_social }}</div></div>
                <div><div class="emp-info-label">Nome Fantasia</div><div class="emp-info-val">{{ $empresa->nome_fantasia ?: '—' }}</div></div>
                <div><div class="emp-info-label">CNPJ</div><div class="emp-info-val font-mono">{{ substr($empresa->cnpj,0,2).'.'.substr($empresa->cnpj,2,3).'.'.substr($empresa->cnpj,5,3).'/'.substr($empresa->cnpj,8,4).'-'.substr($empresa->cnpj,12,2) }}</div></div>
                <div><div class="emp-info-label">Cidade / Estado</div><div class="emp-info-val">{{ $empresa->cidade ? $empresa->cidade.', '.$empresa->estado : '—' }}</div></div>
                <div><div class="emp-info-label">Colaboradores Ativos</div><div class="emp-info-val" style="font-size:20px;color:var(--brand)">{{ $totalAtivos }}</div></div>
                <div><div class="emp-info-label">Status</div><div class="emp-info-val"><span class="status-badge-big {{ $empresa->status === 'ativa' ? 'ativa' : 'vencida' }}"><i class="fas fa-{{ $empresa->status === 'ativa' ? 'check-circle' : 'times-circle' }}"></i> {{ ucfirst($empresa->status) }}</span></div></div>
            </div>
        </div>
        @else
        <div style="padding:16px">
            <div class="emp-info-grid">
                <div><div class="emp-info-label">Empresas Ativas</div><div class="emp-info-val" style="font-size:24px;color:var(--brand)">{{ $empresas->count() }}</div></div>
                <div><div class="emp-info-label">Total Colaboradores</div><div class="emp-info-val" style="font-size:24px;color:var(--brand)">{{ number_format($totalAtivos) }}</div></div>
                <div><div class="emp-info-label">Afastados</div><div class="emp-info-val">{{ $totalAfastados }}</div></div>
                <div><div class="emp-info-label">Demitidos (30d)</div><div class="emp-info-val">{{ $demissoesRecentes }}</div></div>
            </div>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-shield-alt"></i> Conformidade — ASOs</div>
            <a href="{{ route('asos.index') }}" class="btn btn-secondary btn-sm">Ver ASOs</a>
        </div>
        <div class="conf-ring-wrap">
            @php
                $r2 = 52; $circ = 2 * pi() * $r2;
                $dash = ($asoConformidade / 100) * $circ;
                $color = $asoConformidade >= 80 ? '#16a34a' : ($asoConformidade >= 60 ? '#d97706' : '#dc2626');
            @endphp
            <div class="conf-ring">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="{{ $r2 }}" fill="none" stroke="rgba(255,255,255,.06)" stroke-width="10"/>
                    <circle cx="60" cy="60" r="{{ $r2 }}" fill="none" stroke="{{ $color }}" stroke-width="10"
                        stroke-dasharray="{{ round($dash,1) }} {{ round($circ,1) }}" stroke-linecap="round"/>
                </svg>
                <div class="conf-ring-text">
                    <div class="conf-ring-pct" style="color:{{ $color }}">{{ $asoConformidade }}%</div>
                    <div class="conf-ring-lbl">conformidade</div>
                </div>
            </div>
            <div class="conf-stats">
                <div class="conf-stat"><div class="conf-stat-dot" style="background:var(--success)"></div><div><div class="conf-stat-info">ASOs Válidos</div><div class="conf-stat-num" style="color:var(--success)">{{ $asoValidos }}</div></div></div>
                <div class="conf-stat"><div class="conf-stat-dot" style="background:var(--danger)"></div><div><div class="conf-stat-info">Vencidos</div><div class="conf-stat-num" style="color:var(--danger)">{{ $asoVencidos }}</div></div></div>
                <div class="conf-stat"><div class="conf-stat-dot" style="background:var(--warning)"></div><div><div class="conf-stat-info">A Vencer (30d)</div><div class="conf-stat-num" style="color:var(--warning)">{{ $asoAVencer }}</div></div></div>
                <div class="conf-stat"><div class="conf-stat-dot" style="background:var(--text-3)"></div><div><div class="conf-stat-info">Sem ASO</div><div class="conf-stat-num">{{ $semAso }}</div></div></div>
            </div>
        </div>
    </div>
</div>

{{-- WIDGET BRIGADA ──────────────────────────────────────────────────────── --}}
@php
$brigColor = $brigConforme ? 'var(--success)' : ($brigPct >= $brigPctMin * 0.5 ? 'var(--warning)' : 'var(--danger)');
$brigDash  = 2 * M_PI * 36;
$brigFill  = $brigDash * min($brigPct / 100, 1);
@endphp
<div class="card mb-16" style="padding:18px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:8px">
            <i class="fas fa-fire" style="color:#ea580c"></i>
            <span style="font-size:13px;font-weight:700;color:var(--text-1)">Brigada de Incêndio</span>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            @if(auth()->user()->isSuperAdmin() && !$eid)
            <span style="font-size:11px;color:var(--text-3)">Selecione uma empresa no filtro acima</span>
            @endif
            <a href="{{ route('brigada.dashboard') }}{{ $eid ? '?empresa_id='.$eid : '' }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-map-marked-alt"></i> Ver Planta
            </a>
        </div>
    </div>

    @if(auth()->user()->isSuperAdmin() && !$eid)
    <div style="padding:20px 0;color:var(--text-3);font-size:13px;text-align:center">
        <i class="fas fa-building" style="font-size:24px;opacity:.3;display:block;margin-bottom:8px"></i>
        Selecione uma empresa no filtro acima para visualizar os dados da brigada
    </div>
    @else
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
        {{-- Gauge mini --}}
        <div style="position:relative;width:90px;height:90px;flex-shrink:0">
            <svg width="90" height="90" viewBox="0 0 90 90" style="transform:rotate(-90deg)">
                <circle cx="45" cy="45" r="36" fill="none" stroke="var(--bg-secondary)" stroke-width="10"/>
                <circle cx="45" cy="45" r="36" fill="none" stroke="{{ $brigColor }}" stroke-width="10"
                    stroke-dasharray="{{ round($brigFill,2) }} {{ round($brigDash,2) }}"
                    stroke-linecap="round"/>
            </svg>
            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.1">
                <span style="font-size:18px;font-weight:800;color:{{ $brigColor }}">{{ $brigPct }}%</span>
                <span style="font-size:9px;color:var(--text-3)">cobertura</span>
            </div>
        </div>

        {{-- Stats --}}
        <div style="flex:1;min-width:160px">
            <div style="font-size:22px;font-weight:800;color:var(--text-1);line-height:1">{{ $brigTotal }}</div>
            <div style="font-size:12px;color:var(--text-3)">brigadistas ativos / {{ $brigAtivos }} colaboradores</div>

            <div style="margin-top:8px;width:100%;max-width:300px">
                <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text-3);margin-bottom:3px">
                    <span>Cobertura</span>
                    <span style="color:{{ $brigColor }};font-weight:700">{{ $brigPct }}% / mín. {{ $brigPctMin }}%</span>
                </div>
                <div style="position:relative;height:7px;background:var(--bg-secondary);border-radius:99px">
                    <div style="height:100%;width:{{ min($brigPct,100) }}%;background:{{ $brigColor }};border-radius:99px"></div>
                    <div style="position:absolute;top:-3px;left:{{ min($brigPctMin,100) }}%;transform:translateX(-50%);width:2px;height:13px;background:{{ $brigCor }};border-radius:2px"></div>
                </div>
            </div>

            <div style="margin-top:7px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <span style="font-size:11px;padding:3px 8px;border-radius:6px;background:{{ $brigCor }}1a;color:{{ $brigCor }};font-weight:700">{{ $brigLabel }}</span>
                @if($brigConforme)
                <span style="font-size:11px;color:var(--success);font-weight:600"><i class="fas fa-check-circle"></i> Conforme NBR 14276</span>
                @else
                <span style="font-size:11px;color:var(--danger);font-weight:600"><i class="fas fa-times-circle"></i> Abaixo do mínimo</span>
                @if($brigFaltam > 0)
                <span style="font-size:11px;color:var(--danger)">— faltam <strong>{{ $brigFaltam }}</strong> brigadista(s)</span>
                @endif
                @endif
            </div>
        </div>

        @if($brigadaEmpresa?->cnae)
        <div style="font-size:10px;color:var(--text-3);align-self:flex-end">CNAE {{ $brigadaEmpresa->cnae }}</div>
        @endif
    </div>
    @endif
</div>

{{-- GRÁFICOS TENDÊNCIA + SETOR ─────────────────────────────────────────── --}}
<div class="dg dg-2 mb-16">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-line"></i> Tendência (6 meses)</div></div>
        <div class="chart-box"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Colaboradores por Setor</div></div>
        <div class="chart-box"><canvas id="chartSetor"></canvas></div>
    </div>
</div>

{{-- SETORES + INDICADORES ───────────────────────────────────────────────── --}}
<div class="dg dg-2 mb-16">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-layer-group"></i> Colaboradores por Setor</div>
            <a href="{{ route('setores.index') }}" class="btn btn-ghost btn-sm">Gerenciar</a>
        </div>
        <div style="padding:0 16px 8px;max-height:320px;overflow-y:auto">
            @forelse($porSetor as $s)
            @php $pct = $totalAtivos > 0 ? round(($s->total / $totalAtivos) * 100) : 0; @endphp
            <div class="setor-row">
                <div class="setor-avatar">{{ strtoupper(substr($s->nome, 0, 2)) }}</div>
                <div style="flex:1;min-width:0">
                    <div class="setor-nome">{{ $s->nome }}</div>
                    <div class="prog-bar"><div class="prog-fill" style="width:{{ $pct }}%;background:var(--brand)"></div></div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:15px;font-weight:700;color:var(--text-1)">{{ $s->total }}</div>
                    <div style="font-size:10px;color:var(--text-3)">{{ $pct }}%</div>
                </div>
            </div>
            @empty
            <p style="color:var(--text-3);font-size:13px;padding:12px 0">Nenhum setor cadastrado.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-users"></i> Indicadores de Pessoas</div>
            <a href="{{ route('colaboradores.index') }}" class="btn btn-ghost btn-sm">Ver todos</a>
        </div>
        <div style="padding:0 16px 16px;display:flex;flex-direction:column;gap:10px">
            @foreach([['Ativos',$totalAtivos,'var(--success)','fa-user-check'],['Afastados',$totalAfastados,'var(--warning)','fa-user-clock'],['Demitidos (30d)',$demissoesRecentes,'var(--danger)','fa-user-minus'],['Admissões (30d)',$admissoesRecentes,'var(--brand)','fa-user-plus']] as [$lbl,$val,$cor,$ico])
            <div style="display:flex;align-items:center;gap:12px;padding:10px;background:var(--bg-alt);border-radius:var(--r-sm)">
                <div style="width:36px;height:36px;border-radius:8px;background:{{ $cor }}1a;color:{{ $cor }};display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0"><i class="fas {{ $ico }}"></i></div>
                <div style="flex:1"><div style="font-size:11px;color:var(--text-3)">{{ $lbl }}</div><div style="font-size:22px;font-weight:800;color:var(--text-1)">{{ $val }}</div></div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- RISCOS + CIPA + EPIs ─────────────────────────────────────────────────── --}}
<div class="dg dg-3 mb-16">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-exclamation-triangle"></i> Riscos (PGR)</div>
            <a href="{{ route('riscos.index') }}" class="btn btn-ghost btn-sm">Ver</a>
        </div>
        <div style="padding:0 16px 16px">
            <div style="font-size:28px;font-weight:800;color:var(--text-1);margin-bottom:12px">{{ $totalRiscos }} <span style="font-size:13px;font-weight:400;color:var(--text-3)">riscos identificados</span></div>
            @foreach(['fisico'=>['Físico','fa-bolt'],'quimico'=>['Químico','fa-flask'],'biologico'=>['Biológico','fa-biohazard'],'ergonomico'=>['Ergonômico','fa-chair'],'acidente'=>['Acidente','fa-hard-hat']] as $key=>[$lbl,$ico])
            <div class="risco-chip {{ $key }}">
                <div style="display:flex;align-items:center;gap:8px"><i class="fas {{ $ico }}"></i><span>{{ $lbl }}</span></div>
                <span class="risco-chip-num">{{ $riscosPorTipo[$key]->total ?? 0 }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-users-cog"></i> Obrigações Legais</div>
            <a href="{{ route('cipa.index') }}" class="btn btn-ghost btn-sm">CIPA</a>
        </div>
        <div style="padding:0 16px 16px;display:flex;flex-direction:column;gap:14px">
            <div style="padding:12px;background:var(--bg-alt);border-radius:var(--r-sm)">
                <div style="font-size:11px;color:var(--text-3);margin-bottom:6px;font-weight:700;text-transform:uppercase;letter-spacing:.07em">CIPA</div>
                @if($cipaAtiva > 0)
                <span class="status-badge-big ativa"><i class="fas fa-check-circle"></i> Ativa — {{ $cipaAtiva }} membro(s)</span>
                @elseif($cipaMandatoVencido > 0)
                <span class="status-badge-big vencida"><i class="fas fa-times-circle"></i> Mandato Vencido</span>
                @else
                <span class="status-badge-big sem-dados"><i class="fas fa-minus-circle"></i> Não cadastrada</span>
                @endif
            </div>
            <div style="padding:12px;background:var(--bg-alt);border-radius:var(--r-sm)">
                <div style="font-size:11px;color:var(--text-3);margin-bottom:6px;font-weight:700;text-transform:uppercase;letter-spacing:.07em">Extintores</div>
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="font-size:24px;font-weight:800;color:{{ $extintVencidos > 0 ? 'var(--danger)' : 'var(--success)' }}">{{ $extintVencidos }}</span>
                    <span style="font-size:12px;color:var(--text-3)">vencido(s) de {{ $extintTotal }} total</span>
                </div>
                @if($extintTotal > 0)
                <div class="prog-bar" style="margin-top:6px"><div class="prog-fill" style="width:{{ round((($extintTotal - $extintVencidos) / $extintTotal) * 100) }}%;background:{{ $extintVencidos > 0 ? 'var(--danger)' : 'var(--success)' }}"></div></div>
                @endif
            </div>
            <div style="padding:12px;background:var(--bg-alt);border-radius:var(--r-sm)">
                <div style="font-size:11px;color:var(--text-3);margin-bottom:6px;font-weight:700;text-transform:uppercase;letter-spacing:.07em">Brigada de Emergência</div>
                <a href="{{ route('brigada.index') }}" class="btn btn-secondary btn-sm btn-full"><i class="fas fa-user-shield"></i> Verificar membros</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-hard-hat"></i> EPIs</div>
            <a href="{{ route('epis.dashboard') }}" class="btn btn-ghost btn-sm">Painel</a>
        </div>
        <div style="padding:0 16px 16px;display:flex;flex-direction:column;gap:10px">
            @foreach([['Entregas Registradas',$epiEntregues,'var(--brand)'],['Prazo Vencido',$epiVencidos,'var(--danger)'],['Vencendo (30d)',$epiAVencer,'var(--warning)'],['Estoque Zerado',$estoquesBaixos,'var(--text-3)']] as [$lbl,$val,$cor])
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg-alt);border-radius:var(--r-sm)">
                <span style="font-size:12px;color:var(--text-2)">{{ $lbl }}</span>
                <span style="font-size:18px;font-weight:800;color:{{ $cor }}">{{ $val }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- SETORES RISCO + ADMISSÕES ───────────────────────────────────────────── --}}
<div class="dg dg-2 mb-16">
    @if($setoresRisco->count() > 0)
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-map-marked-alt"></i> Setores com Maior Exposição</div>
            <a href="{{ route('gro.matriz') }}" class="btn btn-ghost btn-sm">Matriz GRO</a>
        </div>
        <div style="padding:0 16px 16px">
            @foreach($setoresRisco as $i => $s)
            @php $cores=['var(--danger)','var(--warning)','var(--brand)','var(--success)','var(--text-3)']; $max=$setoresRisco->first()->total_riscos?:1; $pct=round(($s->total_riscos/$max)*100); @endphp
            <div class="setor-row">
                <div style="width:24px;height:24px;border-radius:50%;background:{{ $cores[$i]??'var(--text-3)' }}1a;color:{{ $cores[$i]??'var(--text-3)' }};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0">{{ $i+1 }}</div>
                <div style="flex:1;min-width:0">
                    <div class="setor-nome">{{ $s->nome }}</div>
                    <div class="prog-bar"><div class="prog-fill" style="width:{{ $pct }}%;background:{{ $cores[$i]??'var(--text-3)' }}"></div></div>
                </div>
                <div style="font-size:13px;font-weight:700;color:{{ $cores[$i]??'var(--text-3)' }};flex-shrink:0">{{ $s->total_riscos }} riscos</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-user-plus"></i> Admissões Recentes (60d)</div>
            <a href="{{ route('colaboradores.index') }}" class="btn btn-ghost btn-sm">Ver todos</a>
        </div>
        <div style="padding:0 16px 8px;max-height:280px;overflow-y:auto">
            @forelse($admissoesLista as $col)
            <div class="tl-item">
                <div class="tl-dot" style="background:var(--success)"></div>
                <div class="tl-content">
                    <div class="tl-name">{{ $col->nome }}</div>
                    <div class="tl-date">{{ $col->funcao?->nome ?? 'Sem função' }} · {{ $col->setor?->nome ?? 'Sem setor' }} · <strong>{{ $col->data_admissao?->format('d/m/Y') }}</strong></div>
                </div>
            </div>
            @empty
            <p style="color:var(--text-3);font-size:13px;padding:12px 0">Nenhuma admissão nos últimos 60 dias.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- PRÓXIMOS ASOs ──────────────────────────────────────────────────────────── --}}
<div class="card mb-16">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-clock"></i> ASOs — Vencidos e Próximos ao Vencimento</div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('asos.vencidos') }}" class="btn btn-secondary btn-sm">Vencidos</a>
            <a href="{{ route('asos.a-vencer') }}" class="btn btn-secondary btn-sm">A Vencer</a>
        </div>
    </div>
    @if($proximosAsos->isEmpty())
    <div class="empty-state py-32">
        <i class="fas fa-check-circle" style="font-size:36px;color:var(--success)"></i>
        <p style="margin-top:8px;color:var(--text-3)">Nenhum ASO vencendo nos próximos 30 dias</p>
    </div>
    @else
    <div class="table-wrap" style="max-height:360px;overflow-y:auto">
        <table class="table">
            <thead><tr><th>COLABORADOR</th><th>FUNÇÃO / SETOR</th><th>VENCIMENTO</th><th>SITUAÇÃO</th><th></th></tr></thead>
            <tbody>
            @foreach($proximosAsos as $a)
            @php $dias = $a->dias_restantes; @endphp
            <tr class="{{ $dias !== null && $dias < 0 ? 'tr-danger' : ($dias !== null && $dias <= 15 ? 'tr-warning' : '') }}">
                <td>
                    <div class="font-bold text-13">{{ $a->colaborador?->nome ?? '—' }}</div>
                    <div class="text-11 text-muted">{{ $a->colaborador?->empresa?->nome_display ?? '—' }}</div>
                </td>
                <td class="text-12">{{ $a->colaborador?->funcao?->nome ?? '—' }}<br><span class="text-muted">{{ $a->colaborador?->setor?->nome ?? '—' }}</span></td>
                <td class="font-mono text-12">{{ $a->data_vencimento?->format('d/m/Y') ?? '—' }}</td>
                <td>
                    @if($dias !== null && $dias < 0)
                        <span class="badge badge-danger">Vencido há {{ abs($dias) }}d</span>
                    @elseif($dias !== null && $dias <= 15)
                        <span class="badge badge-warning">Urgente — {{ $dias }}d</span>
                    @elseif($dias !== null)
                        <span class="badge badge-info">{{ $dias }} dias</span>
                    @else
                        <span class="badge badge-secondary">Sem data</span>
                    @endif
                </td>
                <td><a href="{{ route('asos.edit', $a->id) }}" class="btn btn-secondary btn-sm">Atualizar</a></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- GRÁFICO RISCOS + ASOs ─────────────────────────────────────────────────── --}}
<div class="dg dg-2 mb-16">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Distribuição de Riscos por Tipo</div></div>
        <div class="chart-box"><canvas id="chartRiscos"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> ASOs — Válidos x Vencidos x A Vencer</div></div>
        <div class="chart-box"><canvas id="chartAsos"></canvas></div>
    </div>
</div>

</div>{{-- /tab-sst --}}

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- TAB DADOS DA EMPRESA                                                   --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="tab-empresa" class="dash-tab-content" style="display:none">

{{-- FILTRO (superadmin) ──────────────────────────────────────────────────── --}}
@if(auth()->user()->isSuperAdmin())
<div class="card mb-16" style="padding:16px">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <i class="fas fa-filter" style="color:var(--text-3)"></i>
        <span style="font-size:13px;font-weight:600;color:var(--text-2)">Filtrar por empresa:</span>
        <form method="GET" action="{{ route('dashboard') }}" style="display:flex;gap:6px;align-items:center">
        <input type="hidden" name="_tab" value="empresa">
        <select name="empresa_id" class="filter-select" style="width:260px" id="empFilterEmpresa" onchange="this.form.submit()">
            <option value="">— Todas as empresas —</option>
            @foreach($empresas as $e)
            <option value="{{ $e->id }}" {{ $eid == $e->id ? 'selected' : '' }}>{{ $e->nome_display }}</option>
            @endforeach
        </select>
        </form>
    </div>
</div>
@endif

{{-- KPIs EMPRESA ─────────────────────────────────────────────────────────── --}}
<div class="dg dg-6 mb-16">
    <div class="kpi2 k-blue" style="grid-column:span 1">
        <div class="kpi2-icon"><i class="fas fa-users"></i></div>
        <div class="kpi2-label">Total Colaboradores</div>
        <div class="kpi2-val">{{ $totalAtivos }}</div>
        <div class="kpi2-sub">Status: Contratado</div>
    </div>
    <div class="kpi2 k-cyan">
        <div class="kpi2-icon"><i class="fas fa-mars"></i></div>
        <div class="kpi2-label">Homens</div>
        <div class="kpi2-val">{{ $homens }}</div>
        <div class="kpi2-sub">{{ $totalAtivos > 0 ? round($homens/$totalAtivos*100) : 0 }}% do total</div>
    </div>
    <div class="kpi2 k-pink">
        <div class="kpi2-icon"><i class="fas fa-venus"></i></div>
        <div class="kpi2-label">Mulheres</div>
        <div class="kpi2-val">{{ $mulheres }}</div>
        <div class="kpi2-sub">{{ $totalAtivos > 0 ? round($mulheres/$totalAtivos*100) : 0 }}% do total</div>
    </div>
    <div class="kpi2 k-purple">
        <div class="kpi2-icon"><i class="fas fa-birthday-cake"></i></div>
        <div class="kpi2-label">Média de Idade</div>
        <div class="kpi2-val">{{ $mediaIdade ?? '—' }}</div>
        <div class="kpi2-sub">anos</div>
    </div>
    <div class="kpi2 k-green">
        <div class="kpi2-icon"><i class="fas fa-seedling"></i></div>
        <div class="kpi2-label">Mais Jovem</div>
        <div class="kpi2-val" style="font-size:20px">{{ $maisJovem ? $maisJovem->data_nascimento->age.'a' : '—' }}</div>
        <div class="kpi2-sub" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $maisJovem?->nome ?? '—' }}</div>
    </div>
    <div class="kpi2 k-yellow">
        <div class="kpi2-icon"><i class="fas fa-medal"></i></div>
        <div class="kpi2-label">Mais Velho</div>
        <div class="kpi2-val" style="font-size:20px">{{ $maisVelho ? $maisVelho->data_nascimento->age.'a' : '—' }}</div>
        <div class="kpi2-sub" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $maisVelho?->nome ?? '—' }}</div>
    </div>
</div>

{{-- SEXO BAR ─────────────────────────────────────────────────────────────── --}}
@php $pctM = $totalAtivos > 0 ? round($homens/$totalAtivos*100) : 0; $pctF = 100 - $pctM; @endphp
<div class="card mb-16" style="padding:16px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <div style="display:flex;align-items:center;gap:8px"><span style="width:10px;height:10px;border-radius:50%;background:var(--brand);display:inline-block"></span><span style="font-size:12px;font-weight:600;color:var(--text-2)">Homens {{ $pctM }}%</span></div>
        <div style="font-size:11px;color:var(--text-3)">Distribuição por Sexo</div>
        <div style="display:flex;align-items:center;gap:8px"><span style="font-size:12px;font-weight:600;color:var(--text-2)">Mulheres {{ $pctF }}%</span><span style="width:10px;height:10px;border-radius:50%;background:#db2777;display:inline-block"></span></div>
    </div>
    <div class="sexo-bar">
        <div class="sexo-bar-m" style="width:{{ $pctM }}%"></div>
        <div class="sexo-bar-f"></div>
    </div>
</div>

{{-- GRÁFICOS EMPRESA ─────────────────────────────────────────────────────── --}}
<div class="dg dg-2 mb-16">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Distribuição por Sexo</div></div>
        <div class="chart-box"><canvas id="chartSexo"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Faixa Etária</div></div>
        <div class="chart-box"><canvas id="chartIdade"></canvas></div>
    </div>
</div>

<div class="dg dg-2 mb-16">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-layer-group"></i> Distribuição por Setor</div></div>
        <div class="chart-box"><canvas id="chartSetorEmp"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-hourglass-half"></i> Tempo de Empresa</div></div>
        <div class="chart-box"><canvas id="chartTempo"></canvas></div>
    </div>
</div>

{{-- TABELA SETORES ───────────────────────────────────────────────────────── --}}
<div class="card mb-16">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-table"></i> Colaboradores por Setor</div>
        <a href="{{ route('setores.index') }}" class="btn btn-ghost btn-sm">Gerenciar Setores</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>SETOR</th><th>COLABORADORES</th><th>% DO TOTAL</th><th>PARTICIPAÇÃO</th></tr></thead>
            <tbody>
            @forelse($porSetor as $i => $s)
            @php $pctS = $totalAtivos > 0 ? round(($s->total / $totalAtivos) * 100) : 0; @endphp
            <tr>
                <td class="text-muted text-12">{{ $i+1 }}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="setor-avatar">{{ strtoupper(substr($s->nome,0,2)) }}</div>
                        <span class="font-bold text-13">{{ $s->nome }}</span>
                    </div>
                </td>
                <td><span style="font-size:20px;font-weight:800;color:var(--brand)">{{ $s->total }}</span></td>
                <td class="font-bold text-13">{{ $pctS }}%</td>
                <td style="min-width:160px"><div class="prog-bar"><div class="prog-fill" style="width:{{ $pctS }}%;background:var(--brand)"></div></div></td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="empty-state py-16"><p>Nenhum setor cadastrado</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>{{-- /tab-empresa --}}

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const COLORS = ['#2563eb','#16a34a','#d97706','#dc2626','#0891b2','#7c3aed','#db2777','#ea580c','#0d9488','#ca8a04'];
const gOpts  = {color:'rgba(255,255,255,.05)'};
const tOpts  = {color:'#94a3b8',font:{size:11}};
const ttBg   = 'rgba(15,23,42,.95)';
const charts = {};

function mkChart(id, type, data, opts={}) {
    if (charts[id]) charts[id].destroy();
    const c = document.getElementById(id);
    if (!c) return;
    charts[id] = new Chart(c, {
        type, data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color:'#94a3b8', font:{size:11} } },
                tooltip: { backgroundColor:ttBg, titleColor:'#f1f5f9', bodyColor:'#94a3b8' },
                ...(opts.plugins||{})
            },
            ...opts
        }
    });
}

// ── SST CHARTS ──────────────────────────────────────────────────────────────
const tendencia = @json($tendencia);
mkChart('chartTrend','line',{
    labels: tendencia.map(x=>x.mes),
    datasets:[
        {label:'ASOs criados', data:tendencia.map(x=>x.asos), borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,.12)', fill:true, tension:.4, pointRadius:4},
        {label:'Admissões',    data:tendencia.map(x=>x.admissoes), borderColor:'#16a34a', backgroundColor:'rgba(22,163,74,.12)', fill:true, tension:.4, pointRadius:4},
    ]
},{scales:{x:{ticks:tOpts,grid:gOpts},y:{ticks:tOpts,grid:gOpts,beginAtZero:true}},plugins:{legend:{position:'top'}}});

const porSetor = @json($porSetor);
if (porSetor.length) {
    mkChart('chartSetor','doughnut',{
        labels: porSetor.map(x=>x.nome),
        datasets:[{data:porSetor.map(x=>x.total), backgroundColor:COLORS, borderWidth:2, borderColor:'rgba(0,0,0,.2)'}]
    },{cutout:'55%',plugins:{legend:{position:'right'}}});
}

const riscos = @json($riscosPorTipo);
const tiposLabels = {fisico:'Físico',quimico:'Químico',biologico:'Biológico',ergonomico:'Ergonômico',acidente:'Acidente'};
const tiposCores  = {fisico:'#0891b2',quimico:'#7c3aed',biologico:'#16a34a',ergonomico:'#d97706',acidente:'#dc2626'};
const rL = Object.keys(tiposLabels);
mkChart('chartRiscos','bar',{
    labels: rL.map(k=>tiposLabels[k]),
    datasets:[{label:'Qtd', data:rL.map(k=>riscos[k]?.total||0), backgroundColor:rL.map(k=>tiposCores[k]+'99'), borderColor:rL.map(k=>tiposCores[k]), borderWidth:2, borderRadius:6}]
},{scales:{x:{ticks:tOpts,grid:gOpts},y:{ticks:tOpts,grid:gOpts,beginAtZero:true}},plugins:{legend:{display:false}}});

mkChart('chartAsos','bar',{
    labels:['Válidos','Vencidos','A Vencer (30d)','Sem ASO'],
    datasets:[{data:[{{ $asoValidos }},{{ $asoVencidos }},{{ $asoAVencer }},{{ $semAso }}],
        backgroundColor:['rgba(22,163,74,.7)','rgba(220,38,38,.7)','rgba(217,119,6,.7)','rgba(100,116,139,.5)'],
        borderColor:['#16a34a','#dc2626','#d97706','#64748b'],
        borderWidth:2, borderRadius:6}]
},{scales:{x:{ticks:tOpts,grid:gOpts},y:{ticks:tOpts,grid:gOpts,beginAtZero:true}},plugins:{legend:{display:false}}});

// ── EMPRESA CHARTS ──────────────────────────────────────────────────────────
mkChart('chartSexo','doughnut',{
    labels:['Homens','Mulheres'],
    datasets:[{data:[{{ $homens }},{{ $mulheres }}], backgroundColor:['#2563eb','#db2777'], borderWidth:3, borderColor:'rgba(0,0,0,.15)'}]
},{cutout:'62%',plugins:{legend:{position:'bottom'}}});

const faixaEtaria = @json($faixaEtaria);
mkChart('chartIdade','bar',{
    labels: Object.keys(faixaEtaria),
    datasets:[{label:'Colaboradores', data:Object.values(faixaEtaria),
        backgroundColor:['rgba(37,99,235,.75)','rgba(8,145,178,.75)','rgba(22,163,74,.75)','rgba(217,119,6,.75)','rgba(220,38,38,.75)'],
        borderColor:['#2563eb','#0891b2','#16a34a','#d97706','#dc2626'],
        borderWidth:2, borderRadius:6}]
},{scales:{x:{ticks:tOpts,grid:gOpts},y:{ticks:tOpts,grid:gOpts,beginAtZero:true,precision:0}},plugins:{legend:{display:false}}});

if (porSetor.length) {
    mkChart('chartSetorEmp','bar',{
        labels: porSetor.map(x=>x.nome),
        datasets:[{label:'Colaboradores', data:porSetor.map(x=>x.total),
            backgroundColor:COLORS.map(c=>c+'bb'), borderColor:COLORS, borderWidth:2, borderRadius:6}]
    },{indexAxis:'y',scales:{x:{ticks:tOpts,grid:gOpts,beginAtZero:true},y:{ticks:{...tOpts,font:{size:10}},grid:gOpts}},plugins:{legend:{display:false}}});
}

const tempoEmpresa = @json($tempoEmpresa);
mkChart('chartTempo','bar',{
    labels: Object.keys(tempoEmpresa),
    datasets:[{label:'Colaboradores', data:Object.values(tempoEmpresa),
        backgroundColor:['rgba(8,145,178,.75)','rgba(37,99,235,.75)','rgba(124,58,237,.75)','rgba(22,163,74,.75)'],
        borderColor:['#0891b2','#2563eb','#7c3aed','#16a34a'],
        borderWidth:2, borderRadius:6}]
},{scales:{x:{ticks:tOpts,grid:gOpts},y:{ticks:tOpts,grid:gOpts,beginAtZero:true,precision:0}},plugins:{legend:{display:false}}});

// ── TABS ────────────────────────────────────────────────────────────────────
function activateTab(tabName) {
    document.querySelectorAll('.dash-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.dash-tab-content').forEach(c => c.style.display='none');
    const btn = document.querySelector(`.dash-tab[data-tab="${tabName}"]`);
    if (btn) btn.classList.add('active');
    const content = document.getElementById('tab-'+tabName);
    if (content) content.style.display='block';
    Object.values(charts).forEach(ch => ch.resize());
}
document.querySelectorAll('.dash-tab').forEach(btn => {
    btn.addEventListener('click', () => activateTab(btn.dataset.tab));
});
// Restaura a tab ativa após filtro
activateTab('{{ $activeTab }}');
</script>
@endpush
