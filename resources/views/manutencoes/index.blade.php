@extends('layouts.app')
@section('title','Manutenções NR12')
@php
$tipos = [
    'preventiva'            => ['label'=>'Preventiva',             'badge'=>'badge-info'],
    'corretiva'             => ['label'=>'Corretiva',              'badge'=>'badge-danger'],
    'preditiva'             => ['label'=>'Preditiva',              'badge'=>'badge-warning'],
    'inspecao'              => ['label'=>'Inspeção NR12',          'badge'=>'badge-secondary'],
    'prensa_preventiva'     => ['label'=>'Prensa — Preventiva',    'badge'=>'badge-info'],
    'prensa_corretiva'      => ['label'=>'Prensa — Corretiva',     'badge'=>'badge-danger'],
    'ferramenta_preventiva' => ['label'=>'Ferramenta — Preventiva','badge'=>'badge-info'],
    'ferramenta_corretiva'  => ['label'=>'Ferramenta — Corretiva', 'badge'=>'badge-danger'],
];
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Manutenções NR12</h1>
        <p class="page-sub">Histórico e registro de manutenções</p>
    </div>
    <div class="flex gap-8">
        <button class="btn btn-secondary" onclick="openModal('modalImportar')">
            <i class="fas fa-file-import"></i> Importar CSV
        </button>
        <button class="btn btn-secondary" onclick="openModal('modalLote')">
            <i class="fas fa-layer-group"></i> Registrar em Lote
        </button>
        <button class="btn btn-primary" onclick="openModal('modalManutencao')">
            <i class="fas fa-plus"></i> Nova Manutenção
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-16">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-16">{{ session('error') }}</div>
@endif

{{-- KPIs --}}
<div class="kpi-row mb-20" style="grid-template-columns:repeat(4,1fr)">
    <div class="kpi kpi-blue"><div class="kpi-label">Total</div><div class="kpi-val">{{ $stats['total'] }}</div></div>
    <div class="kpi kpi-green"><div class="kpi-label">Preventivas</div><div class="kpi-val">{{ $stats['preventiva'] }}</div></div>
    <div class="kpi kpi-red"><div class="kpi-label">Corretivas</div><div class="kpi-val">{{ $stats['corretiva'] }}</div></div>
    <div class="kpi kpi-yellow"><div class="kpi-label">Inspeções NR12</div><div class="kpi-val">{{ $stats['inspecao'] }}</div></div>
</div>

{{-- Filtros --}}
<form method="GET" id="formFiltros">
{{-- preserva sorts atuais --}}
@foreach($currentSorts as $col => $dir)
<input type="hidden" name="sorts[{{ $col }}]" value="{{ $dir }}">
@endforeach

<div class="filter-bar mb-8" style="flex-wrap:wrap;gap:8px;align-items:center">
    @if(auth()->user()->hasRole('super-admin'))
    <select name="empresa_id" class="filter-select" style="width:190px" onchange="this.form.submit()">
        <option value="">Todas as empresas</option>
        @foreach($empresas as $emp)
        <option value="{{ $emp->id }}" {{ request('empresa_id')==$emp->id?'selected':'' }}>{{ $emp->nome_display }}</option>
        @endforeach
    </select>
    @endif

    {{-- Máquina --}}
    <select name="maquina_id" class="filter-select" style="width:190px" onchange="this.form.submit()">
        <option value="">Todas as máquinas</option>
        @foreach($maquinasFiltro as $maq)
        <option value="{{ $maq->id }}" {{ request('maquina_id')==$maq->id?'selected':'' }}>{{ $maq->nome }}</option>
        @endforeach
    </select>

    {{-- Setor --}}
    <select name="setor_id" class="filter-select" style="width:160px" onchange="this.form.submit()">
        <option value="">Todos os setores</option>
        @foreach($setores as $s)
        <option value="{{ $s->id }}" {{ request('setor_id')==$s->id?'selected':'' }}>{{ $s->nome }}</option>
        @endforeach
    </select>

    {{-- Tipo --}}
    <select name="tipo" class="filter-select" style="width:200px" onchange="this.form.submit()">
        <option value="">Todos os tipos</option>
        <optgroup label="Geral">
            <option value="preventiva" {{ request('tipo')==='preventiva'?'selected':'' }}>Preventiva</option>
            <option value="corretiva" {{ request('tipo')==='corretiva'?'selected':'' }}>Corretiva</option>
            <option value="preditiva" {{ request('tipo')==='preditiva'?'selected':'' }}>Preditiva</option>
            <option value="inspecao" {{ request('tipo')==='inspecao'?'selected':'' }}>Inspeção NR12</option>
        </optgroup>
        <optgroup label="Prensa">
            <option value="prensa_preventiva" {{ request('tipo')==='prensa_preventiva'?'selected':'' }}>Prensa — Preventiva</option>
            <option value="prensa_corretiva" {{ request('tipo')==='prensa_corretiva'?'selected':'' }}>Prensa — Corretiva</option>
        </optgroup>
        <optgroup label="Ferramenta">
            <option value="ferramenta_preventiva" {{ request('tipo')==='ferramenta_preventiva'?'selected':'' }}>Ferramenta — Preventiva</option>
            <option value="ferramenta_corretiva" {{ request('tipo')==='ferramenta_corretiva'?'selected':'' }}>Ferramenta — Corretiva</option>
        </optgroup>
    </select>

    {{-- Mecânico --}}
    @if($mecanicosFiltro->count())
    <select name="mecanico_id" class="filter-select" style="width:180px" onchange="this.form.submit()">
        <option value="">Todos os mecânicos</option>
        @foreach($mecanicosFiltro as $mec)
        <option value="{{ $mec->colaborador_id }}" {{ request('mecanico_id')==$mec->colaborador_id?'selected':'' }}>
            {{ $mec->colaborador?->nome ?? '—' }}
        </option>
        @endforeach
    </select>
    @endif
</div>

{{-- Segunda linha: intervalo de datas + botão limpar --}}
<div class="filter-bar mb-16" style="flex-wrap:wrap;gap:8px;align-items:center">
    <span style="font-size:12px;color:var(--text-muted);white-space:nowrap">Período:</span>
    <input type="date" name="data_inicio" class="filter-select" style="width:145px"
        value="{{ request('data_inicio') }}" onchange="this.form.submit()">
    <span style="font-size:12px;color:var(--text-muted)">até</span>
    <input type="date" name="data_fim" class="filter-select" style="width:145px"
        value="{{ request('data_fim') }}" onchange="this.form.submit()">

    @php $temFiltro = request()->hasAny(['empresa_id','setor_id','tipo','maquina_id','mecanico_id','data_inicio','data_fim']); @endphp
    @if($temFiltro)
    <a href="{{ route('manutencoes.index') }}" class="btn btn-ghost btn-sm">✕ Limpar filtros</a>
    @endif

    @if($temFiltro)
    <span style="font-size:11px;color:var(--text-muted);padding:4px 10px;background:var(--bg-secondary);border-radius:20px">
        {{ $manutencoes->total() }} registro(s) encontrado(s)
    </span>
    @endif
</div>
</form>

{{-- Tabela --}}
<div class="card p-0">
    <div class="table-wrap"><table class="table">
    <thead><tr>
        <th class="th-sort" onclick="toggleSort('maquina',event)" title="Ordenar por máquina (Shift+clique para adicionar à ordenação)">
            MÁQUINA <span class="sort-ind" id="si-maquina"></span>
        </th>
        <th>SETOR</th>
        <th class="th-sort" onclick="toggleSort('tipo',event)" title="Ordenar por tipo (Shift+clique para adicionar à ordenação)">
            TIPO <span class="sort-ind" id="si-tipo"></span>
        </th>
        <th class="th-sort" onclick="toggleSort('data_manutencao',event)" title="Ordenar por data (Shift+clique para adicionar à ordenação)">
            DATA <span class="sort-ind" id="si-data_manutencao"></span>
        </th>
        <th class="th-sort" onclick="toggleSort('duracao_minutos',event)" title="Ordenar por duração (Shift+clique para adicionar à ordenação)">
            DURAÇÃO <span class="sort-ind" id="si-duracao_minutos"></span>
        </th>
        <th>RESPONSÁVEL</th><th>DESCRIÇÃO</th><th></th>
    </tr></thead>
    <tbody>
    @forelse($manutencoes as $man)
    <tr>
        <td>
            <div class="font-bold text-13">{{ $man->maquina?->nome ?? '—' }}</div>
            <div class="text-11 text-muted">{{ $man->maquina?->empresa?->nome_display ?? '' }}</div>
        </td>
        <td class="text-12">{{ $man->maquina?->setor?->nome ?? '—' }}</td>
        <td>
            <span class="badge {{ $tipos[$man->tipo]['badge']??'badge-secondary' }}">
                {{ $tipos[$man->tipo]['label']??ucfirst($man->tipo) }}
            </span>
        </td>
        <td class="font-mono text-12">{{ $man->data_manutencao->format('d/m/Y') }}
            @if($man->hora_inicio)<div class="text-11 text-muted">{{ substr($man->hora_inicio,0,5) }} → {{ substr($man->hora_fim??'',0,5) }}</div>@endif
        </td>
        <td class="text-12 font-mono">
            @if($man->duracao_minutos !== null)
                @if($man->duracao_minutos >= 60)
                    {{ intdiv($man->duracao_minutos,60) }}h {{ $man->duracao_minutos % 60 }}min
                @else
                    {{ $man->duracao_minutos }} min
                @endif
            @else —
            @endif
        </td>
        <td class="text-12">{{ $man->nomes_responsaveis }}</td>
        <td class="text-12" style="max-width:220px;white-space:normal">{{ $man->descricao ?? '—' }}</td>
        <td>
            <div class="flex gap-4">
                <button type="button" class="btn btn-ghost btn-icon"
                    onclick="abrirEdicaoManutencao({{ $man->id }}, {{ $man->empresa_id ?? 'null' }})"
                    title="Editar"><i class="fas fa-pencil-alt"></i></button>
                <form method="POST" action="{{ route('manutencoes.destroy',$man->id) }}" style="display:inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir registro?"><i class="fas fa-trash-alt"></i></button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="8">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-wrench"></i></div>
            <h3>Nenhuma manutenção registrada</h3>
            <p class="text-muted">Clique em "Nova Manutenção" para registrar ou importe um CSV.</p>
        </div>
    </td></tr>
    @endforelse
    </tbody></table></div>
    @if($manutencoes->hasPages())
    <div style="padding:12px 20px">{{ $manutencoes->links() }}</div>
    @endif
</div>

{{-- Modal Nova Manutenção --}}
<div class="modal-overlay" id="modalManutencao">
<div class="modal modal-lg">
<div class="modal-header">
    <div class="modal-title"><i class="fas fa-wrench"></i> Nova Manutenção</div>
    <button class="modal-close" onclick="closeModal('modalManutencao')"><i class="fas fa-times"></i></button>
</div>
<div class="modal-body">
<form method="POST" action="{{ route('manutencoes.geral.store') }}">@csrf
<div class="form-grid">

    @if(auth()->user()->hasRole('super-admin'))
    <div class="form-group">
        <label class="form-label">Empresa *</label>
        <select id="modalEmpresa" class="form-select" required onchange="loadMaquinas()">
            <option value="">Selecione</option>
            @foreach($empresas as $emp)
            <option value="{{ $emp->id }}">{{ $emp->nome_display }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="form-group">
        <label class="form-label">Máquina *</label>
        <select name="maquina_id" id="modalMaquina" class="form-select" required>
            <option value="">{{ auth()->user()->hasRole('super-admin') ? 'Selecione a empresa primeiro' : 'Carregando...' }}</option>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Tipo *</label>
        <select name="tipo" class="form-select" required>
            <optgroup label="Geral">
                <option value="preventiva">Preventiva</option>
                <option value="corretiva">Corretiva</option>
                <option value="preditiva">Preditiva</option>
                <option value="inspecao">Inspeção NR12</option>
            </optgroup>
            <optgroup label="Prensa">
                <option value="prensa_preventiva">Prensa — Preventiva</option>
                <option value="prensa_corretiva">Prensa — Corretiva</option>
            </optgroup>
            <optgroup label="Ferramenta">
                <option value="ferramenta_preventiva">Ferramenta — Preventiva</option>
                <option value="ferramenta_corretiva">Ferramenta — Corretiva</option>
            </optgroup>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Data da Manutenção *</label>
        <input type="date" name="data_manutencao" value="{{ date('Y-m-d') }}" class="form-control" required>
    </div>

    <div class="form-group">
        <label class="form-label">Hora de Início</label>
        <input type="time" name="hora_inicio" id="horaInicioModal" class="form-control" oninput="calcDuracao('modal')">
    </div>

    <div class="form-group">
        <label class="form-label">Hora de Término</label>
        <input type="time" name="hora_fim" id="horaFimModal" class="form-control" oninput="calcDuracao('modal')">
    </div>

    <div class="form-group">
        <label class="form-label">Duração</label>
        <div id="duracaoModal" class="form-control" style="background:var(--bg-secondary);color:var(--text-muted);cursor:default">—</div>
    </div>

    {{-- Responsáveis --}}
    <div class="form-group form-full">
        <label class="form-label">Responsáveis pela Manutenção</label>
        <div id="modalMecanicosLista" style="display:flex;flex-wrap:wrap;gap:6px;min-height:40px;padding:8px;background:var(--bg-sec);border:1px solid var(--border);border-radius:var(--r-sm);margin-bottom:8px">
            <span style="color:var(--text-muted);font-size:12px;align-self:center">{{ auth()->user()->hasRole('super-admin') ? 'Selecione a empresa primeiro' : 'Carregando mecânicos...' }}</span>
        </div>
        <input type="text" name="responsavel_externo" id="modalResponsavel" class="form-control" placeholder="Mecânico externo / empresa terceirizada (opcional)">
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px"><i class="fas fa-info-circle"></i> Marque os mecânicos cadastrados acima e/ou digite um nome externo.</div>
    </div>

    <div class="form-group form-full">
        <label class="form-label">Descrição / Serviços Realizados</label>
        <textarea name="descricao" class="form-control" rows="3" placeholder="Descreva os serviços realizados..."></textarea>
    </div>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" onclick="closeModal('modalManutencao')">Cancelar</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar</button>
</div>
</form>
</div>
</div>
</div>

{{-- Modal Editar Manutenção --}}
<div class="modal-overlay" id="modalEditarManutencao">
<div class="modal modal-lg">
<div class="modal-header">
    <div class="modal-title"><i class="fas fa-edit"></i> Editar Manutenção</div>
    <button class="modal-close" onclick="closeModal('modalEditarManutencao')"><i class="fas fa-times"></i></button>
</div>
<div class="modal-body">
<form method="POST" id="formEditarManutencao">@csrf @method('PUT')
<div class="form-grid">

    <div class="form-group form-full">
        <label class="form-label">Máquina</label>
        <input type="text" id="editMaquinaNome" class="form-control" disabled style="background:var(--bg-secondary)">
    </div>

    <div class="form-group">
        <label class="form-label">Tipo *</label>
        <select name="tipo" id="editTipo" class="form-select" required>
            <optgroup label="Geral">
                <option value="preventiva">Preventiva</option>
                <option value="corretiva">Corretiva</option>
                <option value="preditiva">Preditiva</option>
                <option value="inspecao">Inspeção NR12</option>
            </optgroup>
            <optgroup label="Prensa">
                <option value="prensa_preventiva">Prensa — Preventiva</option>
                <option value="prensa_corretiva">Prensa — Corretiva</option>
            </optgroup>
            <optgroup label="Ferramenta">
                <option value="ferramenta_preventiva">Ferramenta — Preventiva</option>
                <option value="ferramenta_corretiva">Ferramenta — Corretiva</option>
            </optgroup>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Data da Manutenção *</label>
        <input type="date" name="data_manutencao" id="editData" class="form-control" required>
    </div>

    <div class="form-group">
        <label class="form-label">Hora de Início</label>
        <input type="time" name="hora_inicio" id="editHoraInicio" class="form-control" oninput="calcDuracao('edit')">
    </div>

    <div class="form-group">
        <label class="form-label">Hora de Término</label>
        <input type="time" name="hora_fim" id="editHoraFim" class="form-control" oninput="calcDuracao('edit')">
    </div>

    <div class="form-group">
        <label class="form-label">Duração</label>
        <div id="duracaoEdit" class="form-control" style="background:var(--bg-secondary);color:var(--text-muted);cursor:default">—</div>
    </div>

    {{-- Responsáveis --}}
    <div class="form-group form-full">
        <label class="form-label">Responsáveis pela Manutenção</label>
        <div id="editMecanicosLista" style="display:flex;flex-wrap:wrap;gap:6px;min-height:40px;padding:8px;background:var(--bg-sec);border:1px solid var(--border);border-radius:var(--r-sm);margin-bottom:8px">
            <span style="color:var(--text-muted);font-size:12px;align-self:center">Carregando...</span>
        </div>
        <input type="text" name="responsavel_externo" id="editResponsavel" class="form-control" placeholder="Mecânico externo / empresa terceirizada (opcional)">
    </div>

    <div class="form-group form-full">
        <label class="form-label">Descrição / Serviços Realizados</label>
        <textarea name="descricao" id="editDescricao" class="form-control" rows="3" placeholder="Descreva os serviços realizados..."></textarea>
    </div>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" onclick="closeModal('modalEditarManutencao')">Cancelar</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Alterações</button>
</div>
</form>
</div>
</div>
</div>

{{-- Modal Registrar em Lote --}}
<div class="modal-overlay" id="modalLote">
<div class="modal" style="max-width:860px;width:95vw">
<div class="modal-header">
    <div class="modal-title"><i class="fas fa-layer-group"></i> Registrar Manutenções em Lote</div>
    <button class="modal-close" onclick="closeModal('modalLote')"><i class="fas fa-times"></i></button>
</div>
<div class="modal-body">
<form method="POST" action="{{ route('manutencoes.geral.lote') }}" id="formLote">@csrf

{{-- Campos compartilhados (máquina / tipo / data / mecânicos) --}}
<div style="background:var(--bg-secondary);border-radius:var(--r);padding:16px;margin-bottom:16px">
    <div style="font-size:12px;font-weight:700;color:var(--text-3);letter-spacing:.05em;margin-bottom:12px">DADOS COMUNS A TODOS OS REGISTROS</div>
    <div class="form-grid" style="gap:12px">

        @if(auth()->user()->hasRole('super-admin'))
        <div class="form-group" style="margin:0">
            <label class="form-label">Empresa</label>
            <select id="loteEmpresa" class="form-select" onchange="loadMaquinasLote()">
                <option value="">Selecione</option>
                @foreach($empresas as $emp)
                <option value="{{ $emp->id }}">{{ $emp->nome_display }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="form-group" style="margin:0">
            <label class="form-label">Máquina *</label>
            <select name="maquina_id" id="loteMaquina" class="form-select" required>
                <option value="">{{ auth()->user()->hasRole('super-admin') ? 'Selecione a empresa primeiro' : 'Carregando...' }}</option>
            </select>
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label">Tipo *</label>
            <select name="tipo" id="loteTipo" class="form-select" required>
                <optgroup label="Geral">
                    <option value="preventiva">Preventiva</option>
                    <option value="corretiva">Corretiva</option>
                    <option value="preditiva">Preditiva</option>
                    <option value="inspecao">Inspeção NR12</option>
                </optgroup>
                <optgroup label="Prensa">
                    <option value="prensa_preventiva">Prensa — Preventiva</option>
                    <option value="prensa_corretiva">Prensa — Corretiva</option>
                </optgroup>
                <optgroup label="Ferramenta">
                    <option value="ferramenta_preventiva">Ferramenta — Preventiva</option>
                    <option value="ferramenta_corretiva">Ferramenta — Corretiva</option>
                </optgroup>
            </select>
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label">Data *</label>
            <input type="date" name="data_manutencao" class="form-control" value="{{ date('Y-m-d') }}" required>
        </div>

    </div>

    {{-- Mecânicos padrão --}}
    <div class="form-group" style="margin:12px 0 0">
        <label class="form-label">
            Mecânicos padrão
            <span style="font-weight:400;color:var(--text-3);font-size:11px">— usado em linhas que não tiverem mecânico próprio</span>
        </label>
        <div id="loteMecanicosLista" style="display:flex;flex-wrap:wrap;gap:6px;min-height:38px;padding:8px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r-sm)">
            <span style="color:var(--text-muted);font-size:12px;align-self:center">{{ auth()->user()->hasRole('super-admin') ? 'Selecione a empresa primeiro' : 'Carregando...' }}</span>
        </div>
    </div>
</div>

{{-- Lista dinâmica de itens --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
    <div style="font-size:13px;font-weight:700;color:var(--text-1)">
        <i class="fas fa-list"></i> Registros individuais
        <span style="font-size:11px;font-weight:400;color:var(--text-3);margin-left:6px">— cada linha pode ter data e mecânicos próprios</span>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="addLoteItem()">
        <i class="fas fa-plus"></i> Adicionar linha
    </button>
</div>

<div id="loteItens" style="display:flex;flex-direction:column;gap:8px"></div>

<div class="modal-footer" style="margin-top:16px">
    <button type="button" class="btn btn-ghost" onclick="closeModal('modalLote')">Cancelar</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar todos</button>
</div>
</form>
</div>
</div>
</div>

{{-- Modal Importar CSV --}}
<div class="modal-overlay" id="modalImportar">
<div class="modal">
<div class="modal-header">
    <div class="modal-title"><i class="fas fa-file-import"></i> Importar Manutenções</div>
    <button class="modal-close" onclick="closeModal('modalImportar')"><i class="fas fa-times"></i></button>
</div>
<div class="modal-body">
    <div style="background:var(--bg-secondary);border-radius:var(--r-sm);padding:14px;margin-bottom:16px;font-size:13px">
        <div style="font-weight:600;margin-bottom:8px">Formato do arquivo CSV (separado por ponto-e-vírgula):</div>
        <code style="font-size:11px;display:block;line-height:1.8">
            empresa_cnpj ; maquina_nome ; tipo ; data_manutencao ; duracao_minutos ; descricao ; responsavel
        </code>
        <div style="margin-top:8px;color:var(--text-muted);font-size:12px">
            <strong>Tipos válidos:</strong> preventiva, corretiva, preditiva, inspecao, prensa_preventiva, prensa_corretiva, ferramenta_preventiva, ferramenta_corretiva<br>
            <strong>Data:</strong> AAAA-MM-DD ou DD/MM/AAAA<br>
            @if(!auth()->user()->hasRole('super-admin'))
            <strong>empresa_cnpj:</strong> pode deixar em branco (usa sua empresa automaticamente)
            @endif
        </div>
        <a href="{{ route('manutencoes.modelo-csv') }}" class="btn btn-ghost btn-sm" style="margin-top:10px">
            <i class="fas fa-download"></i> Baixar modelo CSV
        </a>
    </div>
    <form method="POST" action="{{ route('manutencoes.importar') }}" enctype="multipart/form-data">@csrf
        <div class="form-group">
            <label class="form-label">Arquivo CSV *</label>
            <input type="file" name="arquivo" class="form-control" accept=".csv,.txt" required>
        </div>
        <div class="modal-footer" style="padding:0;margin-top:16px">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modalImportar')">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Importar</button>
        </div>
    </form>
</div>
</div>
</div>

@endsection

@push('styles')
<style>
/* ── Cabeçalhos ordenáveis ───────────────────────────────────── */
.th-sort {
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
}
.th-sort:hover { background: rgba(var(--brand-rgb),.06); }
.sort-ind {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-left: 5px;
    font-size: 10px;
    font-weight: 700;
    opacity: .35;
    vertical-align: middle;
    transition: opacity .15s;
}
.sort-ind.active { opacity: 1; color: var(--brand); }
.sort-ind .sort-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background: var(--brand);
    color: #fff;
    font-size: 9px;
    font-weight: 800;
    line-height: 1;
}
</style>
@endpush

@push('scripts')
<script>
function calcDuracao(prefix) {
    const inicio = document.getElementById('horaInicio'+prefix.charAt(0).toUpperCase()+prefix.slice(1))?.value;
    const fim    = document.getElementById('horaFim'+prefix.charAt(0).toUpperCase()+prefix.slice(1))?.value;
    const el     = document.getElementById('duracao'+prefix.charAt(0).toUpperCase()+prefix.slice(1));
    if (!el) return;
    if (!inicio || !fim) { el.textContent = '—'; return; }
    const [ih,im] = inicio.split(':').map(Number);
    let   [fh,fm] = fim.split(':').map(Number);
    let total = (fh*60+fm) - (ih*60+im);
    if (total < 0) total += 1440; // virou meia-noite
    const h = Math.floor(total/60), m = total%60;
    el.textContent = h > 0 ? `${h}h ${m}min (${total} min)` : `${total} min`;
}

const isSuperAdmin = {{ auth()->user()->hasRole('super-admin') ? 'true' : 'false' }};

async function loadMaquinas(empresaId) {
    const eid = empresaId ?? (isSuperAdmin ? document.getElementById('modalEmpresa')?.value : '');
    const sel = document.getElementById('modalMaquina');
    if (isSuperAdmin && !eid) { sel.innerHTML = '<option value="">Selecione a empresa primeiro</option>'; return; }
    sel.innerHTML = '<option value="">Carregando...</option>';
    try {
        const r = await fetch(`/api/maquinas${eid ? '?empresa_id='+eid : ''}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!r.ok) throw new Error('Erro ' + r.status);
        const data = await r.json();
        if (data.length === 0) {
            sel.innerHTML = '<option value="">Nenhuma máquina cadastrada</option>';
            return;
        }
        sel.innerHTML = '<option value="">Selecione a máquina</option>';
        data.forEach(m => {
            const o = document.createElement('option');
            o.value = m.id;
            o.textContent = m.nome + (m.numero_serie ? ' (S/N: '+m.numero_serie+')' : '');
            sel.appendChild(o);
        });
    } catch(e) {
        sel.innerHTML = '<option value="">Erro ao carregar máquinas</option>';
        console.error('loadMaquinas:', e);
    }
    if (isSuperAdmin) loadMecanicosModal(eid);
}

async function loadMecanicosModal(empresaId) {
    const eid = empresaId ?? (isSuperAdmin ? document.getElementById('modalEmpresa')?.value : '');
    const lista = document.getElementById('modalMecanicosLista');
    if (isSuperAdmin && !eid) {
        lista.innerHTML = '<span style="color:var(--text-muted);font-size:12px;align-self:center">Selecione a empresa primeiro</span>';
        return;
    }
    lista.innerHTML = '<span style="color:var(--text-muted);font-size:12px">Carregando...</span>';
    try {
        const url = '/api/mecanicos' + (eid ? '?empresa_id='+eid : '');
        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        const data = await r.json();
        if (data.length === 0) {
            lista.innerHTML = '<span style="color:var(--text-muted);font-size:12px">Nenhum mecânico cadastrado para esta empresa. <a href="{{ route("mecanicos.index") }}" target="_blank">Cadastrar</a></span>';
            return;
        }
        lista.innerHTML = '';
        data.forEach(m => {
            const label = document.createElement('label');
            label.style.cssText = 'display:flex;align-items:center;gap:6px;padding:6px 10px;background:var(--bg-card);border:1px solid var(--border);border-radius:20px;cursor:pointer;font-size:13px;user-select:none';
            label.innerHTML = `<input type="checkbox" name="mecanicos[]" value="${m.id}" style="cursor:pointer"> ${m.nome}`;
            label.querySelector('input').addEventListener('change', function() {
                label.style.background = this.checked ? 'var(--brand-l)' : 'var(--bg-card)';
                label.style.borderColor = this.checked ? 'var(--brand)' : 'var(--border)';
                label.style.color = this.checked ? 'var(--brand)' : '';
                label.style.fontWeight = this.checked ? '600' : '';
            });
            lista.appendChild(label);
        });
    } catch(e) { lista.innerHTML = '<span style="color:var(--danger);font-size:12px">Erro ao carregar mecânicos</span>'; }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[onclick="openModal(\'modalManutencao\')"]')?.addEventListener('click', () => {
        if (!isSuperAdmin) {
            loadMaquinas();
            loadMecanicosModal('');
        }
    });
});

// ──── Lote ──────────────────────────────────────────────────────────────────
let loteItemCount = 0;
let loteMecanicosCache = []; // [{id, nome}] carregados para a empresa atual

function addLoteItem() {
    const idx = loteItemCount++;
    const row = document.createElement('div');
    row.id = 'lote_row_' + idx;
    row.style.cssText = 'background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r-sm);overflow:hidden';

    // Linha superior: número + trash
    // Linha de campos
    row.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 12px 0;border-bottom:1px solid var(--border)">
            <span style="font-size:11px;font-weight:700;color:var(--brand)">Registro ${idx + 1}</span>
            <button type="button" class="btn btn-ghost btn-icon text-danger btn-sm" onclick="document.getElementById('lote_row_${idx}').remove()" title="Remover"><i class="fas fa-trash" style="font-size:12px"></i></button>
        </div>
        <div style="display:grid;grid-template-columns:130px 90px 90px 1fr 170px;gap:8px;padding:10px 12px 4px;align-items:end">
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">
                    Data
                    <span style="font-weight:400;color:var(--text-3)">(padrão se vazio)</span>
                </label>
                <input type="date" name="itens[${idx}][data_manutencao]" class="form-control" style="font-size:12px">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Início</label>
                <input type="time" name="itens[${idx}][hora_inicio]" class="form-control" style="font-size:12px" oninput="calcLoteDuracao(${idx})">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Término <span id="dur_${idx}" style="color:var(--brand);font-weight:700"></span></label>
                <input type="time" name="itens[${idx}][hora_fim]" class="form-control" style="font-size:12px" oninput="calcLoteDuracao(${idx})">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Descrição / Serviços realizados</label>
                <input type="text" name="itens[${idx}][descricao]" class="form-control" style="font-size:12px" placeholder="Descreva os serviços…">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:11px">Responsável externo</label>
                <input type="text" name="itens[${idx}][responsavel_externo]" class="form-control" style="font-size:12px" placeholder="Empresa / Técnico">
            </div>
        </div>
        <div style="padding:0 12px 10px">
            <label class="form-label" style="font-size:11px;margin-bottom:4px">
                Mecânicos
                <span style="font-weight:400;color:var(--text-3)">(deixe em branco para usar o padrão)</span>
            </label>
            <div id="mec_chips_${idx}" style="display:flex;flex-wrap:wrap;gap:5px;min-height:32px;padding:6px 8px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--r-sm)">
                ${loteMecanicosCache.length
                    ? loteMecanicosCache.map(m => `
                        <label style="display:flex;align-items:center;gap:5px;padding:4px 9px;background:var(--bg-card);border:1px solid var(--border);border-radius:20px;cursor:pointer;font-size:11px;user-select:none;transition:all .12s" id="mec_chip_${idx}_${m.id}">
                            <input type="checkbox" name="itens[${idx}][mecanicos][]" value="${m.id}" style="cursor:pointer;accent-color:var(--brand)" onchange="toggleMecChip(this,'mec_chip_${idx}_${m.id}')"> ${m.nome}
                        </label>`).join('')
                    : '<span style="color:var(--text-muted);font-size:11px;align-self:center">Carregando mecânicos...</span>'
                }
            </div>
        </div>
    `;
    document.getElementById('loteItens').appendChild(row);
}

function toggleMecChip(cb, chipId) {
    const label = document.getElementById(chipId);
    if (!label) return;
    label.style.background  = cb.checked ? 'rgba(var(--brand-rgb),.1)' : 'var(--bg-card)';
    label.style.borderColor = cb.checked ? 'var(--brand)'              : 'var(--border)';
    label.style.color       = cb.checked ? 'var(--brand)'              : '';
    label.style.fontWeight  = cb.checked ? '700'                       : '';
}

function calcLoteDuracao(idx) {
    const row = document.getElementById('lote_row_' + idx);
    if (!row) return;
    const ini = row.querySelector(`input[name="itens[${idx}][hora_inicio]"]`)?.value;
    const fim = row.querySelector(`input[name="itens[${idx}][hora_fim]"]`)?.value;
    const el  = document.getElementById('dur_' + idx);
    if (!el) return;
    if (!ini || !fim) { el.textContent = ''; return; }
    const [ih,im] = ini.split(':').map(Number);
    let   [fh,fm] = fim.split(':').map(Number);
    let total = (fh*60+fm) - (ih*60+im);
    if (total < 0) total += 1440;
    const h = Math.floor(total/60), m = total%60;
    el.textContent = h > 0 ? `${h}h${m}min` : `${total}min`;
}

async function loadMaquinasLote() {
    const eid = document.getElementById('loteEmpresa')?.value || '';
    const sel = document.getElementById('loteMaquina');
    if (isSuperAdmin && !eid) {
        sel.innerHTML = '<option value="">Selecione a empresa primeiro</option>';
        loadMecanicosLote('');
        return;
    }
    sel.innerHTML = '<option value="">Carregando...</option>';
    const r    = await fetch(`/api/maquinas${eid?'?empresa_id='+eid:''}`, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
    const data = await r.json();
    sel.innerHTML = '<option value="">Selecione a máquina</option>';
    data.forEach(m => {
        const o = document.createElement('option');
        o.value = m.id;
        o.textContent = m.nome + (m.numero_serie ? ' (S/N: '+m.numero_serie+')' : '');
        sel.appendChild(o);
    });
    loadMecanicosLote(eid);
}

async function loadMecanicosLote(empresaId) {
    const lista = document.getElementById('loteMecanicosLista');
    const eid   = empresaId !== undefined ? empresaId : (document.getElementById('loteEmpresa')?.value || '');
    if (isSuperAdmin && !eid) {
        lista.innerHTML = '<span style="color:var(--text-muted);font-size:12px;align-self:center">Selecione a empresa primeiro</span>';
        loteMecanicosCache = [];
        return;
    }
    lista.innerHTML = '<span style="color:var(--text-muted);font-size:12px">Carregando...</span>';
    try {
        const r    = await fetch('/api/mecanicos'+(eid?'?empresa_id='+eid:''), {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
        const data = await r.json();
        loteMecanicosCache = data;

        if (!data.length) {
            lista.innerHTML = '<span style="color:var(--text-muted);font-size:12px">Nenhum mecânico cadastrado.</span>';
        } else {
            lista.innerHTML = '';
            data.forEach(m => {
                const label = document.createElement('label');
                label.style.cssText = 'display:flex;align-items:center;gap:6px;padding:5px 10px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:20px;cursor:pointer;font-size:12px;user-select:none';
                label.innerHTML = `<input type="checkbox" name="mecanicos[]" value="${m.id}" style="cursor:pointer;accent-color:var(--brand)"> ${m.nome}`;
                label.querySelector('input').addEventListener('change', function() {
                    label.style.background  = this.checked ? 'var(--brand-l)' : 'var(--bg-secondary)';
                    label.style.borderColor = this.checked ? 'var(--brand)'   : 'var(--border)';
                    label.style.color       = this.checked ? 'var(--brand)'   : '';
                    label.style.fontWeight  = this.checked ? '600'            : '';
                });
                lista.appendChild(label);
            });
        }

        // Atualiza chips de mecânicos em todas as linhas existentes
        refreshMecChipsInRows();

    } catch(e) { lista.innerHTML = '<span style="color:var(--danger);font-size:12px">Erro ao carregar mecânicos</span>'; }
}

function refreshMecChipsInRows() {
    // Re-renderiza os chips de mecânicos em cada linha já criada
    document.querySelectorAll('[id^="mec_chips_"]').forEach(wrap => {
        const idx = wrap.id.replace('mec_chips_', '');
        if (!loteMecanicosCache.length) {
            wrap.innerHTML = '<span style="color:var(--text-muted);font-size:11px;align-self:center">Nenhum mecânico disponível</span>';
            return;
        }
        wrap.innerHTML = loteMecanicosCache.map(m => `
            <label style="display:flex;align-items:center;gap:5px;padding:4px 9px;background:var(--bg-card);border:1px solid var(--border);border-radius:20px;cursor:pointer;font-size:11px;user-select:none;transition:all .12s" id="mec_chip_${idx}_${m.id}">
                <input type="checkbox" name="itens[${idx}][mecanicos][]" value="${m.id}" style="cursor:pointer;accent-color:var(--brand)" onchange="toggleMecChip(this,'mec_chip_${idx}_${m.id}')"> ${m.nome}
            </label>`).join('');
    });
}

// Abre o modal de lote com 3 linhas iniciais
const _origOpenLote = window.openModal;
window.openModal = function(id) {
    if (id === 'modalLote') {
        const itens = document.getElementById('loteItens');
        if (itens && itens.children.length === 0) {
            addLoteItem(); addLoteItem(); addLoteItem();
        }
        if (!isSuperAdmin && document.getElementById('loteMaquina').options.length <= 1) {
            loadMaquinasLote();
        }
    }
    _origOpenLote && _origOpenLote(id);
};

// ──── Editar Manutenção ─────────────────────────────────────────────────────
async function abrirEdicaoManutencao(id, empresaId) {
    try {
        const r = await fetch(`/manutencoes/${id}/edit`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const d = await r.json();

        // Preenche os campos
        document.getElementById('editMaquinaNome').value = d.maquina_nome ?? '—';
        document.getElementById('editTipo').value        = d.tipo;
        document.getElementById('editData').value        = d.data_manutencao;
        document.getElementById('editHoraInicio').value  = d.hora_inicio ?? '';
        document.getElementById('editHoraFim').value     = d.hora_fim    ?? '';
        document.getElementById('editResponsavel').value = d.responsavel ?? '';
        document.getElementById('editDescricao').value   = d.descricao   ?? '';
        calcDuracao('edit');

        // Define o action do form
        document.getElementById('formEditarManutencao').action = `/manutencoes/${id}`;

        // Carrega mecânicos com os já selecionados marcados
        await carregarMecanicosEdit(empresaId, d.mecanicos ?? []);

        openModal('modalEditarManutencao');
    } catch(e) {
        alert('Erro ao carregar dados da manutenção.');
        console.error(e);
    }
}

async function carregarMecanicosEdit(empresaId, selecionados) {
    const lista = document.getElementById('editMecanicosLista');
    lista.innerHTML = '<span style="color:var(--text-muted);font-size:12px">Carregando...</span>';
    try {
        const url = '/api/mecanicos' + (empresaId ? '?empresa_id='+empresaId : '');
        const r   = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        const data = await r.json();
        if (!data.length) {
            lista.innerHTML = '<span style="color:var(--text-muted);font-size:12px">Nenhum mecânico cadastrado.</span>';
            return;
        }
        lista.innerHTML = '';
        data.forEach(m => {
            const checked = selecionados.includes(m.id);
            const label   = document.createElement('label');
            label.style.cssText = 'display:flex;align-items:center;gap:6px;padding:6px 10px;background:var(--bg-card);border:1px solid var(--border);border-radius:20px;cursor:pointer;font-size:13px;user-select:none';
            if (checked) {
                label.style.background   = 'var(--brand-l)';
                label.style.borderColor  = 'var(--brand)';
                label.style.color        = 'var(--brand)';
                label.style.fontWeight   = '600';
            }
            label.innerHTML = `<input type="checkbox" name="mecanicos[]" value="${m.id}" ${checked?'checked':''} style="cursor:pointer"> ${m.nome}`;
            label.querySelector('input').addEventListener('change', function() {
                label.style.background  = this.checked ? 'var(--brand-l)' : 'var(--bg-card)';
                label.style.borderColor = this.checked ? 'var(--brand)'   : 'var(--border)';
                label.style.color       = this.checked ? 'var(--brand)'   : '';
                label.style.fontWeight  = this.checked ? '600'            : '';
            });
            lista.appendChild(label);
        });
    } catch(e) {
        lista.innerHTML = '<span style="color:var(--danger);font-size:12px">Erro ao carregar mecânicos</span>';
    }
}

// ── Ordenação multi-coluna ──────────────────────────────────────
// Estado atual vindo do PHP (ex: {data_manutencao: 'desc'})
let currentSorts = @json($currentSorts);

// Renderiza os indicadores nas colunas ao carregar a página
(function initSortIndicators() {
    const cols = Object.keys(currentSorts);
    cols.forEach((col, idx) => {
        const el = document.getElementById('si-' + col);
        if (!el) return;
        const dir   = currentSorts[col];
        const arrow = dir === 'asc' ? '▲' : '▼';
        const badge = cols.length > 1 ? `<span class="sort-badge">${idx + 1}</span>` : '';
        el.innerHTML = arrow + badge;
        el.classList.add('active');
    });
    // Colunas sem ordenação mostram ↕ apagado
    ['maquina','tipo','data_manutencao','duracao_minutos'].forEach(col => {
        if (!currentSorts[col]) {
            const el = document.getElementById('si-' + col);
            if (el) el.innerHTML = '↕';
        }
    });
})();

function toggleSort(col, event) {
    // Shift+clique → adiciona/alterna/remove da ordenação composta
    // Clique simples → substitui tudo por esta coluna
    if (event.shiftKey) {
        if (currentSorts[col]) {
            if (currentSorts[col] === 'asc') {
                currentSorts[col] = 'desc';
            } else {
                // Terceiro clique remove a coluna do sort
                const novo = {};
                for (const k in currentSorts) { if (k !== col) novo[k] = currentSorts[k]; }
                currentSorts = novo;
            }
        } else {
            currentSorts[col] = 'asc';
        }
    } else {
        // Clique simples: se já é a única coluna, inverte; senão, começa do zero
        const keys = Object.keys(currentSorts);
        if (keys.length === 1 && keys[0] === col) {
            currentSorts = { [col]: currentSorts[col] === 'asc' ? 'desc' : 'asc' };
        } else {
            currentSorts = { [col]: 'asc' };
        }
    }
    applySort();
}

function applySort() {
    const url = new URL(window.location.href);
    // Remove sorts anteriores
    [...url.searchParams.keys()]
        .filter(k => k.startsWith('sorts['))
        .forEach(k => url.searchParams.delete(k));
    // Adiciona sorts atuais
    for (const [col, dir] of Object.entries(currentSorts)) {
        url.searchParams.set('sorts[' + col + ']', dir);
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}
</script>
@endpush
