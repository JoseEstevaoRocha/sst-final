@extends('layouts.app')
@section('title','Empresas')
@section('content')
<div class="page-header"><div><h1 class="page-title">Empresas</h1><p class="page-sub">{{ $empresas->total() }} cadastradas</p></div><a href="{{ route('empresas.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Nova Empresa</a></div>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>EMPRESA</th><th>CNPJ</th><th>CIDADE</th><th>CONTATO</th><th>STATUS</th><th>AÇÕES</th></tr></thead>
<tbody>
@forelse($empresas as $e)
<tr>
    <td><div class="font-bold text-13">{{ $e->razao_social }}</div>@if($e->nome_fantasia)<div class="text-11 text-muted">{{ $e->nome_fantasia }}</div>@endif</td>
    <td class="font-mono text-12">{{ $e->cnpj }}</td>
    <td class="text-12">{{ $e->cidade }}@if($e->estado) — {{ $e->estado }}@endif</td>
    <td class="text-12">{{ $e->email??$e->telefone??'—' }}</td>
    <td><span class="badge {{ $e->status==='ativa'?'badge-success':'badge-danger' }}">{{ ucfirst($e->status) }}</span></td>
    <td><div class="flex gap-4"><a href="{{ route('empresas.edit',$e->id) }}" class="btn btn-secondary btn-icon"><i class="fas fa-pencil-alt"></i></a><form method="POST" action="{{ route('empresas.destroy',$e->id) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir {{ $e->razao_social }}?"><i class="fas fa-trash-alt"></i></button></form></div></td>
</tr>
@empty
<tr><td colspan="6"><div class="empty-state"><div class="empty-icon"><i class="fas fa-building"></i></div><h3>Nenhuma empresa</h3></div></td></tr>
@endforelse
</tbody></table></div></div>
@endsection
