@extends('layouts.app')
@section('title','Exames Clínicos')
@php
$tipos = \App\Models\ExameClinico::TIPOS;
$badgeTipo = ['audiometria'=>'badge-info','laboratorial'=>'badge-warning','imagem'=>'badge-secondary','clinico'=>'badge-success','espirometria'=>'badge-info','ecg'=>'badge-danger','outros'=>'badge-secondary'];
@endphp
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Exames Clínicos</h1>
        <p class="page-sub">Catálogo de exames para associação a setores e funções</p>
    </div>
    <div class="flex gap-8">
        <button class="btn btn-secondary" onclick="openModal('modalLote')">
            <i class="fas fa-layer-group"></i> Atribuir a Funções
        </button>
        <button class="btn btn-primary" onclick="openModal('modalExame')">
            <i class="fas fa-plus"></i> Novo Exame
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-16">{{ session('success') }}</div>
@endif

{{-- Filtros --}}
<form method="GET">
<div class="filter-bar mb-16">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-select" placeholder="Buscar exame..." style="width:220px">
    <select name="tipo" class="filter-select" onchange="this.form.submit()">
        <option value="">Todos os tipos</option>
        @foreach($tipos as $v=>$l)
        <option value="{{ $v }}" {{ request('tipo')===$v?'selected':'' }}>{{ $l }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
    @if(request()->hasAny(['search','tipo']))
    <a href="{{ route('exames-clinicos.index') }}" class="btn btn-ghost btn-sm">✕ Limpar</a>
    @endif
</div>
</form>

<div class="card p-0">
    <div class="table-wrap"><table class="table">
    <thead><tr><th>EXAME</th><th>TIPO</th><th>NR REFERÊNCIA</th><th>DESCRIÇÃO</th><th>SETORES</th><th>FUNÇÕES</th><th>AÇÕES</th></tr></thead>
    <tbody>
    @forelse($exames as $ex)
    <tr>
        <td class="font-bold text-13">{{ $ex->nome }}</td>
        <td><span class="badge {{ $badgeTipo[$ex->tipo]??'badge-secondary' }}">{{ $tipos[$ex->tipo]??ucfirst($ex->tipo) }}</span></td>
        <td class="text-12 font-mono">{{ $ex->obrigatorio_nr ?? '—' }}</td>
        <td class="text-12" style="max-width:200px;white-space:normal">{{ $ex->descricao ?? '—' }}</td>
        <td class="text-12">{{ $ex->setores()->count() }}</td>
        <td class="text-12">{{ $ex->funcoes()->count() }}</td>
        <td>
            <div class="flex gap-4">
                <button onclick="editExame({{ json_encode(['id'=>$ex->id,'nome'=>$ex->nome,'tipo'=>$ex->tipo,'descricao'=>$ex->descricao,'obrigatorio_nr'=>$ex->obrigatorio_nr]) }})"
                    class="btn btn-secondary btn-icon"><i class="fas fa-pencil-alt"></i></button>
                <form method="POST" action="{{ route('exames-clinicos.destroy',$ex->id) }}" style="display:inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir '{{ $ex->nome }}'?"><i class="fas fa-trash-alt"></i></button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-stethoscope"></i></div>
            <h3>Nenhum exame cadastrado</h3>
            <p class="text-muted">Clique em "Novo Exame" para começar.</p>
        </div>
    </td></tr>
    @endforelse
    </tbody></table></div>
    @if($exames->hasPages())
    <div style="padding:12px 20px">{{ $exames->links() }}</div>
    @endif
</div>

{{-- Modal Atribuição em Lote --}}
<div class="modal-overlay" id="modalLote">
<div class="modal" style="max-width:700px">
<div class="modal-header">
    <div class="modal-title"><i class="fas fa-layer-group"></i> Atribuir Exames a Múltiplas Funções</div>
    <button class="modal-close" onclick="closeModal('modalLote')"><i class="fas fa-times"></i></button>
</div>
<div class="modal-body">
<form method="POST" action="{{ route('exames-clinicos.lote') }}">@csrf
@php
    $todasEmpresas = \App\Models\Empresa::ativas()->orderBy('razao_social')->get();
    $todosExames   = \App\Models\ExameClinico::orderBy('nome')->get();
@endphp
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    {{-- Exames --}}
    <div>
        <label class="form-label" style="margin-bottom:8px">Exames * <span style="font-weight:400;color:var(--text-3)">(selecione um ou mais)</span></label>
        <input type="text" id="buscaExame" placeholder="Filtrar exames..." class="form-control" style="margin-bottom:8px" oninput="filtrarLista('listaExames', this.value)">
        <div id="listaExames" style="border:1.5px solid var(--border);border-radius:var(--r-sm);max-height:260px;overflow-y:auto">
            @foreach($todosExames as $ex)
            <label style="display:flex;align-items:center;gap:10px;padding:9px 12px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .1s" onmouseover="this.style.background='var(--bg-alt)'" onmouseout="this.style.background=''">
                <input type="checkbox" name="exames_ids[]" value="{{ $ex->id }}" style="width:15px;height:15px;accent-color:var(--brand)">
                <span style="font-size:13px">{{ $ex->nome }}</span>
                <span class="badge badge-secondary" style="font-size:10px;margin-left:auto">{{ \App\Models\ExameClinico::TIPOS[$ex->tipo] ?? $ex->tipo }}</span>
            </label>
            @endforeach
        </div>
        <button type="button" onclick="toggleAll('listaExames', true)" class="btn btn-ghost btn-sm" style="margin-top:6px">Marcar todos</button>
        <button type="button" onclick="toggleAll('listaExames', false)" class="btn btn-ghost btn-sm" style="margin-top:6px">Desmarcar</button>
    </div>

    {{-- Funções --}}
    <div>
        <label class="form-label" style="margin-bottom:8px">Funções * <span style="font-weight:400;color:var(--text-3)">(selecione uma ou mais)</span></label>
        <div style="display:flex;gap:6px;margin-bottom:6px">
            <select id="loteEmpresa" class="filter-select" style="flex:1" onchange="onLoteEmpresaChange(this.value)">
                <option value="">Todas as empresas</option>
                @foreach($todasEmpresas as $e)
                <option value="{{ $e->id }}">{{ $e->nome_display }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:6px;margin-bottom:8px">
            <select id="loteSetor" class="filter-select" style="flex:1" onchange="filtrarFuncoesLote()">
                <option value="">Todos os setores</option>
            </select>
        </div>
        <input type="text" id="buscaFuncao" placeholder="Filtrar funções..." class="form-control" style="margin-bottom:8px" oninput="filtrarFuncoesLote()">
        <div id="listaFuncoes" style="border:1.5px solid var(--border);border-radius:var(--r-sm);max-height:220px;overflow-y:auto">
            @foreach(\App\Models\Funcao::with(['setor','empresa'])->orderBy('nome')->get() as $f)
            <label style="display:flex;align-items:center;gap:10px;padding:9px 12px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .1s"
                data-empresa="{{ $f->empresa_id }}"
                data-setor="{{ $f->setor_id }}"
                data-nome="{{ strtolower($f->nome) }}"
                onmouseover="this.style.background='var(--bg-alt)'" onmouseout="this.style.background=''">
                <input type="checkbox" name="funcoes_ids[]" value="{{ $f->id }}" style="width:15px;height:15px;accent-color:var(--brand)">
                <div>
                    <div style="font-size:13px;font-weight:600">{{ $f->nome }}</div>
                    <div style="font-size:11px;color:var(--text-3)">{{ $f->setor?->nome ?? '—' }} · {{ $f->empresa?->nome_display ?? '—' }}</div>
                </div>
            </label>
            @endforeach
        </div>
        <button type="button" onclick="toggleAll('listaFuncoes', true)" class="btn btn-ghost btn-sm" style="margin-top:6px">Marcar todos</button>
        <button type="button" onclick="toggleAll('listaFuncoes', false)" class="btn btn-ghost btn-sm" style="margin-top:6px">Desmarcar</button>
    </div>
</div>

{{-- Opções --}}
<div style="display:flex;gap:16px;margin-top:16px;padding:12px;background:var(--bg-alt);border-radius:var(--r-sm);align-items:center;flex-wrap:wrap">
    <div class="form-group" style="margin:0;flex:1;min-width:140px">
        <label class="form-label">Periodicidade (meses)</label>
        <input type="number" name="periodicidade_meses" class="form-control" placeholder="Ex: 12 (opcional)">
    </div>
    <label class="checkbox-wrap" style="margin:0">
        <input type="checkbox" name="obrigatorio" value="1" checked>
        <span class="checkbox-box"></span>
        <span style="font-size:13px">Obrigatório</span>
    </label>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-ghost" onclick="closeModal('modalLote')">Cancelar</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Atribuir exames às funções selecionadas</button>
</div>
</form>
</div>
</div>
</div>

{{-- Modal Novo/Editar Exame --}}
<div class="modal-overlay" id="modalExame">
<div class="modal modal-sm">
<div class="modal-header">
    <div class="modal-title" id="exameTitle"><i class="fas fa-stethoscope"></i> Novo Exame Clínico</div>
    <button class="modal-close" onclick="closeModal('modalExame')"><i class="fas fa-times"></i></button>
</div>
<div class="modal-body">
<form method="POST" id="exameForm" action="{{ route('exames-clinicos.store') }}">@csrf
<div id="exameMethod"></div>
<div class="flex flex-col gap-14">
    <div class="form-group">
        <label class="form-label">Nome *</label>
        <input type="text" name="nome" id="eName" class="form-control" required placeholder="Ex: Audiometria Tonal">
    </div>
    <div class="form-group">
        <label class="form-label">Tipo *</label>
        <select name="tipo" id="eTipo" class="form-select" required>
            @foreach($tipos as $v=>$l)
            <option value="{{ $v }}">{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">NR de Referência</label>
        <input type="text" name="obrigatorio_nr" id="eNr" class="form-control" placeholder="Ex: NR-7, NR-15">
    </div>
    <div class="form-group">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" id="eDesc" class="form-control" rows="2" placeholder="Detalhes do exame..."></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" onclick="closeModal('modalExame')">Cancelar</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
</div>
</form>
</div>
</div>
</div>

@endsection
@push('scripts')
<script>
// ── Lote: filtrar lista por texto ─────────────────────────────────
function filtrarLista(listaId, termo) {
    document.querySelectorAll(`#${listaId} label`).forEach(label => {
        const txt = label.textContent.toLowerCase();
        label.style.display = txt.includes(termo.toLowerCase()) ? '' : 'none';
    });
}

// ── Lote: marcar/desmarcar todos visíveis ─────────────────────────
function toggleAll(listaId, check) {
    document.querySelectorAll(`#${listaId} label`).forEach(label => {
        if (label.style.display !== 'none') {
            const cb = label.querySelector('input[type=checkbox]');
            if (cb) cb.checked = check;
        }
    });
}

// ── Lote: empresa muda → recarrega setores via AJAX, depois filtra ──
async function onLoteEmpresaChange(eid) {
    const setorSel = document.getElementById('loteSetor');
    setorSel.innerHTML = '<option value="">Todos os setores</option>';
    if (eid) {
        try {
            const r = await fetch(`/api/setores?empresa_id=${eid}`, { headers: {'X-Requested-With':'XMLHttpRequest'} });
            const data = await r.json();
            data.forEach(s => {
                const o = document.createElement('option');
                o.value = s.id;
                o.textContent = s.nome;
                setorSel.appendChild(o);
            });
        } catch(e) {}
    }
    filtrarFuncoesLote();
}

// ── Lote: filtra funções por empresa + setor + texto ─────────────
function filtrarFuncoesLote() {
    const eid   = document.getElementById('loteEmpresa').value;
    const sid   = document.getElementById('loteSetor').value;
    const termo = document.getElementById('buscaFuncao').value.toLowerCase();

    document.querySelectorAll('#listaFuncoes label').forEach(label => {
        const matchEmp   = !eid   || label.dataset.empresa === eid;
        const matchSetor = !sid   || label.dataset.setor   === sid;
        const matchNome  = !termo || label.dataset.nome.includes(termo);
        const show = matchEmp && matchSetor && matchNome;
        label.style.display = show ? '' : 'none';
        if (!show) {
            const cb = label.querySelector('input[type=checkbox]');
            if (cb) cb.checked = false;
        }
    });
}

function editExame(e) {
    document.getElementById('exameTitle').innerHTML = '<i class="fas fa-stethoscope"></i> Editar Exame';
    document.getElementById('exameForm').action = `/exames-clinicos/${e.id}`;
    document.getElementById('exameMethod').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('eName').value = e.nome;
    document.getElementById('eTipo').value = e.tipo;
    document.getElementById('eNr').value = e.obrigatorio_nr || '';
    document.getElementById('eDesc').value = e.descricao || '';
    openModal('modalExame');
}
</script>
@endpush
