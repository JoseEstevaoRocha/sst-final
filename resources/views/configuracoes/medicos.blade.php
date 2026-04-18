@extends('layouts.app')
@section('title','Médicos do Trabalho')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Médicos do Trabalho</h1>
        <p class="page-sub">Cadastro de médicos para seleção nos ASOs</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalNovo')"><i class="fas fa-plus"></i> Novo Médico</button>
</div>

{{-- FILTROS --}}
<form method="GET">
<div class="filter-bar mb-16">
    <div class="filter-search-wrap"><i class="fas fa-search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome do médico..." class="filter-input filter-input-icon" style="width:220px">
    </div>
    <select name="clinica_id" class="filter-select" style="width:200px" onchange="this.form.submit()">
        <option value="">Todas as clínicas</option>
        @foreach($clinicas as $c)<option value="{{ $c->id }}" {{ request('clinica_id')==$c->id?'selected':'' }}>{{ $c->nome }}</option>@endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i></button>
    @if(request()->hasAny(['search','clinica_id']))<a href="{{ route('medicos.index') }}" class="btn btn-ghost btn-sm">✕</a>@endif
</div>
</form>

<div class="card p-0">
<div class="table-wrap">
<table class="table">
<thead><tr>
    <th>NOME</th><th>CRM</th><th>ESPECIALIDADE</th><th>CLÍNICA VINCULADA</th><th>STATUS</th><th style="width:100px">AÇÕES</th>
</tr></thead>
<tbody>
@forelse($medicos as $m)
<tr>
    <td class="font-bold text-13">{{ $m->nome }}</td>
    <td class="font-mono text-12">{{ $m->crm ?: '—' }}</td>
    <td class="text-12">{{ $m->especialidade }}</td>
    <td class="text-12">{{ $m->clinica?->nome ?? '—' }}</td>
    <td><span class="badge {{ $m->ativo ? 'badge-success' : 'badge-secondary' }}">{{ $m->ativo ? 'Ativo' : 'Inativo' }}</span></td>
    <td>
        <div class="flex gap-4">
            <button class="btn btn-secondary btn-icon btn-sm" title="Editar"
                onclick="editarMedico({{ $m->id }}, '{{ addslashes($m->nome) }}', '{{ addslashes($m->crm ?? '') }}', '{{ addslashes($m->especialidade) }}', {{ $m->clinica_id ?? 'null' }}, {{ $m->ativo ? 'true' : 'false' }})">
                <i class="fas fa-pencil-alt"></i>
            </button>
            <form method="POST" action="{{ route('medicos.destroy',$m->id) }}" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-icon btn-sm text-danger" data-confirm="Excluir Dr(a). {{ $m->nome }}?">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="6"><div class="empty-state py-24"><i class="fas fa-user-md" style="font-size:32px;color:var(--text-3)"></i><p style="margin-top:8px">Nenhum médico cadastrado</p></div></td></tr>
@endforelse
</tbody>
</table>
</div>
@if($medicos->hasPages())
<div class="pagination-bar">
    <span class="pag-info">{{ $medicos->firstItem() }}–{{ $medicos->lastItem() }} de {{ $medicos->total() }}</span>
    <div class="pagination">
        @if(!$medicos->onFirstPage())<a href="{{ $medicos->previousPageUrl() }}" class="page-btn">‹</a>@else<span class="page-btn disabled">‹</span>@endif
        @foreach($medicos->getUrlRange(max(1,$medicos->currentPage()-2),min($medicos->lastPage(),$medicos->currentPage()+2)) as $p=>$u)<a href="{{ $u }}" class="page-btn {{ $p==$medicos->currentPage()?'active':'' }}">{{ $p }}</a>@endforeach
        @if($medicos->hasMorePages())<a href="{{ $medicos->nextPageUrl() }}" class="page-btn">›</a>@else<span class="page-btn disabled">›</span>@endif
    </div>
</div>
@endif
</div>

{{-- MODAL NOVO --}}
<div class="modal-overlay" id="modalNovo">
<div class="modal modal-sm">
    <div class="modal-header">
        <div class="modal-title"><i class="fas fa-user-md"></i> Novo Médico</div>
        <button class="modal-close" onclick="closeModal('modalNovo')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
    <form method="POST" action="{{ route('medicos.store') }}">
        @csrf
        <div class="flex flex-col gap-14">
            <div class="form-group">
                <label class="form-label">Nome Completo <span class="required">*</span></label>
                <input type="text" name="nome" class="form-control" placeholder="Dr(a). Nome" required>
            </div>
            <div class="form-group">
                <label class="form-label">CRM</label>
                <input type="text" name="crm" class="form-control" placeholder="Ex: 12345/SP">
            </div>
            <div class="form-group">
                <label class="form-label">Especialidade</label>
                <input type="text" name="especialidade" class="form-control" value="Medicina do Trabalho">
            </div>
            <div class="form-group">
                <label class="form-label">Clínica Vinculada</label>
                <select name="clinica_id" class="form-select">
                    <option value="">Nenhuma / Independente</option>
                    @foreach($clinicas as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalNovo')">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cadastrar</button>
        </div>
    </form>
    </div>
</div>
</div>

{{-- MODAL EDITAR --}}
<div class="modal-overlay" id="modalEditar">
<div class="modal modal-sm">
    <div class="modal-header">
        <div class="modal-title"><i class="fas fa-user-md"></i> Editar Médico</div>
        <button class="modal-close" onclick="closeModal('modalEditar')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
    <form method="POST" id="formEditar">
        @csrf @method('PUT')
        <div class="flex flex-col gap-14">
            <div class="form-group">
                <label class="form-label">Nome Completo <span class="required">*</span></label>
                <input type="text" name="nome" id="editNome" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">CRM</label>
                <input type="text" name="crm" id="editCrm" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Especialidade</label>
                <input type="text" name="especialidade" id="editEspecialidade" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Clínica Vinculada</label>
                <select name="clinica_id" id="editClinica" class="form-select">
                    <option value="">Nenhuma / Independente</option>
                    @foreach($clinicas as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="ativo" id="editAtivo" value="1" checked style="accent-color:var(--brand)">
                    Médico ativo
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalEditar')">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </form>
    </div>
</div>
</div>

@endsection
@push('scripts')
<script>
function editarMedico(id, nome, crm, esp, clinicaId, ativo) {
    document.getElementById('editNome').value = nome;
    document.getElementById('editCrm').value  = crm;
    document.getElementById('editEspecialidade').value = esp;
    document.getElementById('editClinica').value = clinicaId || '';
    document.getElementById('editAtivo').checked  = ativo;
    document.getElementById('formEditar').action = `/configuracoes/medicos/${id}`;
    openModal('modalEditar');
}
</script>
@endpush
