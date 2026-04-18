@extends('layouts.app')
@section('title','Ficha do Funcionário')
@section('content')
<div class="page-header"><div><h1 class="page-title">Ficha do Funcionário</h1><p class="page-sub">EPI, Uniformes, ASO e histórico por colaborador</p></div></div>
<div class="card mb-20">
    <form method="GET">
        <div class="flex gap-12 flex-wrap align-center">
            <div class="filter-search-wrap flex-1" style="min-width:220px"><i class="fas fa-search"></i><input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome, CPF ou matrícula..." class="filter-input filter-input-icon" style="width:100%"></div>
            <select name="empresa_id" id="empresa_id" class="filter-select" style="width:220px"><option value="">Todas as empresas</option>@foreach($empresas as $e)<option value="{{ $e->id }}" {{ request('empresa_id')==$e->id?'selected':'' }}>{{ $e->nome_display }}</option>@endforeach</select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
        </div>
    </form>
</div>
@if($colaboradores->isNotEmpty())
<div class="flex flex-col gap-8">
    @foreach($colaboradores as $c)
    <a href="{{ route('ficha.show',$c->id) }}" style="text-decoration:none">
        <div class="card" style="padding:14px 18px;cursor:pointer;transition:all .15s" onmouseover="this.style.borderColor='var(--brand)'" onmouseout="this.style.borderColor='var(--border)'">
            <div class="flex align-center gap-14">
                <div class="avatar-md">{{ $c->initials }}</div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold">{{ $c->nome }}</div>
                    <div class="text-12 text-muted">{{ $c->funcao->nome??'' }} · {{ $c->setor->nome??'' }} · {{ $c->empresa->nome_display??'' }}</div>
                </div>
                <span class="badge {{ $c->status==='Contratado'?'badge-success':'badge-danger' }}">{{ $c->status }}</span>
                <i class="fas fa-chevron-right text-muted"></i>
            </div>
        </div>
    </a>
    @endforeach
</div>
@elseif(request()->hasAny(['search','empresa_id']))
<div class="empty-state"><div class="empty-icon"><i class="fas fa-user-slash"></i></div><h3>Nenhum colaborador encontrado</h3><p>Tente ajustar os filtros.</p></div>
@else
<div class="empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><h3>Digite um nome ou selecione uma empresa</h3></div>
@endif
@endsection
