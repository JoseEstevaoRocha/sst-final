@extends('layouts.app')
@section('title','Entregas de Uniforme')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Entregas de Uniforme</h1>
        <p class="page-sub">{{ $entregas->total() }} registros</p>
    </div>
    <div class="flex gap-8">
        <a href="{{ route('uniformes.index') }}" class="btn btn-secondary"><i class="fas fa-tshirt"></i> Catálogo</a>
        <button class="btn btn-primary" onclick="openModal('modalEntrega')"><i class="fas fa-plus"></i> Nova Entrega</button>
    </div>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:1rem">
    <form method="GET" id="filtroForm">
        <div class="flex gap-8 flex-wrap" style="padding:.75rem 1rem .5rem;align-items:flex-end">
            <div class="form-group" style="flex:1;min-width:180px;margin:0">
                <input type="text" name="nome" value="{{ request('nome') }}" class="form-control" placeholder="Nome do colaborador…">
            </div>
            @if($empresas->count() > 1)
            <div class="form-group" style="min-width:180px;margin:0">
                <select name="empresa_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas as empresas</option>
                    @foreach($empresas as $emp)
                    <option value="{{ $emp->id }}" {{ request('empresa_id')==$emp->id?'selected':'' }}>{{ $emp->nome_display }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="form-group" style="min-width:160px;margin:0">
                <select name="uniforme_id" class="form-select">
                    <option value="">Todos os uniformes</option>
                    @foreach($uniformes_list as $u)
                    <option value="{{ $u->id }}" {{ request('uniforme_id')==$u->id?'selected':'' }}>{{ $u->nome }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
            @if(request()->hasAny(['nome','empresa_id','setor_id','funcao_id','uniforme_id','de','ate']))
            <a href="{{ route('uniformes.entregas') }}" class="btn btn-ghost">Limpar</a>
            @endif
            <button type="button" class="btn btn-ghost" onclick="toggleFiltroAvancado()">
                <i class="fas fa-sliders-h"></i> Avançado
            </button>
        </div>
        <div id="filtroAvancado" style="display:none;padding:0 1rem .75rem">
            <div class="flex gap-8 flex-wrap" style="align-items:flex-end">
                <div class="form-group" style="min-width:150px;margin:0">
                    <label class="form-label">Setor</label>
                    <select name="setor_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($setores as $s)
                        <option value="{{ $s->id }}" {{ request('setor_id')==$s->id?'selected':'' }}>{{ $s->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="min-width:150px;margin:0">
                    <label class="form-label">Função</label>
                    <select name="funcao_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach($funcoes as $f)
                        <option value="{{ $f->id }}" {{ request('funcao_id')==$f->id?'selected':'' }}>{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="min-width:130px;margin:0">
                    <label class="form-label">Data de</label>
                    <input type="date" name="de" value="{{ request('de') }}" class="form-control">
                </div>
                <div class="form-group" style="min-width:130px;margin:0">
                    <label class="form-label">Data até</label>
                    <input type="date" name="ate" value="{{ request('ate') }}" class="form-control">
                </div>
            </div>
        </div>
    </form>
</div>

<div class="card p-0">
    <div class="table-wrap">
        <table class="table">
        <thead>
            <tr>
                <th>COLABORADOR</th>
                @if($empresas->count() > 1)<th>EMPRESA</th>@endif
                <th>UNIFORME</th>
                <th>TAMANHO</th>
                <th>QTD</th>
                <th>DATA ENTREGA</th>
                <th>MOTIVO</th>
                <th>RESPONSÁVEL</th>
            </tr>
        </thead>
        <tbody>
        @forelse($entregas as $e)
        <tr>
            <td>
                <div class="font-bold text-13">{{ $e->colaborador->nome ?? '—' }}</div>
                @if($e->colaborador)
                <div class="text-11 text-muted">{{ $e->colaborador->setor->nome ?? '' }}</div>
                @endif
            </td>
            @if($empresas->count() > 1)
            <td class="text-12">{{ $e->empresa->nome_display ?? '—' }}</td>
            @endif
            <td>
                <div class="font-bold text-13">{{ $e->uniforme->nome ?? '—' }}</div>
                <div class="text-11 text-muted">{{ $e->uniforme->tipo ?? '' }}</div>
            </td>
            <td><span class="badge badge-info">{{ $e->tamanho->codigo ?? '—' }}</span></td>
            <td class="font-bold text-16" style="color:var(--brand)">{{ $e->quantidade }}</td>
            <td class="font-mono text-12">{{ $e->data_entrega instanceof \Carbon\Carbon ? $e->data_entrega->format('d/m/Y') : \Carbon\Carbon::parse($e->data_entrega)->format('d/m/Y') }}</td>
            <td class="text-12">{{ ucfirst($e->motivo ?? '—') }}</td>
            <td class="text-12">{{ $e->responsavel ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="8">
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                <h3>Nenhuma entrega encontrada</h3>
            </div>
        </td></tr>
        @endforelse
        </tbody>
        </table>
    </div>
    @if($entregas->hasPages())
    <div style="padding:1rem">{{ $entregas->links() }}</div>
    @endif
</div>

{{-- Modal Nova Entrega --}}
<div class="modal-overlay" id="modalEntrega">
    <div class="modal modal-lg" style="max-width:780px">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-box-open"></i> Nova Entrega de Uniforme</div>
            <button class="modal-close" onclick="closeModal('modalEntrega')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('uniformes.entregas.store') }}" id="formEntrega">@csrf

                {{-- Cabeçalho da entrega --}}
                <div class="form-grid">
                    @if($empresas->count() > 1)
                    <div class="form-group">
                        <label class="form-label">Empresa *</label>
                        <select name="empresa_id" id="ent_empresa" class="form-select" required onchange="carregarColaboradoresEmpresa()">
                            <option value="">Selecione</option>
                            @foreach($empresas as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->nome_display }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" name="empresa_id" id="ent_empresa_hidden" value="{{ $empresas->first()->id ?? '' }}">
                    @endif

                    <div class="form-group {{ $empresas->count() > 1 ? '' : 'form-full' }}">
                        <label class="form-label">Colaborador *</label>
                        <select name="colaborador_id" id="colabSelect" class="form-select" required>
                            @if($empresas->count() > 1)
                            <option value="">Selecione a empresa primeiro</option>
                            @else
                            <option value="">Carregando...</option>
                            @endif
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Data de Entrega *</label>
                        <input type="date" name="data_entrega" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Motivo</label>
                        <select name="motivo" class="form-select">
                            <option value="admissao">Admissão</option>
                            <option value="substituicao">Substituição</option>
                            <option value="perda">Perda</option>
                            <option value="dano">Dano</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Responsável</label>
                        <input type="text" name="responsavel" class="form-control" value="{{ auth()->user()->name }}">
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:1rem 0">

                {{-- Itens da entrega --}}
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
                    <h4 style="font-size:14px;font-weight:700;color:var(--text-1);margin:0"><i class="fas fa-list"></i> Itens da Entrega</h4>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addItem()"><i class="fas fa-plus"></i> Adicionar Item</button>
                </div>

                <div id="itensList">
                    {{-- Preenchido dinamicamente pelo JS --}}
                </div>

                <div class="modal-footer" style="margin-top:1rem">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('modalEntrega')">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar Entrega</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.item-row {
    display: grid; grid-template-columns: 1fr 120px 80px 36px;
    gap: 8px; align-items: end; margin-bottom: 8px;
    padding: 10px 12px; background: var(--bg-card);
    border: 1px solid var(--border); border-radius: var(--r-sm);
}
.item-row-num {
    grid-column: 1/-1;
    font-size: 11px; font-weight: 600; color: var(--brand);
    margin-bottom: 4px;
}
</style>
@endpush

@push('scripts')
<script>
// ---- Uniformes & Tamanhos data ----
const uniformesData = @json($uniformes_list->map(fn($u)=>['id'=>$u->id,'nome'=>$u->nome,'tipo'=>$u->tipo]));
const tamanhoData   = @json($tamanhos->map(fn($t)=>['id'=>$t->id,'codigo'=>$t->codigo]));

// ---- Filtro avançado toggle ----
function toggleFiltroAvancado() {
    const el = document.getElementById('filtroAvancado');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
@if(request()->hasAny(['setor_id','funcao_id','de','ate']))
document.getElementById('filtroAvancado').style.display = 'block';
@endif

// ---- Colaborador dropdown ----
async function carregarColaboradoresEmpresa() {
    const eid = document.getElementById('ent_empresa')?.value || '';
    const sel = document.getElementById('colabSelect');
    if (!eid) {
        sel.innerHTML = '<option value="">Selecione a empresa primeiro</option>';
        return;
    }
    sel.innerHTML = '<option value="">Carregando...</option>';
    try {
        const r    = await fetch(`/api/colaboradores?empresa_id=${eid}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
        const data = await r.json();
        if (!data.length) {
            sel.innerHTML = '<option value="">Nenhum colaborador encontrado</option>';
            return;
        }
        sel.innerHTML = '<option value="">Selecione o colaborador</option>';
        data.forEach(c => {
            const o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.nome + (c.setor ? ' — ' + c.setor : '');
            sel.appendChild(o);
        });
    } catch(e) {
        sel.innerHTML = '<option value="">Erro ao carregar</option>';
    }
}

// Para empresa única: carrega ao abrir o modal
const _origOpenUni = window.openModal;
window.openModal = function(id) {
    if (id === 'modalEntrega') {
        const hiddenEmp = document.getElementById('ent_empresa_hidden');
        const empSel    = document.getElementById('ent_empresa');
        // empresa única: carrega colaboradores automaticamente
        if (hiddenEmp && hiddenEmp.value) {
            const sel = document.getElementById('colabSelect');
            if (sel && sel.options.length <= 1) {
                fetch(`/api/colaboradores?empresa_id=${hiddenEmp.value}`, {headers:{'X-Requested-With':'XMLHttpRequest'}})
                    .then(r => r.json())
                    .then(data => {
                        sel.innerHTML = '<option value="">Selecione o colaborador</option>';
                        data.forEach(c => {
                            const o = document.createElement('option');
                            o.value = c.id;
                            o.textContent = c.nome + (c.setor ? ' — ' + c.setor : '');
                            sel.appendChild(o);
                        });
                    });
            }
        }
        // Inicializa itens se vazio
        if (document.getElementById('itensList').children.length === 0) addItem();
    }
    _origOpenUni && _origOpenUni(id);
};

// ---- Itens da entrega ----
let itemCount = 0;

function buildUniformeOpts(selectedId) {
    return '<option value="">Selecione o uniforme</option>' +
        uniformesData.map(u => `<option value="${u.id}" ${u.id==selectedId?'selected':''}>${u.nome} (${u.tipo})</option>`).join('');
}

function buildTamanhoOpts(selectedId) {
    return '<option value="">Tamanho</option>' +
        tamanhoData.map(t => `<option value="${t.id}" ${t.id==selectedId?'selected':''}>${t.codigo}</option>`).join('');
}

function addItem(uniId, tamId, qty) {
    const idx = itemCount++;
    const row = document.createElement('div');
    row.className = 'item-row';
    row.id = 'irow_'+idx;
    row.innerHTML = `
        <div class="item-row-num" style="grid-column:1/-1">Item ${idx+1}</div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Uniforme *</label>
            <select name="items[${idx}][uniforme_id]" class="form-select" required>${buildUniformeOpts(uniId)}</select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Tamanho *</label>
            <select name="items[${idx}][tamanho_id]" class="form-select" required>${buildTamanhoOpts(tamId)}</select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Qtd</label>
            <input type="number" name="items[${idx}][quantidade]" class="form-control" value="${qty||1}" min="1" required>
        </div>
        <div style="padding-bottom:2px">
            <button type="button" class="btn btn-ghost btn-icon text-danger" onclick="removeItem('irow_${idx}')" title="Remover item"><i class="fas fa-trash"></i></button>
        </div>
    `;
    document.getElementById('itensList').appendChild(row);
}

function removeItem(rowId) {
    const el = document.getElementById(rowId);
    if (el) el.remove();
}

</script>
@endpush
