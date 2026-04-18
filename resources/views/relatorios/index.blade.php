@extends('layouts.app')
@section('title','Relatórios')
@section('content')
<div class="page-header"><div><h1 class="page-title">Central de Relatórios</h1></div></div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px">
    @foreach([['ASOs','asos','fas fa-clipboard-list','badge-warning'],['EPIs','epis','fas fa-hard-hat','badge-blue'],['Uniformes','uniformes','fas fa-tshirt','badge-purple'],['Extintores','extintores','fas fa-fire-extinguisher','badge-danger'],['Máquinas','maquinas','fas fa-cogs','badge-secondary']] as [$label,$route,$icon,$badge])
    <a href="{{ route('relatorios.'.$route) }}" style="text-decoration:none">
        <div class="card" style="text-align:center;padding:28px;cursor:pointer;transition:all .15s" onmouseover="this.style.borderColor='var(--brand)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--border)';this.style.transform='none'">
            <i class="{{ $icon }}" style="font-size:32px;color:var(--brand);margin-bottom:12px;display:block"></i>
            <div class="font-bold">Relatório {{ $label }}</div>
            <div class="text-11 text-muted mt-8">Visualizar e exportar</div>
        </div>
    </a>
    @endforeach
</div>
@endsection
