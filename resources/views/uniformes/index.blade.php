@extends('layouts.app')
@section('title','Uniformes')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">Catálogo de Uniformes</h1></div>
    <div class="flex gap-8">
        <a href="{{ route('uniformes.grade') }}" class="btn btn-secondary"><i class="fas fa-table"></i> Consulta de Grade</a>
        <a href="{{ route('uniformes.entregas') }}" class="btn btn-secondary"><i class="fas fa-box-open"></i> Entregas</a>
        <a href="{{ route('uniformes.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Uniforme</a>
    </div>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:1rem">
    <form method="GET" class="flex gap-8 flex-wrap" style="padding:.75rem 1rem;align-items:flex-end">
        <div class="form-group" style="flex:1;min-width:180px;margin:0">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por nome…">
        </div>
        <div class="form-group" style="min-width:140px;margin:0">
            <select name="tipo" class="form-select">
                <option value="">Todos os tipos</option>
                @foreach(['Camisa','Calça','Bota','Jaleco','Colete','Cinto','Boné','Macacão','Luva','Outros'] as $t)
                <option value="{{ $t }}" {{ request('tipo')===$t?'selected':'' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        @if($empresas->count() > 1)
        <div class="form-group" style="min-width:180px;margin:0">
            <select name="empresa_id" class="form-select">
                <option value="">Todas as empresas</option>
                @foreach($empresas as $emp)
                <option value="{{ $emp->id }}" {{ request('empresa_id')==$emp->id?'selected':'' }}>{{ $emp->nome_display }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="btn btn-primary">Buscar</button>
        @if(request()->hasAny(['search','tipo','empresa_id']))
        <a href="{{ route('uniformes.index') }}" class="btn btn-ghost">Limpar</a>
        @endif
    </form>
</div>

<div class="card p-0">
    <div class="table-wrap">
        <table class="table">
        <thead>
            <tr>
                <th>UNIFORME</th>
                <th>TIPO</th>
                @if($empresas->count() > 1)<th>EMPRESA</th>@endif
                <th>FORNECEDOR</th>
                <th>GRADE DE ESTOQUE</th>
                <th>CUSTO</th>
                <th>STATUS</th>
                <th>AÇÕES</th>
            </tr>
        </thead>
        <tbody>
        @forelse($uniformes as $u)
        <tr>
            <td class="font-bold text-13">{{ $u->nome }}</td>
            <td><span class="badge badge-secondary">{{ $u->tipo }}</span></td>
            @if($empresas->count() > 1)
            <td class="text-12">{{ $u->empresa->nome_display ?? '—' }}</td>
            @endif
            <td class="text-12">{{ $u->fornecedor ?? '—' }}</td>
            <td>
                <div class="grade-chips">
                    @foreach($u->estoques->sortBy('tamanho.ordem') as $est)
                    @php $nivel = $est->quantidade<=0?'danger':($est->baixo_estoque?'warn':'ok'); @endphp
                    <span class="grade-chip grade-{{ $nivel }}"
                          onclick="openEstModal({{ $u->id }},{{ $est->tamanho_id }},'{{ $est->tamanho->codigo }}',{{ $est->quantidade }},{{ $est->minimo ?? 0 }})"
                          title="Clique para editar estoque">
                        <span class="grade-code">{{ $est->tamanho->codigo }}</span>
                        <span class="grade-qty">{{ $est->quantidade }}</span>
                    </span>
                    @endforeach
                    <button type="button" onclick="openEstModal({{ $u->id }},null,'',0,0)" class="grade-chip grade-add" title="Adicionar tamanho">+</button>
                </div>
            </td>
            <td class="text-12">{{ $u->custo_unitario ? 'R$ '.number_format($u->custo_unitario,2,',','.') : '—' }}</td>
            <td><span class="badge {{ $u->status==='Ativo'?'badge-success':'badge-danger' }}">{{ $u->status }}</span></td>
            <td>
                <div class="flex gap-4">
                    <a href="{{ route('uniformes.edit',$u->id) }}" class="btn btn-secondary btn-icon" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                    <form method="POST" action="{{ route('uniformes.destroy',$u->id) }}" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir este uniforme?" title="Excluir"><i class="fas fa-trash-alt"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="8">
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-tshirt"></i></div>
                <h3>Nenhum uniforme encontrado</h3>
                <a href="{{ route('uniformes.create') }}" class="btn btn-primary" style="margin-top:.5rem"><i class="fas fa-plus"></i> Cadastrar</a>
            </div>
        </td></tr>
        @endforelse
        </tbody>
        </table>
    </div>
    @if($uniformes->hasPages())
    <div style="padding:1rem">{{ $uniformes->links() }}</div>
    @endif
</div>

{{-- Modal de estoque --}}
<div class="modal-overlay" id="estModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-ruler"></i> <span id="estTitle">Estoque por Tamanho</span></div>
            <button class="modal-close" onclick="closeModal('estModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" id="estForm">@csrf
                <div class="flex flex-col gap-16">
                    <div class="form-group">
                        <label class="form-label">Tamanho *</label>
                        <select name="tamanho_id" id="estTam" class="form-select" required>
                            <option value="">Selecione</option>
                            @foreach($tamanhos as $t)
                            <option value="{{ $t->id }}">{{ $t->codigo }} — {{ $t->descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantidade em Estoque</label>
                        <input type="number" name="quantidade" id="estQty" class="form-control" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estoque Mínimo</label>
                        <input type="number" name="minimo" id="estMin" class="form-control" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('estModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEstModal(uniId, tamId, tamCod, qty, min) {
    document.getElementById('estTitle').textContent = tamCod ? 'Estoque — '+tamCod : 'Adicionar Tamanho';
    document.getElementById('estForm').action = `/uniformes/${uniId}/estoque`;
    document.getElementById('estTam').value  = tamId || '';
    document.getElementById('estQty').value  = qty ?? 0;
    document.getElementById('estMin').value  = min ?? 0;
    openModal('estModal');
}
</script>
@endpush
