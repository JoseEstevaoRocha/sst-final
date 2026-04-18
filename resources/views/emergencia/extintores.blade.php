@extends('layouts.app')
@section('title','Extintores')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Controle de Extintores</h1>
        <p class="page-sub">{{ $extintores->total() }} extintor(es) cadastrado(s)</p>
    </div>
    <div class="flex gap-8 align-center">
        @if(auth()->user()->isSuperAdmin())
        <form method="GET" action="{{ route('extintores.index') }}" style="display:flex;gap:8px;align-items:center">
            <select name="empresa_id" class="filter-select" style="width:220px" onchange="this.form.submit()">
                <option value="">Todas as empresas</option>
                @foreach($empresas as $emp)
                <option value="{{ $emp->id }}" {{ $empresaId == $emp->id ? 'selected' : '' }}>{{ $emp->nome_display }}</option>
                @endforeach
            </select>
        </form>
        @endif
        <button class="btn btn-primary" onclick="abrirModalExtintor()">
            <i class="fas fa-plus"></i> Novo Extintor
        </button>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-row mb-20" style="grid-template-columns:repeat(4,1fr)">
    @foreach([['Total','total','blue'],['Regulares','regulares','green'],['Vencidos','vencidos','red'],['Manutenção','manutencao','yellow']] as [$l,$k,$c])
    <div class="kpi kpi-{{ $c }}"><div class="kpi-label">{{ $l }}</div><div class="kpi-val">{{ $stats[$k]??0 }}</div></div>
    @endforeach
</div>

{{-- Filtros --}}
<div class="card mb-16" style="padding:12px 16px">
    <form method="GET" action="{{ route('extintores.index') }}" class="flex gap-12 flex-wrap align-center">
        @if($empresaId)<input type="hidden" name="empresa_id" value="{{ $empresaId }}">@endif
        <select name="status" class="filter-select" style="width:160px">
            <option value="">Todos os status</option>
            <option value="regular"    {{ request('status')==='regular'    ? 'selected' : '' }}>Regular</option>
            <option value="vencido"    {{ request('status')==='vencido'    ? 'selected' : '' }}>Vencido</option>
            <option value="manutencao" {{ request('status')==='manutencao' ? 'selected' : '' }}>Manutenção</option>
        </select>
        <select name="tipo" class="filter-select" style="width:180px">
            <option value="">Todos os tipos</option>
            @foreach(['agua','po_quimico_seco','co2','espuma','halogenado'] as $t)
            <option value="{{ $t }}" {{ request('tipo')===$t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i> Filtrar</button>
        @if(request('status') || request('tipo'))
        <a href="{{ route('extintores.index', $empresaId ? ['empresa_id'=>$empresaId] : []) }}" class="btn btn-ghost btn-sm">Limpar</a>
        @endif
    </form>
</div>

{{-- Tabela --}}
<div class="card p-0">
<div class="table-wrap"><table class="table">
<thead><tr><th>Nº SÉRIE</th><th>TIPO</th><th>CAPACIDADE</th><th>SETOR</th><th>LOCALIZAÇÃO</th><th>ÚLTIMA RECARGA</th><th>PRÓX. RECARGA</th><th>TESTE HIDROST.</th><th>STATUS</th><th>AÇÕES</th></tr></thead>
<tbody>
@forelse($extintores as $e)
@php $s = $e->status_calculado; @endphp
<tr class="{{ $s==='vencido' ? 'tr-danger' : ($s==='manutencao' ? 'tr-warning' : '') }}">
    <td class="font-mono text-12">{{ $e->numero_serie ?? '—' }}</td>
    <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$e->tipo)) }}</span></td>
    <td class="text-12">{{ $e->capacidade ?? '—' }}</td>
    <td class="text-12">{{ $e->setor?->nome ?? '—' }}</td>
    <td class="text-12">{{ Str::limit($e->localizacao ?? '—', 25) }}</td>
    <td class="font-mono text-12">{{ $e->ultima_recarga?->format('d/m/Y') ?? '—' }}</td>
    <td class="font-mono text-12 {{ $s==='vencido' ? 'text-danger' : '' }}">{{ $e->proxima_recarga?->format('d/m/Y') ?? '—' }}</td>
    <td class="font-mono text-12">{{ $e->proximo_teste_hidrostatico?->format('d/m/Y') ?? '—' }}</td>
    <td><span class="badge {{ $s==='regular' ? 'badge-success' : ($s==='vencido' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($s) }}</span></td>
    <td><div class="flex gap-4">
        <button class="btn btn-secondary btn-icon btn-sm" onclick="editarExtintor({{ $e->id }})" title="Editar">
            <i class="fas fa-pencil-alt"></i>
        </button>
        <form id="del-ext-{{ $e->id }}" method="POST" action="{{ route('extintores.destroy',$e->id) }}" style="display:none">
            @csrf @method('DELETE')
        </form>
        <button type="button" class="btn btn-ghost btn-icon btn-sm text-danger"
            onclick="if(confirm('Excluir este extintor?')) document.getElementById('del-ext-{{ $e->id }}').submit()"
            title="Excluir">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div></td>
</tr>
@empty
<tr><td colspan="10">
    <div class="empty-state py-32">
        <div class="empty-icon"><i class="fas fa-fire-extinguisher"></i></div>
        <h3>Nenhum extintor cadastrado</h3>
        <p>Clique em "Novo Extintor" para cadastrar o primeiro.</p>
    </div>
</td></tr>
@endforelse
</tbody>
</table></div>
<div style="padding:12px 16px">{{ $extintores->withQueryString()->links() }}</div>
</div>

{{-- ── MODAL CRIAR / EDITAR ─────────────────────────────────────────────── --}}
<div id="modalExtintor" class="modal-overlay" style="display:none">
<div class="modal" style="max-width:640px">
    <div class="modal-header">
        <h3 class="modal-title" id="modalExtintorTitulo"><i class="fas fa-fire-extinguisher"></i> Novo Extintor</h3>
        <button class="modal-close" onclick="fecharModalExtintor()">&times;</button>
    </div>
    <form id="formExtintor" method="POST" action="{{ route('extintores.store') }}">
    @csrf
    <span id="methodField"></span>
    <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

            @if(auth()->user()->isSuperAdmin())
            {{-- Empresa (super admin only) --}}
            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Empresa *</label>
                <select name="empresa_id" id="fEmpresaId" class="form-select" onchange="carregarSetoresExtintor(this.value)" required>
                    <option value="">— Selecione a empresa —</option>
                    @foreach($empresas as $emp)
                    <option value="{{ $emp->id }}" {{ $empresaId == $emp->id ? 'selected' : '' }}>{{ $emp->nome_display }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="empresa_id" id="fEmpresaId" value="{{ $empresaId }}">
            @endif

            {{-- Tipo --}}
            <div class="form-group">
                <label class="form-label">Tipo *</label>
                <select name="tipo" id="fTipo" class="form-select" required>
                    <option value="">Selecione</option>
                    <option value="agua">Água</option>
                    <option value="po_quimico_seco">Pó Químico Seco</option>
                    <option value="co2">CO₂</option>
                    <option value="espuma">Espuma</option>
                    <option value="halogenado">Halogenado</option>
                </select>
            </div>

            {{-- Capacidade --}}
            <div class="form-group">
                <label class="form-label">Capacidade</label>
                <input type="text" name="capacidade" id="fCapacidade" class="form-control" placeholder="Ex: 6kg, 4L">
            </div>

            {{-- Nº Série --}}
            <div class="form-group">
                <label class="form-label">Nº de Série</label>
                <input type="text" name="numero_serie" id="fNumeroSerie" class="form-control" placeholder="Ex: EXT-001">
            </div>

            {{-- Setor --}}
            <div class="form-group">
                <label class="form-label">Setor</label>
                <select name="setor_id" id="fSetorId" class="form-select">
                    <option value="">— Nenhum —</option>
                    @foreach($setores as $s)
                    <option value="{{ $s->id }}">{{ $s->nome }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Localização --}}
            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Localização</label>
                <input type="text" name="localizacao" id="fLocalizacao" class="form-control" placeholder="Ex: Corredor A, próximo ao quadro elétrico">
            </div>

            {{-- Última recarga --}}
            <div class="form-group">
                <label class="form-label">Última Recarga</label>
                <input type="date" name="ultima_recarga" id="fUltimaRecarga" class="form-control">
            </div>

            {{-- Próxima recarga --}}
            <div class="form-group">
                <label class="form-label">Próxima Recarga</label>
                <input type="date" name="proxima_recarga" id="fProximaRecarga" class="form-control">
            </div>

            {{-- Último teste --}}
            <div class="form-group">
                <label class="form-label">Último Teste Hidrostático</label>
                <input type="date" name="ultimo_teste_hidrostatico" id="fUltimoTeste" class="form-control">
            </div>

            {{-- Próximo teste --}}
            <div class="form-group">
                <label class="form-label">Próximo Teste Hidrostático</label>
                <input type="date" name="proximo_teste_hidrostatico" id="fProximoTeste" class="form-control">
            </div>

            {{-- Status --}}
            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Status</label>
                <select name="status" id="fStatus" class="form-select">
                    <option value="regular">Regular</option>
                    <option value="manutencao">Em Manutenção</option>
                    <option value="vencido">Vencido</option>
                </select>
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="fecharModalExtintor()">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
    </div>
    </form>
</div>
</div>

{{-- Dados dos extintores para edição via JS --}}
@php
$extintoresData = $extintores->getCollection()->map(fn($e) => [
    'id'                           => $e->id,
    'numero_serie'                 => $e->numero_serie,
    'tipo'                         => $e->tipo,
    'capacidade'                   => $e->capacidade,
    'localizacao'                  => $e->localizacao,
    'empresa_id'                   => $e->empresa_id,
    'setor_id'                     => $e->setor_id,
    'ultima_recarga'               => $e->ultima_recarga?->format('Y-m-d'),
    'proxima_recarga'              => $e->proxima_recarga?->format('Y-m-d'),
    'ultimo_teste_hidrostatico'    => $e->ultimo_teste_hidrostatico?->format('Y-m-d'),
    'proximo_teste_hidrostatico'   => $e->proximo_teste_hidrostatico?->format('Y-m-d'),
    'status'                       => $e->status,
])->keyBy('id');
@endphp

@endsection
@push('scripts')
<script>
const EXTINTORES = @json($extintoresData);
const IS_SUPER_ADMIN = {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }};
const EMPRESA_ID_ATUAL = {{ $empresaId ? $empresaId : 'null' }};

function carregarSetoresExtintor(empresaId, selecionarId = null) {
    const sel = document.getElementById('fSetorId');
    sel.innerHTML = '<option value="">— Nenhum —</option>';
    if (!empresaId) return;
    fetch(`/api/setores?empresa_id=${empresaId}`)
        .then(r => r.json())
        .then(data => {
            data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.nome;
                if (selecionarId && s.id == selecionarId) opt.selected = true;
                sel.appendChild(opt);
            });
        });
}

function abrirModalExtintor() {
    document.getElementById('modalExtintorTitulo').innerHTML = '<i class="fas fa-fire-extinguisher"></i> Novo Extintor';
    document.getElementById('formExtintor').action = '{{ route('extintores.store') }}';
    document.getElementById('methodField').innerHTML = '';
    // Limpa campos
    ['fTipo','fCapacidade','fNumeroSerie','fLocalizacao','fUltimaRecarga','fProximaRecarga','fUltimoTeste','fProximoTeste'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('fStatus').value = 'regular';

    if (IS_SUPER_ADMIN) {
        const empSel = document.getElementById('fEmpresaId');
        empSel.value = EMPRESA_ID_ATUAL ?? '';
        // Se já tem empresa selecionada, carrega setores dela
        if (EMPRESA_ID_ATUAL) {
            carregarSetoresExtintor(EMPRESA_ID_ATUAL);
        } else {
            document.getElementById('fSetorId').innerHTML = '<option value="">— Nenhum —</option>';
        }
    }

    document.getElementById('modalExtintor').style.display = 'flex';
}

function editarExtintor(id) {
    const d = EXTINTORES[id];
    if (!d) return;
    document.getElementById('modalExtintorTitulo').innerHTML = '<i class="fas fa-pencil-alt"></i> Editar Extintor';
    document.getElementById('formExtintor').action = `/extintores/${id}`;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('fTipo').value            = d.tipo ?? '';
    document.getElementById('fCapacidade').value      = d.capacidade ?? '';
    document.getElementById('fNumeroSerie').value     = d.numero_serie ?? '';
    document.getElementById('fLocalizacao').value     = d.localizacao ?? '';
    document.getElementById('fUltimaRecarga').value   = d.ultima_recarga ?? '';
    document.getElementById('fProximaRecarga').value  = d.proxima_recarga ?? '';
    document.getElementById('fUltimoTeste').value     = d.ultimo_teste_hidrostatico ?? '';
    document.getElementById('fProximoTeste').value    = d.proximo_teste_hidrostatico ?? '';
    document.getElementById('fStatus').value          = d.status ?? 'regular';

    if (IS_SUPER_ADMIN) {
        // Seta empresa e carrega setores dinamicamente
        const empId = d.empresa_id ?? EMPRESA_ID_ATUAL ?? '';
        document.getElementById('fEmpresaId').value = empId;
        carregarSetoresExtintor(empId, d.setor_id);
    } else {
        document.getElementById('fSetorId').value = d.setor_id ?? '';
    }

    document.getElementById('modalExtintor').style.display = 'flex';
}

function fecharModalExtintor() {
    document.getElementById('modalExtintor').style.display = 'none';
}

// Fecha modal ao clicar fora
document.getElementById('modalExtintor').addEventListener('click', function(e) {
    if (e.target === this) fecharModalExtintor();
});

// Se super admin e já tem empresa selecionada, pré-carrega setores na abertura da página
if (IS_SUPER_ADMIN && EMPRESA_ID_ATUAL) {
    carregarSetoresExtintor(EMPRESA_ID_ATUAL);
}
</script>
@endpush
