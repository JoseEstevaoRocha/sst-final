<!DOCTYPE html>
<html lang="pt-BR" data-theme="{{ Cookie::get('theme','dark') }}" id="html-root">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Login — {{ \App\Http\Controllers\ConfigController::get('system_name','SST Manager') }}</title>
    @php $faviconLogo = \App\Http\Controllers\ConfigController::get('system_logo'); @endphp
    @if($faviconLogo && \Storage::disk('public')->exists($faviconLogo))
    <link rel="icon" type="image/png" href="{{ Storage::url($faviconLogo) }}">
    @else
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡️</text></svg>">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login-page">
    {{-- Left --}}
    <div class="login-left">
        <div class="login-bg-grid"></div>
        <div class="login-glow"></div>
        @php $logo=\App\Http\Controllers\ConfigController::get('system_logo'); $sysName=\App\Http\Controllers\ConfigController::get('system_name','SST Manager'); @endphp
        <div class="login-brand">
            @if($logo && \Storage::disk('public')->exists($logo))
                <img src="{{ Storage::url($logo) }}" style="height:44px;object-fit:contain">
            @else
                <div class="login-brand-icon"><i class="fas fa-shield-alt"></i></div>
            @endif
            <div>
                <div class="login-brand-name">{{ $sysName }}</div>
                <div class="login-brand-sub">SAFETY COMPLIANCE PLATFORM</div>
            </div>
        </div>
        <h1 class="login-headline">Gestão Completa<br>de <span style="color:var(--brand)">SST</span></h1>
        <p class="login-tagline">Plataforma integrada de Saúde e Segurança do Trabalho para empresas modernas</p>
        <div class="login-features">
            @foreach([['fas fa-heartbeat','Saúde Ocupacional & ASO'],['fas fa-hard-hat','EPI & Uniformes'],['fas fa-fire-extinguisher','Emergência & CIPA'],['fas fa-cogs','Máquinas NR12'],['fas fa-shield-alt','GRO & Matriz de Risco'],['fas fa-file-contract','Documentos SST'],['fab fa-whatsapp','WhatsApp API'],['fas fa-chart-bar','Dashboard BI']] as [$icon,$label])
            <div class="login-feature"><i class="{{ $icon }}"></i><span>{{ $label }}</span></div>
            @endforeach
        </div>
    </div>

    {{-- Right --}}
    <div class="login-right">
        <div class="login-card">
            <h1 class="login-title">Entrar</h1>
            <p class="login-sub">Acesse sua conta para continuar</p>

            @if($errors->any())
            <div class="login-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="login-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control pl-icon" placeholder="seu@email.com" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Senha</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="pw" class="form-control pl-icon" placeholder="••••••••" required>
                        <button type="button" onclick="togglePw()" class="pw-toggle"><i class="fas fa-eye" id="pwIcon"></i></button>
                    </div>
                </div>
                <label class="checkbox-wrap mb-16">
                    <input type="checkbox" name="remember">
                    <span class="checkbox-box"></span>
                    <span>Manter conectado</span>
                </label>
                <button type="submit" class="btn btn-primary btn-full btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Entrar no sistema
                </button>
            </form>
            <div class="login-demo">🔑 Demo: <strong>admin@sst.com</strong> / <strong>password</strong></div>
            <div style="text-align:center;margin-top:16px">
                <button onclick="toggleTheme()" class="btn btn-ghost btn-sm"><i class="fas fa-adjust"></i> Alternar tema</button>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/app.js') }}"></script>
<script>
function togglePw(){const i=document.getElementById('pw'),e=document.getElementById('pwIcon');i.type=i.type==='password'?'text':'password';e.className=i.type==='password'?'fas fa-eye':'fas fa-eye-slash';}
function toggleTheme(){const h=document.getElementById('html-root'),n=h.getAttribute('data-theme')==='dark'?'light':'dark';h.setAttribute('data-theme',n);document.cookie=`theme=${n};path=/;max-age=${365*86400}`;}
</script>
</body>
</html>
