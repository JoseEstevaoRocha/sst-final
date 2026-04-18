@extends('layouts.app')
@section('title','Alertas e Pendências')
@section('content')
<div class="page-header"><div><h1 class="page-title">Alertas e Pendências</h1><p class="page-sub">ASOs com vencimento ultrapassado</p></div><a href="{{ route('dashboard') }}" class="btn btn-secondary">← Dashboard</a></div>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>COLABORADOR</th><th>EMPRESA</th><th>TIPO ASO</th><th>VENCIMENTO</th><th>DIAS VENCIDO</th><th>AÇÕES</th></tr></thead>
<tbody>
@forelse($alertas as $a)
@php $dias = today()->diffInDays($a->data_vencimento); @endphp
<tr class="tr-danger">
    <td><div class="font-bold text-13">{{ $a->colaborador->nome??'—' }}</div><div class="text-11 text-muted">{{ $a->colaborador->funcao->nome??'' }}</div></td>
    <td class="text-12">{{ $a->colaborador->empresa->nome_display??'—' }}</td>
    <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$a->tipo)) }}</span></td>
    <td class="font-mono text-12 text-danger">{{ $a->data_vencimento?->format('d/m/Y')??'—' }}</td>
    <td><span class="badge badge-danger">{{ $dias }}d vencido</span></td>
    <td><a href="{{ route('asos.edit',$a->id) }}" class="btn btn-secondary btn-sm">Atualizar</a></td>
</tr>
@empty
<tr><td colspan="6"><div class="empty-state"><div class="empty-icon"><i class="fas fa-check-circle" style="color:var(--success)"></i></div><h3 style="color:var(--success)">Nenhum alerta ativo!</h3><p>Todos os ASOs estão em dia.</p></div></td></tr>
@endforelse
</tbody></table></div></div>
@endsection
