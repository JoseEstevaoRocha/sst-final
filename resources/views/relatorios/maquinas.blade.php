@extends('layouts.app')
@section('title','Relatório')
@section('content')
<div class="page-header"><div><h1 class="page-title">Relatório</h1></div><div class="flex gap-8"><a href="{{ route('relatorios.index') }}" class="btn btn-secondary">← Voltar</a><a href="{{ route('relatorios.export',request()->segment(3)??'relatorio') }}" class="btn btn-primary"><i class="fas fa-download"></i> Exportar CSV</a></div></div>
<div class="card"><div class="empty-state py-32"><i class="fas fa-chart-bar" style="font-size:40px;color:var(--brand);opacity:.4;display:block;margin-bottom:14px"></i><h3>Relatório em construção</h3><p>Use o botão "Exportar CSV" para baixar os dados disponíveis.</p></div></div>
@endsection
