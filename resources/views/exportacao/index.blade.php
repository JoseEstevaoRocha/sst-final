@extends('layouts.app')
@section('title','Exportação de Colaboradores')
@section('content')

<div class="page-header">
    <div><h1 class="page-title"><i class="fas fa-file-export"></i> Exportação de Colaboradores</h1></div>
</div>

@if(session('error'))
    <div class="alert alert-danger mb-16">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('exportacao.exportar') }}" id="formExport">
@csrf
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    {{-- ── Empresas ── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-building"></i> Empresas</div>
        </div>
        <div class="flex flex-col gap-10">
            @if($empresas->count() > 1)
                <div class="flex gap-8 mb-4">
                    <button type="button" class="btn btn-xs btn-secondary" onclick="selecionarTodas(true)">Todas</button>
                    <button type="button" class="btn btn-xs btn-secondary" onclick="selecionarTodas(false)">Nenhuma</button>
                </div>
            @endif
            @foreach($empresas as $emp)
                <label class="flex gap-8 items-center" style="cursor:pointer;padding:6px 8px;border-radius:6px;transition:background .15s" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background=''">
                    <input type="checkbox" name="empresa_ids[]" value="{{ $emp->id }}" class="emp-check"
                        {{ $empresas->count() === 1 ? 'checked' : '' }}>
                    <div>
                        <div class="text-13 font-500">{{ $emp->nome_display }}</div>
                        <div class="text-11 text-muted">{{ $emp->cnpj }}</div>
                    </div>
                </label>
            @endforeach
        </div>
        <div id="avisoMultiEmpresa" class="mt-12 text-11" style="padding:8px;background:var(--bg-secondary);border-radius:6px;display:none">
            <i class="fas fa-info-circle text-primary"></i>
            Múltiplas empresas selecionadas — as colunas <strong>Empresa</strong> e <strong>CNPJ</strong> serão adicionadas automaticamente.
        </div>
    </div>

    {{-- ── Filtros + Campos + Formato ── --}}
    <div class="flex flex-col gap-16">

        {{-- Filtros --}}
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="fas fa-filter"></i> Filtros</div></div>
            <div class="flex flex-col gap-14">

                <div class="form-group">
                    <label class="form-label">Situação</label>
                    <div class="flex gap-16">
                        <label class="flex gap-8 items-center text-13" style="cursor:pointer">
                            <input type="radio" name="status_filtro" value="ativos" checked>
                            Somente ativos
                        </label>
                        <label class="flex gap-8 items-center text-13" style="cursor:pointer">
                            <input type="radio" name="status_filtro" value="todos">
                            Incluir demitidos
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Intervalo de admissão <span class="text-muted text-11">(opcional)</span></label>
                    <div class="flex gap-10 items-center">
                        <div>
                            <div class="text-11 text-muted mb-4">De</div>
                            <input type="date" name="admissao_de" class="form-control" style="max-width:150px">
                        </div>
                        <div style="margin-top:16px;color:var(--text-muted)">—</div>
                        <div>
                            <div class="text-11 text-muted mb-4">Até</div>
                            <input type="date" name="admissao_ate" class="form-control" style="max-width:150px">
                        </div>
                        <div style="margin-top:16px">
                            <button type="button" class="btn btn-xs btn-secondary" onclick="limparDatas()" title="Limpar datas">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-11 text-muted mt-4">Deixe em branco para exportar todos os períodos.</div>
                </div>

            </div>
        </div>

        {{-- Campos --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-columns"></i> Campos a exportar</div>
                <div class="flex gap-8">
                    <button type="button" class="btn btn-xs btn-secondary" onclick="selecionarCampos(true)">Todos</button>
                    <button type="button" class="btn btn-xs btn-secondary" onclick="selecionarCampos(false)">Nenhum</button>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                @foreach($campos as $chave => $label)
                    @php
                        $defaultOn    = in_array($chave, ['nome','cpf','data_admissao','setor','funcao','status']);
                        $isCalculado  = in_array($chave, ['idade','tempo_empresa']);
                    @endphp
                    <label class="flex gap-8 items-center text-13" style="cursor:pointer;padding:4px 6px;border-radius:4px" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background=''">
                        <input type="checkbox" name="campos[]" value="{{ $chave }}" class="campo-check" {{ $defaultOn ? 'checked' : '' }}>
                        {{ $label }}
                        @if($isCalculado)<span class="badge badge-info" style="font-size:9px">Calculado</span>@endif
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Formato --}}
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="fas fa-file"></i> Formato de saída</div></div>
            <div class="flex gap-16">
                <label class="flex gap-8 items-center text-13" style="cursor:pointer">
                    <input type="radio" name="formato" value="xlsx" checked>
                    <i class="fas fa-file-excel text-success"></i> Excel (.xlsx)
                </label>
                <label class="flex gap-8 items-center text-13" style="cursor:pointer">
                    <input type="radio" name="formato" value="csv">
                    <i class="fas fa-file-csv text-primary"></i> CSV (.csv)
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" id="btnExportar">
            <i class="fas fa-download"></i> Exportar Dados
        </button>
        <div id="loadingExport" style="display:none" class="text-center text-muted text-13">
            <i class="fas fa-spinner fa-spin"></i> Gerando arquivo, aguarde...
        </div>
    </div>
</div>
</form>

<script>
function selecionarTodas(on) {
    document.querySelectorAll('.emp-check').forEach(c => c.checked = on);
    verificarMultiEmpresa();
}
function selecionarCampos(on) {
    document.querySelectorAll('.campo-check').forEach(c => c.checked = on);
}
function verificarMultiEmpresa() {
    const marcadas = document.querySelectorAll('.emp-check:checked').length;
    document.getElementById('avisoMultiEmpresa').style.display = marcadas > 1 ? '' : 'none';
}
function limparDatas() {
    document.querySelector('[name=admissao_de]').value  = '';
    document.querySelector('[name=admissao_ate]').value = '';
}
document.querySelectorAll('.emp-check').forEach(c => c.addEventListener('change', verificarMultiEmpresa));

document.getElementById('formExport').addEventListener('submit', function() {
    document.getElementById('btnExportar').disabled = true;
    document.getElementById('loadingExport').style.display = '';
    setTimeout(() => {
        document.getElementById('btnExportar').disabled = false;
        document.getElementById('loadingExport').style.display = 'none';
    }, 6000);
});

verificarMultiEmpresa();
</script>
@endsection
