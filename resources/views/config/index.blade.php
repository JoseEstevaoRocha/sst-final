@extends('layouts.app')
@section('title','Configurações')
@section('content')
<div class="page-header"><div><h1 class="page-title">Configurações do Sistema</h1></div></div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:800px">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-palette"></i> Identidade Visual</div></div>
        <form method="POST" action="{{ route('config.save') }}">@csrf
            <div class="flex flex-col gap-16">
                <div class="form-group"><label class="form-label">Nome do Sistema</label><input type="text" name="system_name" value="{{ $configs['system_name']??'SST Manager' }}" class="form-control" required></div>
                <div style="padding:10px;background:var(--bg-secondary);border-radius:var(--r-sm);font-size:12px;color:var(--text-muted)">💡 Aparece na barra lateral e no título do browser.</div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar nome</button>
            </div>
        </form>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-image"></i> Logo</div></div>
        @if($configs['system_logo']??false)<div style="margin-bottom:14px"><img src="{{ Storage::url($configs['system_logo']) }}" style="height:50px;border-radius:8px"></div>@else<div style="width:80px;height:80px;background:var(--bg-secondary);border:2px dashed var(--border);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:14px">🛡️</div>@endif
        <form method="POST" action="{{ route('config.logo') }}" enctype="multipart/form-data">@csrf
            <div class="flex flex-col gap-12">
                <input type="file" name="logo" class="form-control" accept="image/*" required>
                <div class="text-11 text-muted">PNG, JPG, SVG · máx. 2MB</div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Enviar logo</button>
            </div>
        </form>
    </div>
</div>
<div class="card mt-16" style="max-width:800px">
    <div class="card-header"><div class="card-title"><i class="fas fa-info-circle"></i> Informações do Sistema</div></div>
    <div class="flex flex-wrap gap-20 text-13">
        <div><span class="text-muted">Versão:</span> <strong>2.0.0</strong></div>
        <div><span class="text-muted">Framework:</span> <strong>Laravel 11</strong></div>
        <div><span class="text-muted">PHP:</span> <strong>{{ phpversion() }}</strong></div>
        <div><span class="text-muted">Banco:</span> <strong>PostgreSQL</strong></div>
    </div>
</div>
@endsection
