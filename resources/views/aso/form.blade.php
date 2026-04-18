@extends('layouts.app')
@section('title', $aso ? 'Editar ASO' : 'Novo ASO')
@section('content')

@php
    $preColaborador = $preColaborador ?? null;
    $preEmpresaId   = $preColaborador?->empresa_id ?? $aso?->empresa_id ?? auth()->user()->empresa_id;
    $preColabId     = $preColaborador?->id ?? $aso?->colaborador_id;
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $aso ? 'Editar ASO' : 'Novo ASO' }}</h1>
        @if($preColaborador && !$aso)
        <p class="page-sub" style="color:var(--brand)"><i class="fas fa-user-check"></i> Agendando para: <strong>{{ $preColaborador->nome }}</strong> — {{ $preColaborador->funcao?->nome ?? '—' }} / {{ $preColaborador->setor?->nome ?? '—' }}</p>
        @endif
    </div>
    @php $voltarUrl = url()->previous() && str_contains(url()->previous(), 'agendamento') ? route('asos.agendamento') : route('asos.index'); @endphp
    <a href="{{ $voltarUrl }}" class="btn btn-secondary">← Voltar</a>
</div>

@if(session('info'))
<div class="alert" style="background:rgba(37,99,235,.1);border:1px solid rgba(37,99,235,.3);color:var(--brand);padding:12px 16px;border-radius:var(--r-sm);margin-bottom:16px;font-size:13px;font-weight:500">
    <i class="fas fa-info-circle"></i> {{ session('info') }}
</div>
@endif

<div class="card">
    <form method="POST" action="{{ $aso ? route('asos.update',$aso->id) : route('asos.store') }}">
        @csrf @if($aso) @method('PUT') @endif
        <div class="form-grid">
            <div class="form-section"><i class="fas fa-building"></i> Empresa e Colaborador</div>
            <div class="form-group">
                <label class="form-label">Empresa <span class="required">*</span></label>
                <select name="empresa_id" id="empresa_id" class="form-select" required>
                    <option value="">Selecione</option>
                    @foreach($empresas as $e)<option value="{{ $e->id }}" {{ old('empresa_id',$preEmpresaId)==$e->id?'selected':'' }}>{{ $e->nome_display }}</option>@endforeach
                </select>
            </div>

            {{-- Filtros para colaborador --}}
            <div class="form-group" id="filtrosColabWrap" style="{{ $preEmpresaId ? '' : 'display:none' }}">
                <label class="form-label">Filtrar colaborador por</label>
                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:6px">
                    <select id="filtroSetor" class="form-select" style="font-size:12px" onchange="filtrarColaboradores()">
                        <option value="">Todos os setores</option>
                    </select>
                    <select id="filtroFuncao" class="form-select" style="font-size:12px" onchange="filtrarColaboradores()">
                        <option value="">Todas as funções</option>
                    </select>
                    <div style="position:relative">
                        <i class="fas fa-search" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:11px;pointer-events:none"></i>
                        <input type="text" id="filtroNome" class="form-control" style="padding-left:28px;font-size:12px" placeholder="Buscar nome..." oninput="filtrarColaboradores()">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Colaborador <span class="required">*</span></label>
                <select name="colaborador_id" id="colaboradorSelect" class="form-select" required>
                    <option value="">Selecione a empresa primeiro</option>
                    @if($preColaborador && !$aso)
                        <option value="{{ $preColaborador->id }}" selected>{{ $preColaborador->nome }}</option>
                    @elseif($aso)
                        <option value="{{ $aso->colaborador_id }}" selected>{{ $aso->colaborador?->nome }}</option>
                    @endif
                </select>
            </div>
            <div class="form-section"><i class="fas fa-clipboard-list"></i> Dados do ASO</div>
            <div class="form-group">
                <label class="form-label">Tipo <span class="required">*</span></label>
                <select name="tipo" class="form-select" required>
                    @php $tipoDefault = $preColaborador && !$aso ? 'admissional' : ($aso?->tipo ?? ''); @endphp
                    @foreach(['admissional'=>'Admissional','periodico'=>'Periódico','demissional'=>'Demissional','retorno'=>'Retorno ao Trabalho','mudanca_funcao'=>'Mudança de Função'] as $v=>$l)
                    <option value="{{ $v }}" {{ old('tipo',$tipoDefault)===$v?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Resultado</label>
                <select name="resultado" class="form-select">
                    @foreach(['pendente'=>'Pendente','apto'=>'Apto','apto_restricoes'=>'Apto com Restrições','inapto'=>'Inapto'] as $v=>$l)
                    <option value="{{ $v }}" {{ old('resultado',$aso?->resultado??'pendente')===$v?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Data do Exame</label>
                <input type="date" name="data_exame" id="data_exame" value="{{ old('data_exame',$aso?->data_exame?->format('Y-m-d')??'') }}" class="form-control">
            </div>
            <div class="form-group" id="wrap_data_vencimento">
                <label class="form-label">
                    Data de Vencimento
                    <span style="font-size:10px;color:var(--text-3);font-weight:400;margin-left:4px">(preenchida automaticamente: +365 dias)</span>
                </label>
                <input type="date" name="data_vencimento" id="data_vencimento" value="{{ old('data_vencimento',$aso?->data_vencimento?->format('Y-m-d')??'') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Clínica</label>
                <select name="clinica_id" class="form-select">
                    <option value="">Selecione ou digite abaixo</option>
                    @foreach($clinicas as $cl)<option value="{{ $cl->id }}" {{ old('clinica_id',$aso?->clinica_id??'')==$cl->id?'selected':'' }}>{{ $cl->nome }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Nome da Clínica (manual)</label>
                <input type="text" name="clinica_nome" value="{{ old('clinica_nome',$aso?->clinica_nome??'') }}" class="form-control" placeholder="Ou digite o nome da clínica">
            </div>
            <div class="form-group">
                <label class="form-label">Médico Responsável</label>
                @php $medicos = \App\Models\Medico::ativos()->orderBy('nome')->get(); @endphp
                <select name="_medico_select" id="medicoSelect" class="form-select" style="margin-bottom:6px">
                    <option value="">Selecionar médico cadastrado...</option>
                    @foreach($medicos as $med)
                    <option value="{{ $med->nome_com_crm }}" {{ old('medico_responsavel',$aso?->medico_responsavel??'')===$med->nome_com_crm?'selected':'' }}>
                        {{ $med->nome_com_crm }}{{ $med->clinica ? ' ('.$med->clinica->nome.')' : '' }}
                    </option>
                    @endforeach
                </select>
                <input type="text" name="medico_responsavel" id="medicoTexto" value="{{ old('medico_responsavel',$aso?->medico_responsavel??'') }}" class="form-control" placeholder="Ou digite o nome do médico manualmente">
                <div style="font-size:10px;color:var(--text-3);margin-top:4px">Selecione da lista ou preencha manualmente</div>
            </div>
            <div class="form-group">
                <label class="form-label">Status Logístico</label>
                <select name="status_logistico" class="form-select">
                    @foreach(['pendente'=>'Pendente','agendado'=>'Agendado','em_atendimento'=>'Em Atendimento','finalizado'=>'Finalizado','em_transito'=>'Em Trânsito','recebido_empresa'=>'Recebido Empresa','entregue_colaborador'=>'Entregue Colaborador'] as $v=>$l)
                    <option value="{{ $v }}" {{ old('status_logistico',$aso?->status_logistico??'pendente')===$v?'selected':'' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Observações</label>
                <textarea name="observacoes" class="form-control" rows="2">{{ old('observacoes',$aso?->observacoes??'') }}</textarea>
            </div>
        </div>
        <div class="form-footer">
            <a href="{{ route('asos.index') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>
const preColabId = '{{ $preColabId ?? '' }}';
let todosColaboradores = [];

async function loadColaboradores(empresaId, selectId = '') {
    const sel = document.getElementById('colaboradorSelect');
    const wrap = document.getElementById('filtrosColabWrap');
    if (!empresaId) {
        sel.innerHTML = '<option value="">Selecione a empresa primeiro</option>';
        if (wrap) wrap.style.display = 'none';
        return;
    }
    sel.innerHTML = '<option value="">Carregando...</option>';
    const res  = await fetch(`/api/colaboradores?empresa_id=${empresaId}`, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
    todosColaboradores = await res.json();

    // Popula filtros de setor e função
    const setores  = [...new Map(todosColaboradores.filter(c=>c.setor).map(c=>[c.setor,c.setor])).values()].sort();
    const funcoes  = [...new Map(todosColaboradores.filter(c=>c.funcao).map(c=>[c.funcao,c.funcao])).values()].sort();
    const sEl = document.getElementById('filtroSetor');
    const fEl = document.getElementById('filtroFuncao');
    sEl.innerHTML = '<option value="">Todos os setores</option>' + setores.map(s=>`<option value="${s}">${s}</option>`).join('');
    fEl.innerHTML = '<option value="">Todas as funções</option>' + funcoes.map(f=>`<option value="${f}">${f}</option>`).join('');
    if (wrap) wrap.style.display = '';

    renderColaboradores(selectId);
}

function renderColaboradores(selectId = '') {
    const setor  = document.getElementById('filtroSetor')?.value || '';
    const funcao = document.getElementById('filtroFuncao')?.value || '';
    const nome   = (document.getElementById('filtroNome')?.value || '').toLowerCase();
    const sel    = document.getElementById('colaboradorSelect');
    const prev   = selectId || sel.value;

    const filtrados = todosColaboradores.filter(c =>
        (!setor  || c.setor  === setor)  &&
        (!funcao || c.funcao === funcao) &&
        (!nome   || c.nome.toLowerCase().includes(nome))
    );

    sel.innerHTML = `<option value="">Selecione (${filtrados.length} encontrado${filtrados.length!==1?'s':''})</option>`;
    filtrados.forEach(c => {
        const o = document.createElement('option');
        o.value = c.id;
        o.textContent = c.nome + (c.setor ? ' — '+c.setor : '');
        if (String(c.id) === String(prev)) o.selected = true;
        sel.appendChild(o);
    });
}

function filtrarColaboradores() { renderColaboradores(); }

document.getElementById('empresa_id')?.addEventListener('change', function() {
    document.getElementById('filtroSetor').value  = '';
    document.getElementById('filtroFuncao').value = '';
    document.getElementById('filtroNome').value   = '';
    loadColaboradores(this.value);
});

(function() {
    const empId = document.getElementById('empresa_id')?.value;
    if (empId) loadColaboradores(empId, preColabId);
})();

// ── Tipo → mostrar/ocultar data vencimento ────────────────────────────────
function toggleVencimento() {
    const tipo = document.querySelector('select[name="tipo"]')?.value;
    const wrap = document.getElementById('wrap_data_vencimento');
    const input = document.getElementById('data_vencimento');
    if (!wrap) return;
    if (tipo === 'demissional') {
        wrap.style.display = 'none';
        input.value = '';
        input.removeAttribute('required');
    } else {
        wrap.style.display = '';
    }
}
document.querySelector('select[name="tipo"]')?.addEventListener('change', toggleVencimento);
toggleVencimento(); // executa ao carregar

// ── Data exame → auto-preenche vencimento +365 dias ───────────────────────
document.getElementById('data_exame')?.addEventListener('change', function() {
    const tipo = document.querySelector('select[name="tipo"]')?.value;
    if (tipo === 'demissional') return;
    const venc = document.getElementById('data_vencimento');
    if (!venc.value && this.value) {
        const d = new Date(this.value + 'T00:00:00');
        d.setFullYear(d.getFullYear() + 1);
        venc.value = d.toISOString().split('T')[0];
    }
});

// ── Médico: select preenche campo texto ───────────────────────────────────
document.getElementById('medicoSelect')?.addEventListener('change', function() {
    if (this.value) document.getElementById('medicoTexto').value = this.value;
});
</script>
@endpush
