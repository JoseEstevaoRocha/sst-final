@extends('layouts.app')
@section('title','GHE — Grupos Homogêneos de Exposição')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">GHE & Gestão de Riscos</h1></div>
    <div class="flex gap-8">
        <button class="btn btn-secondary" onclick="openModal('modalRisco')"><i class="fas fa-plus"></i> Novo Risco</button>
        <button class="btn btn-primary" onclick="openModal('modalGhe')"><i class="fas fa-plus"></i> Novo GHE</button>
    </div>
</div>
<div class="charts-grid mb-20">
<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-object-group"></i> GHEs ({{ $ghes->count() }})</div></div>
    @forelse($ghes as $g)
    <div style="padding:12px;background:var(--bg-secondary);border-radius:var(--r-sm);margin-bottom:8px">
        <div class="flex justify-between align-center">
            <div><div class="font-bold text-13">{{ $g->codigo }} — {{ $g->nome }}</div><div class="text-11 text-muted">{{ $g->empresa->nome_display??'' }}</div></div>
            <form method="POST" action="{{ route('ghes.destroy',$g->id) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir GHE?"><i class="fas fa-trash-alt"></i></button></form>
        </div>
    </div>
    @empty
    <div class="empty-state py-24"><div class="empty-icon"><i class="fas fa-object-group"></i></div><h3>Nenhum GHE</h3></div>
    @endforelse
</div>
<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-exclamation-triangle"></i> Riscos Cadastrados ({{ $riscos->count() }})</div></div>
    @forelse($riscos as $r)
    <div class="flex justify-between align-center" style="padding:10px 12px;background:var(--bg-secondary);border-radius:var(--r-sm);margin-bottom:6px">
        <div><div class="font-bold text-13">{{ $r->nome }}</div><div class="text-11 text-muted">{{ ucfirst($r->categoria) }}{{ $r->nr_referencia?' · '.$r->nr_referencia:'' }}</div></div>
        <form method="POST" action="{{ route('riscos.destroy',$r->id) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir?"><i class="fas fa-trash-alt"></i></button></form>
    </div>
    @empty
    <div class="empty-state py-24"><div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div><h3>Nenhum risco</h3></div>
    @endforelse
</div>
</div>

<div class="modal-overlay" id="modalRisco"><div class="modal modal-sm">
<div class="modal-header"><div class="modal-title"><i class="fas fa-exclamation-triangle"></i> Novo Risco</div><button class="modal-close" onclick="closeModal('modalRisco')"><i class="fas fa-times"></i></button></div>
<div class="modal-body"><form method="POST" action="{{ route('riscos.store') }}">@csrf
    <div class="flex flex-col gap-14">
        <div class="form-group"><label class="form-label">Nome *</label><input type="text" name="nome" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Categoria *</label><select name="categoria" class="form-select" required><option value="fisico">Físico</option><option value="quimico">Químico</option><option value="biologico">Biológico</option><option value="ergonomico">Ergonômico</option><option value="acidente">Acidente</option></select></div>
        <div class="form-group"><label class="form-label">NR Referência</label><input type="text" name="nr_referencia" class="form-control" placeholder="Ex: NR-15"></div>
        <div class="form-group"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalRisco')">Cancelar</button><button type="submit" class="btn btn-primary">Criar</button></div>
</form></div></div></div>

<div class="modal-overlay" id="modalGhe"><div class="modal modal-sm">
<div class="modal-header"><div class="modal-title"><i class="fas fa-object-group"></i> Novo GHE</div><button class="modal-close" onclick="closeModal('modalGhe')"><i class="fas fa-times"></i></button></div>
<div class="modal-body"><form method="POST" action="{{ route('ghes.store') }}">@csrf
    <div class="flex flex-col gap-14">
        <div class="form-group"><label class="form-label">Empresa *</label><select name="empresa_id" class="form-select" required><option value="">Selecione</option>@foreach($empresas as $e)<option value="{{ $e->id }}">{{ $e->nome_display }}</option>@endforeach</select></div>
        <div class="form-group"><label class="form-label">Código *</label><input type="text" name="codigo" class="form-control" required placeholder="Ex: GHE-01"></div>
        <div class="form-group"><label class="form-label">Nome *</label><input type="text" name="nome" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalGhe')">Cancelar</button><button type="submit" class="btn btn-primary">Criar</button></div>
</form></div></div></div>
@endsection
