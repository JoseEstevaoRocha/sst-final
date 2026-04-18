@extends('layouts.app')
@section('title','Funções')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Funções / Cargos</h1>
        <p class="page-sub">{{ $funcoes->total() }} cadastradas</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalFunc')"><i class="fas fa-plus"></i> Nova Função</button>
</div>

{{-- FILTROS --}}
<form method="GET" id="filtroForm">
    <div class="filter-bar" style="flex-wrap:wrap;gap:10px">
        {{-- Busca por nome --}}
        <div class="filter-search-wrap" style="position:relative;flex:1;min-width:180px">
            <i class="fas fa-search"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar função..." class="filter-input" style="padding-left:32px;width:100%" oninput="debounceSubmit()">
        </div>

        {{-- Empresa --}}
        <select name="empresa_id" id="filtroEmpresa" class="filter-select" style="min-width:200px" onchange="onEmpresaChange(this.value)">
            <option value="">Todas as empresas</option>
            @foreach($empresas as $e)
            <option value="{{ $e->id }}" {{ request('empresa_id') == $e->id ? 'selected' : '' }}>{{ $e->nome_display }}</option>
            @endforeach
        </select>

        {{-- Setor (carregado por AJAX) --}}
        <select name="setor_id" id="filtroSetor" class="filter-select" style="min-width:180px" onchange="document.getElementById('filtroForm').submit()">
            <option value="">Todos os setores</option>
            @foreach($setores as $s)
            <option value="{{ $s->id }}" {{ request('setor_id') == $s->id ? 'selected' : '' }}>{{ $s->nome }}</option>
            @endforeach
        </select>

        @if(request()->hasAny(['search','empresa_id','setor_id']))
        <a href="{{ route('funcoes.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Limpar</a>
        @endif
    </div>
</form>

{{-- TABELA --}}
<div class="card p-0">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>FUNÇÃO / CBO</th>
                    <th>SETOR</th>
                    <th>EMPRESA</th>
                    <th>PERIODICIDADE ASO</th>
                    <th>EXAMES</th>
                    <th>AÇÕES</th>
                </tr>
            </thead>
            <tbody>
            @forelse($funcoes as $f)
            <tr>
                <td>
                    <div class="font-bold text-13">{{ $f->nome }}</div>
                    @if($f->cbo)<div class="text-11 text-muted font-mono">CBO: {{ $f->cbo }}</div>@endif
                    @if($f->descricao)<div class="text-11 text-muted" style="max-width:220px;white-space:pre-wrap">{{ \Str::limit($f->descricao,60) }}</div>@endif
                </td>
                <td class="text-12">{{ $f->setor->nome ?? '—' }}</td>
                <td class="text-12">{{ $f->empresa->nome_display ?? '—' }}</td>
                <td class="text-12">{{ $f->periodicidade_aso_dias ? $f->periodicidade_aso_dias.'d' : '—' }}</td>
                <td>
                    <a href="{{ route('funcoes.exames', $f->id) }}" class="btn btn-secondary btn-sm" style="font-size:11px">
                        <i class="fas fa-stethoscope"></i> {{ $f->exames()->count() }} exame(s)
                    </a>
                </td>
                <td>
                    <div class="flex gap-4">
                        <button onclick="editFunc({{ json_encode(['id'=>$f->id,'nome'=>$f->nome,'descricao'=>$f->descricao,'setor_id'=>$f->setor_id,'empresa_id'=>$f->empresa_id,'periodicidade_aso_dias'=>$f->periodicidade_aso_dias,'cbo'=>$f->cbo]) }})"
                            class="btn btn-secondary btn-icon" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <form method="POST" action="{{ route('funcoes.destroy', $f->id) }}" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir {{ $f->nome }}?" title="Excluir">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-briefcase"></i></div>
                    <h3>Nenhuma função encontrada</h3>
                    @if(request()->hasAny(['search','empresa_id','setor_id']))
                    <a href="{{ route('funcoes.index') }}" class="btn btn-secondary btn-sm mt-8">Limpar filtros</a>
                    @endif
                </div>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $funcoes->links() }}

{{-- MODAL CRIAR/EDITAR --}}
<div class="modal-overlay" id="modalFunc">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title" id="funcTitle"><i class="fas fa-briefcase"></i> Nova Função</div>
            <button class="modal-close" onclick="closeModal('modalFunc')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" id="funcForm" action="{{ route('funcoes.store') }}">
                @csrf
                <div id="funcMethod"></div>
                <input type="hidden" name="empresa_id" id="fEmpresaHidden">
                <div class="flex flex-col gap-14">
                    <div class="form-group">
                        <label class="form-label">Empresa *</label>
                        <select id="fEmpresa" class="form-select" onchange="loadSetoresMod(this.value)" required>
                            <option value="">Selecione</option>
                            @foreach($empresas as $e)
                            <option value="{{ $e->id }}">{{ $e->nome_display }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Setor *</label>
                        <select name="setor_id" id="fSetor" class="form-select" required>
                            <option value="">Selecione a empresa primeiro</option>
                            @foreach($setores as $s)
                            <option value="{{ $s->id }}">{{ $s->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="fNome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descritivo da Função</label>
                        <textarea name="descricao" id="fDescricao" class="form-control" rows="3" placeholder="Atividades, responsabilidades..."></textarea>
                    </div>
                    <div class="flex gap-14">
                        <div class="form-group" style="flex:1">
                            <label class="form-label">CBO</label>
                            <input type="text" name="cbo" id="fCbo" class="form-control" placeholder="Ex: 7171-10">
                        </div>
                        <div class="form-group" style="flex:1">
                            <label class="form-label">Periodicidade ASO (dias)</label>
                            <input type="number" name="periodicidade_aso_dias" id="fPeriodo" class="form-control" placeholder="365">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('modalFunc')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
// Debounce para busca por texto
let searchTimer;
function debounceSubmit() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => document.getElementById('filtroForm').submit(), 500);
}

// Carrega setores quando empresa muda no filtro
async function onEmpresaChange(eid) {
    const sel = document.getElementById('filtroSetor');
    sel.innerHTML = '<option value="">Todos os setores</option>';
    if (eid) {
        const r = await fetch(`/api/setores?empresa_id=${eid}`, { headers: {'X-Requested-With':'XMLHttpRequest'} });
        const d = await r.json();
        d.forEach(s => { const o = document.createElement('option'); o.value = s.id; o.textContent = s.nome; sel.appendChild(o); });
    }
    document.getElementById('filtroForm').submit();
}

// Carrega setores no modal
async function loadSetoresMod(eid) {
    const sel = document.getElementById('fSetor');
    const hidden = document.getElementById('fEmpresaHidden');
    if (hidden) hidden.value = eid;
    sel.innerHTML = '<option value="">Carregando...</option>';
    if (!eid) { sel.innerHTML = '<option value="">Selecione a empresa primeiro</option>'; return; }
    const r = await fetch(`/api/setores?empresa_id=${eid}`, { headers: {'X-Requested-With':'XMLHttpRequest'} });
    const d = await r.json();
    sel.innerHTML = '<option value="">Selecione o setor</option>';
    d.forEach(s => { const o = document.createElement('option'); o.value = s.id; o.textContent = s.nome; sel.appendChild(o); });
}

function editFunc(f) {
    document.getElementById('funcTitle').innerHTML = '<i class="fas fa-briefcase"></i> Editar Função';
    document.getElementById('funcForm').action = `/funcoes/${f.id}`;
    document.getElementById('funcMethod').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('fEmpresa').value = f.empresa_id;
    document.getElementById('fEmpresaHidden').value = f.empresa_id;
    document.getElementById('fNome').value = f.nome;
    document.getElementById('fDescricao').value = f.descricao || '';
    document.getElementById('fCbo').value = f.cbo || '';
    document.getElementById('fPeriodo').value = f.periodicidade_aso_dias || '';
    loadSetoresMod(f.empresa_id).then(() => {
        setTimeout(() => { document.getElementById('fSetor').value = f.setor_id; }, 400);
    });
    openModal('modalFunc');
}
</script>
@endpush
