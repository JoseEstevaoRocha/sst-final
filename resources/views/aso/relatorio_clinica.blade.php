<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatório de Agendamento — Clínica</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;font-size:12px;color:#1a1a1a;background:#fff;padding:20px}
.header{border-bottom:3px solid #1d4ed8;padding-bottom:14px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:flex-start}
.logo-area h1{font-size:20px;color:#1d4ed8;font-weight:800}
.logo-area p{font-size:11px;color:#64748b;margin-top:2px}
.doc-info{text-align:right;font-size:11px;color:#64748b}
.doc-info strong{display:block;font-size:13px;color:#1a1a1a}
.empresa-box{background:#f1f5f9;border-left:4px solid #1d4ed8;padding:10px 14px;border-radius:4px;margin-bottom:16px}
.empresa-box h2{font-size:14px;color:#1d4ed8;font-weight:700;margin-bottom:4px}
.empresa-box p{font-size:11px;color:#475569}
.section-title{font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.06em;margin:16px 0 8px}
table{width:100%;border-collapse:collapse;font-size:11px}
thead tr{background:#1d4ed8;color:#fff}
thead th{padding:7px 8px;text-align:left;font-weight:600;font-size:10px;text-transform:uppercase;letter-spacing:.05em}
tbody tr{border-bottom:1px solid #e2e8f0}
tbody tr:nth-child(even){background:#f8fafc}
tbody td{padding:7px 8px;vertical-align:top}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700}
.badge-blue{background:#dbeafe;color:#1d4ed8}
.badge-green{background:#dcfce7;color:#16a34a}
.badge-orange{background:#ffedd5;color:#c2410c}
.footer{margin-top:24px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;font-size:10px;color:#94a3b8}
.assinatura{margin-top:40px;display:flex;gap:60px}
.ass-line{border-top:1px solid #64748b;padding-top:6px;font-size:10px;color:#475569;min-width:200px;text-align:center}
.exames-cell{color:#475569;font-style:italic}

/* Toolbar de colunas (no-print) */
.no-print{margin-bottom:16px}
.cols-toolbar{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;margin-bottom:16px}
.cols-toolbar h3{font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px}
.cols-grid{display:flex;flex-wrap:wrap;gap:8px}
.col-check{display:flex;align-items:center;gap:5px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:5px 10px;cursor:pointer;font-size:12px;transition:border-color .15s}
.col-check:hover{border-color:#1d4ed8}
.col-check input:checked ~ *{color:#1d4ed8}
.actions-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap}

@media print{
    .no-print{display:none!important}
    body{padding:10px}
    .cols-toolbar{display:none!important}
}
</style>
</head>
<body>

@php
$colLabels = [
    'nome'        => 'Nome',
    'cpf'         => 'CPF',
    'nascimento'  => 'Nascimento',
    'setor'       => 'Setor',
    'funcao'      => 'Função',
    'tipo'        => 'Tipo de Exame',
    'data_agendada'=> 'Data Agendada',
    'horario'     => 'Horário',
    'local'       => 'Local',
    'exames'      => 'Exames Compl.',
    'observacoes' => 'Observações',
];
$tipos = ['admissional'=>'Admissional','periodico'=>'Periódico','demissional'=>'Demissional','retorno'=>'Retorno','mudanca_funcao'=>'Mudança de Função'];
@endphp

<div class="no-print">
    {{-- Seletor de colunas --}}
    <div class="cols-toolbar">
        <h3>Selecione as colunas do relatório</h3>
        <div class="cols-grid" id="colsGrid">
            @foreach($colLabels as $key => $label)
            <label class="col-check" id="lbl_{{ $key }}">
                <input type="checkbox" value="{{ $key }}" onchange="toggleCol('{{ $key }}')"
                    {{ in_array($key,$colsSelecionadas)?'checked':'' }}>
                <span>{{ $label }}</span>
            </label>
            @endforeach
        </div>
    </div>
    <div class="actions-bar">
        <button onclick="window.print()" style="padding:8px 20px;background:#1d4ed8;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600">
            🖨️ Imprimir / Salvar PDF
        </button>
        <button onclick="window.close()" style="padding:8px 16px;background:#e2e8f0;color:#1a1a1a;border:none;border-radius:6px;cursor:pointer">
            Fechar
        </button>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer">
            <input type="checkbox" id="orientacaoLandscape" onchange="toggleLandscape(this)">
            Modo paisagem (mais colunas)
        </label>
    </div>
</div>

{{-- Cabeçalho --}}
<div class="header">
    <div class="logo-area">
        <h1>SST Manager</h1>
        <p>Relatório de Agendamento de Exames Ocupacionais</p>
    </div>
    <div class="doc-info">
        <strong>Data de Emissão</strong>
        {{ now()->format('d/m/Y H:i') }}<br>
        Emitido por: {{ auth()->user()->name ?? '—' }}
    </div>
</div>

@if($empresa)
<div class="empresa-box">
    <h2>{{ $empresa->razao_social ?? $empresa->nome_display }}</h2>
    <p>
        @if($empresa->cnpj) CNPJ: {{ $empresa->cnpj }} &nbsp;|&nbsp; @endif
        @if($empresa->endereco) {{ $empresa->endereco }} @endif
    </p>
</div>
@endif

@php
// Agrupar por local_exame para separar relatório
$porclinica   = $asos->where('local_exame','clinica')->values();
$porEmpresa   = $asos->where('local_exame','in_company')->values();
$grupos = [];
if ($porclinica->count())  $grupos[] = ['label'=>'Exames na Clínica',    'badge'=>'badge-blue',   'asos'=>$porclinica];
if ($porEmpresa->count())  $grupos[] = ['label'=>'Exames In Company',    'badge'=>'badge-orange', 'asos'=>$porEmpresa];
if (empty($grupos))        $grupos[] = ['label'=>'Colaboradores',        'badge'=>'badge-blue',   'asos'=>$asos];
@endphp

@foreach($grupos as $grupo)
<div class="section-title">
    {{ $grupo['label'] }} — {{ $grupo['asos']->count() }} colaborador(es)
</div>

<table id="tabela_{{ $loop->index }}">
    <thead>
        <tr>
            <th>#</th>
            @if(in_array('nome',          $colsSelecionadas)) <th>Colaborador</th> @endif
            @if(in_array('cpf',           $colsSelecionadas)) <th>CPF</th> @endif
            @if(in_array('nascimento',    $colsSelecionadas)) <th>Nasc.</th> @endif
            @if(in_array('setor',         $colsSelecionadas)) <th>Setor</th> @endif
            @if(in_array('funcao',        $colsSelecionadas)) <th>Função</th> @endif
            @if(in_array('tipo',          $colsSelecionadas)) <th>Tipo</th> @endif
            @if(in_array('data_agendada', $colsSelecionadas)) <th>Data</th> @endif
            @if(in_array('horario',       $colsSelecionadas)) <th>Hora</th> @endif
            @if(in_array('local',         $colsSelecionadas)) <th>Local</th> @endif
            @if(in_array('exames',        $colsSelecionadas)) <th>Exames Compl.</th> @endif
            @if(in_array('observacoes',   $colsSelecionadas)) <th>Obs.</th> @endif
        </tr>
    </thead>
    <tbody>
    @foreach($grupo['asos'] as $i => $a)
    @php $colab = $a->colaborador; @endphp
    <tr>
        <td>{{ $i + 1 }}</td>
        @if(in_array('nome',          $colsSelecionadas)) <td><strong>{{ $colab?->nome ?? '—' }}</strong></td> @endif
        @if(in_array('cpf',           $colsSelecionadas)) <td>{{ $colab?->cpf ?? '—' }}</td> @endif
        @if(in_array('nascimento',    $colsSelecionadas)) <td>{{ $colab?->data_nascimento?->format('d/m/Y') ?? '—' }}</td> @endif
        @if(in_array('setor',         $colsSelecionadas)) <td>{{ $colab?->setor?->nome ?? '—' }}</td> @endif
        @if(in_array('funcao',        $colsSelecionadas)) <td>{{ $colab?->funcao?->nome ?? '—' }}</td> @endif
        @if(in_array('tipo',          $colsSelecionadas)) <td><span class="badge badge-blue">{{ $tipos[$a->tipo] ?? ucfirst($a->tipo) }}</span></td> @endif
        @if(in_array('data_agendada', $colsSelecionadas)) <td>{{ $a->data_agendada?->format('d/m/Y') ?? '—' }}</td> @endif
        @if(in_array('horario',       $colsSelecionadas)) <td>{{ $a->horario_agendado ? substr($a->horario_agendado,0,5) : '—' }}</td> @endif
        @if(in_array('local',         $colsSelecionadas)) <td>{{ $a->local_exame === 'in_company' ? 'In Company' : ($a->clinica?->nome ?? 'Clínica') }}</td> @endif
        @if(in_array('exames',        $colsSelecionadas)) <td class="exames-cell">{{ $a->exames_complementares ?? '—' }}</td> @endif
        @if(in_array('observacoes',   $colsSelecionadas)) <td class="exames-cell">{{ $a->observacoes ?? '—' }}</td> @endif
    </tr>
    @endforeach
    </tbody>
</table>
@endforeach

<div class="assinatura">
    <div class="ass-line">Responsável SST / Empresa</div>
    <div class="ass-line">Responsável Clínica / Médico</div>
    <div class="ass-line">Data: ___/___/______</div>
</div>

<div class="footer">
    <span>SST Manager — Sistema de Gestão de Saúde e Segurança do Trabalho</span>
    <span>Gerado em {{ now()->format('d/m/Y \à\s H:i') }}</span>
</div>

<script>
// Seletor de colunas dinâmico (oculta/mostra sem recarregar)
const COL_KEYS = @json(array_keys($colLabels));

function toggleCol(key) {
    const checked = document.querySelector(`input[value="${key}"]`).checked;
    // Encontra índice da coluna nas tabelas
    const idx = COL_KEYS.indexOf(key) + 2; // +2: coluna # + offset
    document.querySelectorAll('table').forEach(tbl => {
        tbl.querySelectorAll(`th:nth-child(${idx}), td:nth-child(${idx})`).forEach(cell => {
            cell.style.display = checked ? '' : 'none';
        });
    });
}

function toggleLandscape(cb) {
    if (cb.checked) {
        document.head.insertAdjacentHTML('beforeend','<style id="landscapeStyle">@page{size:A4 landscape}</style>');
    } else {
        document.getElementById('landscapeStyle')?.remove();
    }
}

// Inicializa: oculta colunas não selecionadas
document.addEventListener('DOMContentLoaded', () => {
    @foreach($colLabels as $key => $label)
    @if(!in_array($key, $colsSelecionadas))
    toggleCol('{{ $key }}');
    @endif
    @endforeach
});
</script>
</body>
</html>
