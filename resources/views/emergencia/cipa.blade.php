@extends('layouts.app')
@section('title','CIPA — NR05')
@section('content')
<div class="page-header"><div><h1 class="page-title">CIPA — Comissão Interna de Prevenção de Acidentes</h1><p class="page-sub">{{ $membros->count() }} membros ativos</p></div><button class="btn btn-primary" onclick="openModal('modalCipa')"><i class="fas fa-plus"></i> Adicionar Membro</button></div>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>COLABORADOR</th><th>CARGO</th><th>TIPO</th><th>MANDATO INÍCIO</th><th>MANDATO FIM</th><th>STATUS</th><th>AÇÕES</th></tr></thead>
<tbody>
@forelse($membros as $m)
<tr>
    <td><div class="font-bold text-13">{{ $m->colaborador->nome??'—' }}</div></td>
    <td class="text-12">{{ $m->cargo??'—' }}</td>
    <td><span class="badge {{ $m->tipo==='eleito'?'badge-success':'badge-info' }}">{{ ucfirst($m->tipo??'—') }}</span></td>
    <td class="font-mono text-12">{{ $m->mandato_inicio?->format('d/m/Y')??'—' }}</td>
    <td class="font-mono text-12 {{ $m->mandato_fim?->isPast()?'text-danger':'' }}">{{ $m->mandato_fim?->format('d/m/Y')??'—' }}</td>
    <td><span class="badge {{ $m->ativo?'badge-success':'badge-danger' }}">{{ $m->ativo?'Ativo':'Inativo' }}</span></td>
    <td><form method="POST" action="{{ route('cipa.destroy',$m->id) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Remover?"><i class="fas fa-trash-alt"></i></button></form></td>
</tr>
@empty
<tr><td colspan="7"><div class="empty-state"><div class="empty-icon"><i class="fas fa-users-cog"></i></div><h3>Nenhum membro CIPA</h3></div></td></tr>
@endforelse
</tbody></table></div></div>
<div class="modal-overlay" id="modalCipa"><div class="modal modal-md">
<div class="modal-header"><div class="modal-title"><i class="fas fa-users-cog"></i> Adicionar Membro CIPA</div><button class="modal-close" onclick="closeModal('modalCipa')"><i class="fas fa-times"></i></button></div>
<div class="modal-body"><form method="POST" action="{{ route('cipa.store') }}">@csrf
<div class="form-grid">
    <div class="form-group form-full"><label class="form-label">Colaborador *</label><select name="colaborador_id" class="form-select" required><option value="">Selecione</option>@foreach($colaboradores as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Cargo</label><select name="cargo" class="form-select"><option value="">Selecione</option>@foreach(['Presidente','Vice-Presidente','Secretário','Membro Efetivo','Membro Suplente'] as $cg)<option value="{{ $cg }}">{{ $cg }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Tipo</label><select name="tipo" class="form-select"><option value="eleito">Eleito</option><option value="indicado">Indicado</option></select></div>
    <div class="form-group"><label class="form-label">Mandato Início</label><input type="date" name="mandato_inicio" class="form-control"></div>
    <div class="form-group"><label class="form-label">Mandato Fim</label><input type="date" name="mandato_fim" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalCipa')">Cancelar</button><button type="submit" class="btn btn-primary">Adicionar</button></div>
</form></div></div></div>
@endsection
