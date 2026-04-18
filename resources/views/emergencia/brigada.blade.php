@extends('layouts.app')
@section('title','Brigada de Incêndio')

@push('styles')
<style>
.setor-section-row td {
    background: var(--bg-secondary, #f8f9fa);
    padding: 7px 16px;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--text-muted, #6c757d);
    border-top: 2px solid var(--border, #dee2e6);
}
.setor-section-row:first-child td { border-top: none; }

/* Fila de seleção no modal lote */
#brigadaLoteForm .sel-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 8px;
    align-items: end;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    padding: 12px;
    margin-bottom: 12px;
}
/* Lista de pendentes */
#brigadaPendentes {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 12px;
    min-height: 40px;
}
.brigada-pending-item {
    display: grid;
    grid-template-columns: 22px 1fr auto auto auto;
    align-items: center;
    gap: 10px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    padding: 8px 12px;
    font-size: 13px;
    overflow: hidden;
}
.brigada-pending-item .bp-num {
    font-size: 11px;
    font-weight: 800;
    color: var(--brand);
    text-align: center;
}
.brigada-pending-item .bp-nome { font-weight: 600; }
.brigada-pending-item .bp-funcao { font-size: 11px; color: var(--text-muted); }
.brigada-pending-item input[type=date] {
    width: 130px;
    font-size: 11px;
    padding: 3px 6px;
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    background: var(--bg-secondary);
    color: var(--text-1);
}
.brigada-pending-empty {
    font-size: 12px;
    color: var(--text-muted);
    text-align: center;
    padding: 14px;
    border: 1px dashed var(--border);
    border-radius: var(--r-sm);
}
</style>
@endpush

@section('content')
@php
$grouped = $brigadistas->groupBy(fn($b) => $b->colaborador?->setor?->nome ?? 'Sem Setor');
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Brigada de Incêndio</h1>
        <p class="page-sub">{{ $brigadistas->count() }} brigadistas ativos em {{ $grouped->count() }} setor(es)</p>
    </div>
    <div class="flex gap-8">
        <a href="{{ route('brigada.dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-map"></i> Planta / Dashboard
        </a>
        <button class="btn btn-primary" onclick="openModal('modalBrigada')">
            <i class="fas fa-plus"></i> Adicionar Brigadistas
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-16">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-16">{{ session('error') }}</div>
@endif

<div class="card p-0">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>COLABORADOR</th>
                    <th>SETOR</th>
                    <th>FUNÇÃO NA BRIGADA</th>
                    <th>INÍCIO</th>
                    <th>VALIDADE CERT.</th>
                    <th>STATUS</th>
                    <th>AÇÕES</th>
                </tr>
            </thead>
            <tbody>
            @forelse($grouped as $setorNome => $lista)
                <tr class="setor-section-row">
                    <td colspan="7">
                        <i class="fas fa-layer-group" style="margin-right:6px;opacity:.6"></i>
                        {{ $setorNome }}
                        <span class="badge badge-secondary" style="margin-left:8px">{{ $lista->count() }}</span>
                    </td>
                </tr>
                @foreach($lista as $b)
                <tr>
                    <td>
                        <div class="font-bold text-13">{{ $b->colaborador?->nome ?? '—' }}</div>
                        <div class="text-11 text-muted">{{ $b->colaborador?->funcao?->nome ?? '' }}</div>
                    </td>
                    <td class="text-12">{{ $b->colaborador?->setor?->nome ?? '—' }}</td>
                    <td class="text-12">{{ $b->funcao_brigada ?? '—' }}</td>
                    <td class="font-mono text-12">{{ $b->data_inicio?->format('d/m/Y') ?? '—' }}</td>
                    <td class="font-mono text-12 {{ $b->data_validade_cert && $b->data_validade_cert->isPast() ? 'text-danger' : '' }}">
                        {{ $b->data_validade_cert?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td><span class="badge {{ $b->ativo ? 'badge-success' : 'badge-danger' }}">{{ $b->ativo ? 'Ativo' : 'Inativo' }}</span></td>
                    <td>
                        <div class="flex gap-4">
                            <button type="button" class="btn btn-ghost btn-icon"
                                onclick="brigadaAbrirEdit({{ $b->id }})" title="Editar">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <form method="POST" action="{{ route('brigada.destroy', $b->id) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Remover brigadista?">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-user-shield"></i></div>
                            <h3>Nenhum brigadista cadastrado</h3>
                            <p>Clique em "Adicionar Brigadistas" para começar.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Adicionar Brigadistas (lote) --}}
<div class="modal-overlay" id="modalBrigada">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user-shield"></i> Adicionar Brigadistas</div>
            <button class="modal-close" onclick="brigadaFecharModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('brigada.lote') }}" id="brigadaLoteForm">
                @csrf
                @if(auth()->user()->isSuperAdmin() && $empresas->count())
                <input type="hidden" name="empresa_id" id="brigadaEmpresaHidden">
                @endif

                {{-- ── Seleção cascata ── --}}
                <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--r-sm);padding:14px;margin-bottom:16px">
                    <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">
                        <i class="fas fa-plus-circle" style="margin-right:5px;color:var(--brand)"></i>
                        Selecionar e adicionar à lista
                    </div>

                    {{-- Linha 1: Empresa (super-admin) --}}
                    @if(auth()->user()->isSuperAdmin() && $empresas->count())
                    <div class="form-group" style="margin-bottom:8px">
                        <label class="form-label">Empresa</label>
                        <select id="bEmpresa" class="form-select" onchange="brigadaOnEmpresa(this.value)">
                            <option value="">Selecione a empresa</option>
                            @foreach($empresas as $e)
                            <option value="{{ $e->id }}">{{ $e->nome_fantasia ?: $e->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Linha 2: Setor + Colaborador + Botão --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Setor</label>
                            <select id="bSetor" class="form-select" onchange="brigadaLoadColaboradores(this.value)">
                                <option value="">Selecione o setor</option>
                                @foreach($setores as $s)
                                <option value="{{ $s->id }}">{{ $s->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Colaborador</label>
                            <select id="bColaborador" class="form-select">
                                <option value="">— Selecione o setor primeiro —</option>
                            </select>
                        </div>
                    </div>

                    {{-- Linha 3: Função + Datas + Botão adicionar --}}
                    <div style="display:grid;grid-template-columns:1fr 130px 130px auto;gap:8px;align-items:end">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Função na Brigada</label>
                            <select id="bFuncao" class="form-select">
                                <option value="">Selecione</option>
                                @foreach(['Líder de Brigada','Coordenador Geral','Brigadista'] as $f)
                                <option value="{{ $f }}">{{ $f }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label" style="font-size:11px">Início</label>
                            <input type="date" id="bDataInicio" class="form-control" style="font-size:12px">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label" style="font-size:11px">Validade Cert.</label>
                            <input type="date" id="bDataValidade" class="form-control" style="font-size:12px">
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary" onclick="brigadaAdicionarItem()" style="white-space:nowrap">
                                <i class="fas fa-plus"></i> Adicionar
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── Lista de pendentes ── --}}
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <div style="font-size:13px;font-weight:700;color:var(--text-1)">
                        <i class="fas fa-list" style="margin-right:5px"></i>
                        Lista para cadastro
                        <span id="brigadaContador" class="badge badge-secondary" style="margin-left:6px">0</span>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm text-danger" onclick="brigadaLimparTodos()" id="brigadaBtnLimpar" style="display:none">
                        <i class="fas fa-trash"></i> Limpar todos
                    </button>
                </div>

                <div id="brigadaPendentes">
                    <div class="brigada-pending-empty" id="brigadaVazio">
                        Nenhum brigadista na lista. Use o formulário acima para adicionar.
                    </div>
                </div>

                <div class="modal-footer" style="padding-top:8px">
                    <button type="button" class="btn btn-ghost" onclick="brigadaFecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="brigadaBtnSalvar" disabled>
                        <i class="fas fa-save"></i> Cadastrar todos
                        <span id="brigadaBtnCount" style="margin-left:4px"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar Brigadista --}}
<div class="modal-overlay" id="modalEditarBrigadista">
    <div class="modal modal-md">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-pencil-alt"></i> Editar Brigadista</div>
            <button class="modal-close" onclick="closeModal('modalEditarBrigadista')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" id="formEditarBrigadista">
                @csrf @method('PUT')
                <div style="background:var(--bg-secondary);border-radius:var(--r-sm);padding:12px;margin-bottom:14px">
                    <div style="font-size:11px;color:var(--text-muted);margin-bottom:2px">Brigadista</div>
                    <div id="editBrigNome" style="font-weight:700;font-size:15px"></div>
                    <div id="editBrigSetor" style="font-size:12px;color:var(--text-muted)"></div>
                </div>
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label class="form-label">Função na Brigada *</label>
                        <select name="funcao_brigada" id="editBrigFuncao" class="form-select" required>
                            <option value="">Selecione</option>
                            @foreach(['Líder de Brigada','Coordenador Geral','Brigadista'] as $f)
                            <option value="{{ $f }}">{{ $f }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data de Início</label>
                        <input type="date" name="data_inicio" id="editBrigInicio" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Validade da Certificação</label>
                        <input type="date" name="data_validade_cert" id="editBrigValidade" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('modalEditarBrigadista')">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const brigadaIsSuperAdmin = {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }};
let brigadaItens = []; // [{colaborador_id, nome, setor, funcao_brigada, data_inicio, data_validade_cert}]
let brigadaSeqIdx = 0;

// ── Cascata empresa → setores ──────────────────────────────────────────────
function brigadaOnEmpresa(empresaId) {
    document.getElementById('brigadaEmpresaHidden').value = empresaId;
    const setorSel = document.getElementById('bSetor');
    const colSel   = document.getElementById('bColaborador');
    setorSel.innerHTML = '<option value="">Carregando...</option>';
    colSel.innerHTML   = '<option value="">— Selecione o setor primeiro —</option>';
    if (!empresaId) { setorSel.innerHTML = '<option value="">Selecione o setor</option>'; return; }
    fetch(`/api/setores?empresa_id=${empresaId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            setorSel.innerHTML = '<option value="">Selecione o setor</option>' +
                data.map(s => `<option value="${s.id}">${s.nome}</option>`).join('');
        });
}

// ── Cascata setor → colaboradores ─────────────────────────────────────────
function brigadaLoadColaboradores(setorId) {
    const colSel = document.getElementById('bColaborador');
    colSel.innerHTML = '<option value="">Carregando...</option>';
    if (!setorId) { colSel.innerHTML = '<option value="">— Selecione o setor primeiro —</option>'; return; }
    const empresaId = brigadaIsSuperAdmin ? (document.getElementById('bEmpresa')?.value ?? '') : '';
    const params = new URLSearchParams({ setor_id: setorId });
    if (empresaId) params.set('empresa_id', empresaId);
    fetch(`/api/colaboradores?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (!data.length) {
                colSel.innerHTML = '<option value="">Nenhum colaborador neste setor</option>';
                return;
            }
            colSel.innerHTML = '<option value="">Selecione o colaborador</option>' +
                data.map(c => `<option value="${c.id}" data-nome="${c.nome}" data-setor="${c.setor??''}">${c.nome}</option>`).join('');
        });
}

// ── Adicionar item à lista pendente ───────────────────────────────────────
function brigadaAdicionarItem() {
    const colSel   = document.getElementById('bColaborador');
    const funcaoEl = document.getElementById('bFuncao');
    const colId    = colSel.value;
    const funcao   = funcaoEl.value;

    if (!colId)  { alert('Selecione um colaborador.'); return; }
    if (!funcao) { alert('Selecione a função na brigada.'); return; }

    // Verifica duplicata
    if (brigadaItens.some(i => String(i.colaborador_id) === String(colId))) {
        alert('Este colaborador já foi adicionado à lista.'); return;
    }

    const opt       = colSel.options[colSel.selectedIndex];
    const nome      = opt.dataset.nome || opt.textContent;
    const setor     = opt.dataset.setor || document.getElementById('bSetor').options[document.getElementById('bSetor').selectedIndex]?.textContent || '';
    const dataInicio   = document.getElementById('bDataInicio').value;
    const dataValidade = document.getElementById('bDataValidade').value;
    const idx = brigadaSeqIdx++;

    brigadaItens.push({ idx, colaborador_id: colId, nome, setor, funcao_brigada: funcao, data_inicio: dataInicio, data_validade_cert: dataValidade });
    brigadaRenderLista();

    // Limpa seleção para facilitar próximo cadastro
    colSel.value = '';
    funcaoEl.value = '';
    document.getElementById('bDataInicio').value = '';
    document.getElementById('bDataValidade').value = '';
}

// ── Renderizar lista de pendentes ─────────────────────────────────────────
function brigadaRenderLista() {
    const container = document.getElementById('brigadaPendentes');
    const vazioEl   = document.getElementById('brigadaVazio');
    const contador  = document.getElementById('brigadaContador');
    const btnSalvar = document.getElementById('brigadaBtnSalvar');
    const btnCount  = document.getElementById('brigadaBtnCount');
    const btnLimpar = document.getElementById('brigadaBtnLimpar');

    contador.textContent = brigadaItens.length;
    btnSalvar.disabled   = brigadaItens.length === 0;
    btnCount.textContent = brigadaItens.length > 0 ? `(${brigadaItens.length})` : '';
    btnLimpar.style.display = brigadaItens.length > 0 ? '' : 'none';

    if (!brigadaItens.length) {
        container.innerHTML = '';
        container.appendChild(vazioEl);
        vazioEl.style.display = '';
        return;
    }

    // Remove campos hidden antigos
    document.querySelectorAll('.brigada-hidden-input').forEach(el => el.remove());

    vazioEl.style.display = 'none';
    container.innerHTML = '';

    brigadaItens.forEach((item, pos) => {
        const div = document.createElement('div');
        div.className = 'brigada-pending-item';
        div.id = `bp_${item.idx}`;
        div.innerHTML = `
            <span class="bp-num">${pos + 1}</span>
            <div>
                <div class="bp-nome">${item.nome}</div>
                <div class="bp-funcao">${item.setor ? item.setor + ' · ' : ''}${item.funcao_brigada}</div>
            </div>
            <input type="date" title="Início" value="${item.data_inicio}" onchange="brigadaUpdateItem(${item.idx},'data_inicio',this.value)">
            <input type="date" title="Validade cert." value="${item.data_validade_cert}" onchange="brigadaUpdateItem(${item.idx},'data_validade_cert',this.value)">
            <button type="button" class="btn btn-ghost btn-icon text-danger btn-sm" onclick="brigadaRemoverItem(${item.idx})" title="Remover">
                <i class="fas fa-times" style="font-size:12px"></i>
            </button>
        `;
        container.appendChild(div);
    });

    // Injeta hidden inputs para o form
    const form = document.getElementById('brigadaLoteForm');
    brigadaItens.forEach((item, pos) => {
        [
            ['colaborador_id', item.colaborador_id],
            ['funcao_brigada', item.funcao_brigada],
            ['data_inicio', item.data_inicio],
            ['data_validade_cert', item.data_validade_cert],
        ].forEach(([key, val]) => {
            if (!val) return;
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = `itens[${pos}][${key}]`;
            inp.value = val;
            inp.className = 'brigada-hidden-input';
            form.appendChild(inp);
        });
    });
}

function brigadaUpdateItem(idx, field, val) {
    const item = brigadaItens.find(i => i.idx === idx);
    if (item) {
        item[field] = val;
        brigadaRenderLista();
    }
}

function brigadaRemoverItem(idx) {
    brigadaItens = brigadaItens.filter(i => i.idx !== idx);
    brigadaRenderLista();
}

function brigadaLimparTodos() {
    if (!confirm('Limpar todos os brigadistas da lista?')) return;
    brigadaItens = [];
    brigadaRenderLista();
}

function brigadaFecharModal() {
    closeModal('modalBrigada');
}

// Garante que hidden inputs ficam atualizados antes de submeter
document.getElementById('brigadaLoteForm')?.addEventListener('submit', function() {
    // Reinjeta todos os hidden inputs com valores finais
    document.querySelectorAll('.brigada-hidden-input').forEach(el => el.remove());
    const form = this;
    brigadaItens.forEach((item, pos) => {
        [
            ['colaborador_id', item.colaborador_id],
            ['funcao_brigada', item.funcao_brigada],
            ['data_inicio', item.data_inicio],
            ['data_validade_cert', item.data_validade_cert],
        ].forEach(([key, val]) => {
            if (!val) return;
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = `itens[${pos}][${key}]`;
            inp.value = val;
            inp.className = 'brigada-hidden-input';
            form.appendChild(inp);
        });
    });
});

// ── Editar Brigadista ───────────────────────────────────────────────────────
async function brigadaAbrirEdit(id) {
    try {
        const r = await fetch(`/brigada/${id}/edit`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        const d = await r.json();
        document.getElementById('editBrigNome').textContent    = d.nome ?? '—';
        document.getElementById('editBrigSetor').textContent   = d.setor ?? '';
        document.getElementById('editBrigFuncao').value        = d.funcao_brigada ?? '';
        document.getElementById('editBrigInicio').value        = d.data_inicio ?? '';
        document.getElementById('editBrigValidade').value      = d.data_validade_cert ?? '';
        document.getElementById('formEditarBrigadista').action = `/brigada/${id}`;
        openModal('modalEditarBrigadista');
    } catch(e) {
        alert('Erro ao carregar dados do brigadista.');
        console.error(e);
    }
}
</script>
@endpush
@endsection
