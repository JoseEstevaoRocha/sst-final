<!DOCTYPE html>
<html lang="pt-BR" data-theme="{{ Cookie::get('theme','dark') }}" id="html-root">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Dashboard') — {{ \App\Http\Controllers\ConfigController::get('system_name','SST Manager') }}</title>
    @php $faviconLogo = \App\Http\Controllers\ConfigController::get('system_logo'); @endphp
    @if($faviconLogo && \Storage::disk('public')->exists($faviconLogo))
    <link rel="icon" type="image/png" href="{{ Storage::url($faviconLogo) }}">
    @else
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡️</text></svg>">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>

{{-- TOPBAR --}}
<header class="topbar">
    <div class="topbar-left">
        <button class="topbar-mobile-btn" id="mobileToggle"><i class="fas fa-bars"></i></button>
        @php $topbarLogo = \App\Http\Controllers\ConfigController::get('system_logo'); $topbarName = \App\Http\Controllers\ConfigController::get('system_name','SST Manager'); @endphp
        <a href="{{ route('dashboard') }}" class="topbar-brand">
            @if($topbarLogo && \Storage::disk('public')->exists($topbarLogo))
                <img src="{{ Storage::url($topbarLogo) }}" alt="{{ $topbarName }}" class="topbar-logo-img">
            @else
                <i class="fas fa-shield-alt topbar-logo-icon"></i>
            @endif
            <span class="topbar-brand-name">{{ $topbarName }}</span>
        </a>
        <div class="topbar-search">
            <i class="fas fa-search topbar-search-icon"></i>
            <input type="text" id="globalSearch" placeholder="Buscar colaborador..." autocomplete="off">
            <div class="search-dropdown" id="searchDropdown"></div>
        </div>
    </div>
    <div class="topbar-right">
        {{-- Notifications --}}
        <div class="notif-wrap" id="notifWrap">
            <button class="topbar-icon-btn" id="notifBtn" title="Alertas">
                <i class="fas fa-bell"></i>
                <span class="notif-badge" id="notifBadge" style="display:none">0</span>
            </button>
            <div class="notif-panel" id="notifPanel">
                <div class="notif-panel-header"><span>Alertas do Sistema</span><a href="{{ route('dashboard.alertas') }}" class="notif-see-all">Ver todos</a></div>
                <div id="notifList"><div class="notif-loading">Carregando...</div></div>
            </div>
        </div>

        {{-- Theme --}}
        <button class="topbar-icon-btn" id="themeToggle" title="Alternar tema">
            <i class="fas fa-moon theme-moon"></i>
            <i class="fas fa-sun theme-sun" style="display:none"></i>
        </button>

        {{-- User --}}
        <div class="user-wrap" id="userWrap">
            <button class="user-trigger" id="userTrigger">
                <div class="user-info text-right">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ auth()->user()->cargo ?? ucfirst(auth()->user()->getRoleNames()->first() ?? '') }}</span>
                </div>
                <div class="user-avatar">{{ auth()->user()->initials }}</div>
            </button>
            <div class="user-dropdown" id="userDropdown">
                <div class="user-dropdown-head">
                    <div class="user-avatar lg">{{ auth()->user()->initials }}</div>
                    <div>
                        <div class="font-bold">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-muted">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="{{ route('config.index') }}" class="dropdown-item"><i class="fas fa-sliders-h"></i> Configurações</a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> Sair</button>
                </form>
            </div>
        </div>
    </div>
</header>

{{-- LAYOUT --}}
<div class="layout" id="layout">
    @include('layouts.sidebar')
    <main class="main-area">
        <div class="page-content">

        {{-- Flash --}}
        @if(session('success'))
        <div class="flash flash-success" id="flash"><i class="fas fa-check-circle"></i> {{ session('success') }}<button onclick="this.parentElement.remove()" class="flash-close">×</button></div>
        @endif
        @if(session('error'))
        <div class="flash flash-error" id="flash"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}<button onclick="this.parentElement.remove()" class="flash-close">×</button></div>
        @endif
        @if($errors->any())
        <div class="flash flash-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}<button onclick="this.parentElement.remove()" class="flash-close">×</button></div>
        @endif

        @yield('content')
        </div>
    </main>
</div>

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
