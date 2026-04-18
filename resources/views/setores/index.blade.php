@extends('layouts.app')
@section('title','Setores')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Setores</h1>
        <p class="page-sub">{{ $setores->total() }} cadastrados</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalSetor')"><i class="fas fa-plus"></i> Novo Setor</button>
</div>

{{-- FILTROS --}}
<form method="GET" id="filtroForm">
    <div class="filter-bar" style="flex-wrap:wrap;gap:10px">
        <div class="filter-search-wrap" style="position:relative;flex:1;min-width:180px">
            <i class="fas fa-search"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar setor..." class="filter-input" style="padding-left:32px;width:100%" oninput="debounceSubmit()">
        </div>
        <select name="empresa_id" class="filter-select" style="min-width:200px" onchange="document.getElementById('filtroForm').submit()">
            <option value="">Todas as empresas</option>
            @foreach($empresas as $e)
            <option value="{{ $e->id }}" {{ request('empresa_id') == $e->id ? 'selected' : '' }}>{{ $e->nome_display }}</option>
            @endforeach
        </select>
        @if(request()->hasAny(['search','empresa_id']))
        <a href="{{ route('setores.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Limpar</a>
        @endif
    </div>
</form>

{{-- TABELA --}}
<div class="card p-0">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>SETOR</th>
                    <th>EMPRESA</th>
                    <th>DESCRIÇÃO</th>
                    <th>EXAMES</th>
                    <th>AÇÕES</th>
                </tr>
            </thead>
            <tbody>
            @forelse($setores as $s)
            <tr>
                <td class="font-bold text-13">{{ $s->nome }}</td>
                <td class="text-12">{{ $s->empresa->nome_display ?? '—' }}</td>
                <td class="text-12" style="max-width:240px">{{ \Str::limit($s->descricao ?? '—', 60) }}</td>
                <td>
                    <a href="{{ route('setores.exames', $s->id) }}" class="btn btn-secondary btn-sm" style="font-size:11px">
                        <i class="fas fa-stethoscope"></i> {{ $s->exames()->count() }} exame(s)
                    </a>
                </td>
                <td>
                    <div class="flex gap-4">
                        <button onclick="editSetor({{ json_encode(['id'=>$s->id,'nome'=>$s->nome,'descricao'=>$s->descricao,'empresa_id'=>$s->empresa_id]) }})"
                            class="btn btn-secondary btn-icon" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <form method="POST" action="{{ route('setores.destroy', $s->id) }}" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir {{ $s->nome }}?" title="Excluir">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-layer-group"></i></div>
                    <h3>Nenhum setor encontrado</h3>
                    @if(request()->hasAny(['search','empresa_id']))
                    <a href="{{ route('setores.index') }}" class="btn btn-secondary btn-sm mt-8">Limpar filtros</a>
                    @endif
                </div>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $setores->links() }}

{{-- MODAL CRIAR/EDITAR --}}
<div class="modal-overlay" id="modalSetor">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title" id="setorTitle"><i class="fas fa-layer-group"></i> Novo Setor</div>
            <button class="modal-close" onclick="closeModal('modalSetor')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" id="setorForm" action="{{ route('setores.store') }}">
                @csrf
                <div id="setorMethod"></div>
                <div class="flex flex-col gap-14">
                    <div class="form-group">
                        <label class="form-label">Empresa *</label>
                        <select name="empresa_id" id="sEmpresa" class="form-select" required>
                            <option value="">Selecione</option>
                            @foreach($empresas as $e)
                            <option value="{{ $e->id }}">{{ $e->nome_display }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="sNome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" id="sDesc" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('modalSetor')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
let searchTimer;
function debounceSubmit() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => document.getElementById('filtroForm').submit(), 500);
}
function editSetor(s) {
    document.getElementById('setorTitle').innerHTML = '<i class="fas fa-layer-group"></i> Editar Setor';
    document.getElementById('setorForm').action = `/setores/${s.id}`;
    document.getElementById('setorMethod').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('sEmpresa').value = s.empresa_id;
    document.getElementById('sNome').value = s.nome;
    document.getElementById('sDesc').value = s.descricao || '';
    openModal('modalSetor');
}
</script>
@endpush
