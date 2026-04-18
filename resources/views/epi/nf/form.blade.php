@extends('layouts.app')
@section('title','Nova Nota Fiscal de Entrada')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-file-invoice"></i> Nova Nota Fiscal de Entrada</h1>
        <div class="text-muted text-13">O estoque será atualizado automaticamente ao salvar.</div>
    </div>
    <a href="{{ route('nf-entradas.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

@if(session('error'))<div class="alert alert-danger mb-16">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger mb-16">
        <ul class="mb-0" style="padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

{{-- Upload XML --}}
<div class="card mb-16" style="border:2px dashed var(--border);background:var(--bg-secondary)">
    <div class="flex gap-16 items-center flex-wrap">
        <div>
            <div class="card-title"><i class="fas fa-upload"></i> Importar XML NF-e <span class="badge badge-info">Opcional</span></div>
            <div class="text-12 text-muted">Carregue o XML da nota para preencher automaticamente todos os campos.</div>
        </div>
        <div class="flex gap-8 items-center">
            <input type="file" id="xmlInput" accept=".xml" class="form-control" style="max-width:280px" onchange="importarXml(this)">
            <span id="xmlStatus" class="text-12 text-muted"></span>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('nf-entradas.store') }}" id="formNf">
@csrf

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

{{-- ── Dados da Nota ── --}}
<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-file-alt"></i> Dados da Nota Fiscal</div></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
            <label class="form-label">Número <span class="text-danger">*</span></label>
            <input type="text" name="numero" id="f_numero" value="{{ old('numero') }}" class="form-control" required maxlength="20">
        </div>
        <div class="form-group">
            <label class="form-label">Série <span class="text-danger">*</span></label>
            <input type="text" name="serie" id="f_serie" value="{{ old('serie','1') }}" class="form-control" required maxlength="5">
        </div>
        <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Chave de Acesso (44 dígitos)</label>
            <input type="text" name="chave_acesso" id="f_chave" value="{{ old('chave_acesso') }}" class="form-control" maxlength="44"
                   style="font-family:monospace;font-size:11px" oninput="validarChave(this)">
            <div id="chaveMsg" class="text-11 mt-2"></div>
        </div>
        <div class="form-group">
            <label class="form-label">Data de Emissão <span class="text-danger">*</span></label>
            <input type="date" name="data_emissao" id="f_emissao" value="{{ old('data_emissao') }}" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Data de Entrada <span class="text-danger">*</span></label>
            <input type="date" name="data_entrada" id="f_entrada" value="{{ old('data_entrada', date('Y-m-d')) }}" class="form-control" required>
        </div>
        <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Natureza da Operação</label>
            <input type="text" name="natureza_operacao" id="f_natureza" value="{{ old('natureza_operacao') }}" class="form-control">
        </div>
    </div>
    <hr style="border-color:var(--border);margin:8px 0">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
            <label class="form-label">Valor Produtos (R$)</label>
            <input type="number" name="valor_produtos" id="f_vprod" value="{{ old('valor_produtos','0.00') }}" class="form-control" step="0.01" min="0" oninput="calcularTotal()">
        </div>
        <div class="form-group">
            <label class="form-label">Frete (R$)</label>
            <input type="number" name="valor_frete" id="f_vfrete" value="{{ old('valor_frete','0.00') }}" class="form-control" step="0.01" min="0" oninput="calcularTotal()">
        </div>
        <div class="form-group">
            <label class="form-label">Desconto (R$)</label>
            <input type="number" name="valor_desconto" id="f_vdesc" value="{{ old('valor_desconto','0.00') }}" class="form-control" step="0.01" min="0" oninput="calcularTotal()">
        </div>
        <div class="form-group">
            <label class="form-label">Valor Total (R$) <span class="text-danger">*</span></label>
            <input type="number" name="valor_total" id="f_vtotal" value="{{ old('valor_total','0.00') }}" class="form-control" step="0.01" min="0" required style="font-weight:700;font-size:16px;color:var(--success)">
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Observações</label>
        <textarea name="observacoes" class="form-control" rows="2">{{ old('observacoes') }}</textarea>
    </div>
</div>

{{-- ── Fornecedor ── --}}
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-truck"></i> Fornecedor</div>
        <a href="{{ route('fornecedores.create') }}" target="_blank" class="btn btn-xs btn-secondary" title="Abrir cadastro em nova aba">
            <i class="fas fa-plus"></i> Novo
        </a>
    </div>

    {{-- Busca rápida --}}
    <div class="form-group mb-12">
        <label class="form-label">Buscar fornecedor existente</label>
        <div class="flex gap-8">
            <input type="text" id="buscaFornecedor" placeholder="CNPJ, razão social ou nome fantasia..."
                   class="form-control" onkeydown="if(event.key==='Enter'){event.preventDefault();buscarFornecedor(this.value)}">
            <button type="button" class="btn btn-secondary btn-sm" onclick="buscarFornecedor(document.getElementById('buscaFornecedor').value)">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
        <div id="fornecedorSugestoes" style="position:relative;z-index:50"></div>
    </div>
    <input type="hidden" name="fornecedor[_id]" id="f_forn_id">

    <hr style="border-color:var(--border);margin:8px 0">
    <div class="text-11 text-muted mb-10">Ou preencha manualmente:</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Razão Social <span class="text-danger">*</span></label>
            <input type="text" name="fornecedor[razao_social]" id="f_razao" value="{{ old('fornecedor.razao_social') }}" class="form-control" required>
        </div>
        <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Nome Fantasia</label>
            <input type="text" name="fornecedor[nome_fantasia]" id="f_fantasia" value="{{ old('fornecedor.nome_fantasia') }}" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">CNPJ</label>
            <input type="text" name="fornecedor[cnpj]" id="f_cnpj" value="{{ old('fornecedor.cnpj') }}" class="form-control" maxlength="18" oninput="mascaraCnpj(this)" placeholder="00.000.000/0000-00">
        </div>
        <div class="form-group">
            <label class="form-label">IE</label>
            <input type="text" name="fornecedor[inscricao_estadual]" id="f_ie" value="{{ old('fornecedor.inscricao_estadual') }}" class="form-control">
        </div>
        {{-- CEP + auto-fill --}}
        <div class="form-group" style="grid-column:span 2">
            <label class="form-label">CEP</label>
            <div class="flex gap-8">
                <input type="text" name="fornecedor[cep]" id="f_cep" value="{{ old('fornecedor.cep') }}" class="form-control" maxlength="9" oninput="mascaraCepNf(this)" placeholder="00000-000" style="max-width:120px">
                <button type="button" class="btn btn-xs btn-secondary" onclick="buscarCepNf()"><i class="fas fa-search"></i> Buscar CEP</button>
                <span id="nfCepStatus" class="text-11 text-muted" style="align-self:center"></span>
            </div>
        </div>
        <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Logradouro</label>
            <input type="text" name="fornecedor[logradouro]" id="f_logr" value="{{ old('fornecedor.logradouro') }}" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">Município</label>
            <input type="text" name="fornecedor[municipio]" id="f_mun" value="{{ old('fornecedor.municipio') }}" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">UF</label>
            <input type="text" name="fornecedor[uf]" id="f_uf" value="{{ old('fornecedor.uf') }}" class="form-control" maxlength="2" oninput="this.value=this.value.toUpperCase()">
        </div>
        <div class="form-group">
            <label class="form-label">Telefone</label>
            <input type="text" name="fornecedor[telefone]" id="f_tel" value="{{ old('fornecedor.telefone') }}" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="fornecedor[email]" id="f_email" value="{{ old('fornecedor.email') }}" class="form-control">
        </div>
    </div>
</div>
</div>{{-- fim grid 2 cols --}}

{{-- ── Itens ── --}}
<div class="card mt-20">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-hard-hat"></i> Itens da Nota <span id="contadorItens" class="badge badge-info ml-8">0 itens</span></div>
        <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalEpi()"><i class="fas fa-plus"></i> Adicionar Item</button>
    </div>

    <div id="semItens" class="text-center py-24 text-muted text-13">
        Clique em <strong>+ Adicionar Item</strong> para inserir EPIs desta nota.
    </div>

    <table class="table" id="tabelaItens" style="display:none;font-size:12px">
        <thead>
            <tr>
                <th style="width:28%">EPI</th>
                <th style="width:10%">Tamanho</th>
                <th style="width:7%">Qtd <span class="text-danger">*</span></th>
                <th style="width:10%">Vlr Unit. <span class="text-danger">*</span></th>
                <th style="width:10%">Vlr Total</th>
                <th style="width:9%">Lote</th>
                <th style="width:9%">Validade CA</th>
                <th style="width:9%">Val. Produto</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="corpoItens"></tbody>
    </table>

    <div class="flex justify-end gap-16 mt-8 px-12" id="totalItens" style="display:none!important;padding:10px;border-top:1px solid var(--border)">
        <span class="text-13 text-muted">Subtotal:</span>
        <span class="font-500 text-14" id="subtotalItens">R$ 0,00</span>
    </div>
</div>

<div class="flex justify-end gap-12 mt-20">
    <a href="{{ route('nf-entradas.index') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary btn-lg" onclick="return validarForm()">
        <i class="fas fa-save"></i> Salvar Nota e Atualizar Estoque
    </button>
</div>
</form>

{{-- ── Template de item ── --}}
<template id="tplItem">
    <tr class="item-row" data-idx="__IDX__">
        {{-- Campos ocultos com dados do EPI --}}
        <input type="hidden" name="itens[__IDX__][nome]"             class="item-nome">
        <input type="hidden" name="itens[__IDX__][tipo]"             class="item-tipo">
        <input type="hidden" name="itens[__IDX__][marca]"            class="item-marca">
        <input type="hidden" name="itens[__IDX__][numero_ca]"        class="item-ca">
        <input type="hidden" name="itens[__IDX__][unidade]"          class="item-unidade" value="un">
        <input type="hidden" name="itens[__IDX__][codigo_fornecedor]" class="item-cod-forn">
        <input type="hidden" name="itens[__IDX__][fabricante]"       class="item-fabricante">
        <input type="hidden" name="itens[__IDX__][tamanho_id]"       class="item-tamanho-id">
        {{-- Célula EPI (display) --}}
        <td>
            <div class="font-500 text-13 item-nome-display"></div>
            <div style="display:flex;gap:6px;margin-top:3px;flex-wrap:wrap">
                <span class="badge badge-secondary item-tipo-badge" style="font-size:10px"></span>
                <span class="item-marca-display text-11 text-muted"></span>
                <span class="item-ca-badge" style="font-size:10px"></span>
            </div>
        </td>
        {{-- Tamanho --}}
        <td class="text-center">
            <span class="item-tamanho-badge text-muted text-12">—</span>
        </td>
        {{-- Qtd --}}
        <td><input type="number" name="itens[__IDX__][quantidade]" class="form-control form-control-sm item-qty" required min="1" step="1" value="1" oninput="calcularLinha(this)"></td>
        {{-- Vlr Unit --}}
        <td><input type="number" name="itens[__IDX__][valor_unitario]" class="form-control form-control-sm item-vunit" required min="0" step="0.01" value="0.00" oninput="calcularLinha(this)"></td>
        {{-- Vlr Total --}}
        <td><input type="number" name="itens[__IDX__][valor_total]" class="form-control form-control-sm item-vtotal" readonly style="background:var(--bg-secondary);font-weight:600" value="0.00"></td>
        {{-- Lote --}}
        <td><input type="text" name="itens[__IDX__][lote]" class="form-control form-control-sm" placeholder="Lote" maxlength="60"></td>
        {{-- Validade CA --}}
        <td><input type="date" name="itens[__IDX__][validade_ca]" class="form-control form-control-sm item-validade-ca"></td>
        {{-- Validade Produto --}}
        <td><input type="date" name="itens[__IDX__][data_validade]" class="form-control form-control-sm"></td>
        <td>
            <button type="button" class="btn btn-xs btn-danger" onclick="removerItem(this)" title="Remover">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>
</template>

{{-- ── Modal Selecionar / Criar EPI ── --}}
<div id="modalEpi" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;align-items:center;justify-content:center">
<div style="background:var(--bg-card);border-radius:var(--r-md);padding:24px;width:580px;max-width:98vw;max-height:92vh;overflow-y:auto">

    {{-- Passo 1: busca --}}
    <div id="epiStep1">
        <div class="flex justify-between items-center mb-16">
            <h3 style="margin:0;font-size:16px"><i class="fas fa-hard-hat"></i> Selecionar EPI</h3>
            <button type="button" onclick="fecharModalEpi()" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted)">×</button>
        </div>

        <div class="flex gap-8 mb-10">
            <input type="text" id="buscaEpiInput" class="form-control" placeholder="Nome, CA ou marca..." onkeydown="if(event.key==='Enter'){event.preventDefault();buscarEpis()}">
            <button type="button" class="btn btn-secondary" onclick="buscarEpis()"><i class="fas fa-search"></i></button>
            <button type="button" class="btn btn-primary" onclick="mostrarFormNovoEpi()"><i class="fas fa-plus"></i> Novo EPI</button>
        </div>
        <div id="epiResultados" style="max-height:320px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;min-height:60px">
            <div class="text-center py-16 text-muted text-13">Digite para buscar EPIs cadastrados.</div>
        </div>
    </div>

    {{-- Passo 1b: form completo novo EPI (idêntico ao catálogo) --}}
    <div id="epiFormNovo" style="display:none">
        <div class="flex items-center gap-10 mb-16">
            <button type="button" onclick="voltarBusca()" class="btn btn-xs btn-secondary"><i class="fas fa-arrow-left"></i></button>
            <h3 style="margin:0;font-size:16px"><i class="fas fa-plus"></i> Novo EPI — Cadastro Completo</h3>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">

            {{-- Nome --}}
            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Nome <span class="text-danger">*</span></label>
                <input type="text" id="novoEpiNome" class="form-control" required placeholder="Ex: Luva de Vaqueta">
            </div>

            {{-- Tipo --}}
            <div class="form-group">
                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                <select id="novoEpiTipo" class="form-control">
                    @foreach(['Capacete','Luva','Óculos','Protetor Auricular','Calçado de Segurança','Respirador','Cinto de Segurança','Colete','Uniforme','Outros'] as $tp)
                        <option>{{ $tp }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="form-group">
                <label class="form-label">Status</label>
                <select id="novoEpiStatus" class="form-control">
                    <option value="Ativo">Ativo</option>
                    <option value="Inativo">Inativo</option>
                </select>
            </div>

            {{-- Número CA com busca CAEPI --}}
            <div class="form-group">
                <label class="form-label">
                    Número do CA
                    <span id="novoEpiCaLoading" style="display:none;font-size:10px;color:var(--brand);margin-left:6px">
                        <i class="fas fa-spinner fa-spin"></i> Consultando CAEPI...
                    </span>
                </label>
                <div style="display:flex;gap:6px">
                    <input type="text" id="novoEpiCa" class="form-control" placeholder="Ex: 498232"
                           oninput="novoEpiCaInput(this.value)">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="novoEpiBuscarCa()" title="Consultar CAEPI">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div id="novoEpiCaResultado" style="display:none;margin-top:6px;padding:10px 12px;border-radius:6px;font-size:11px;border:1px solid var(--border);background:var(--bg-secondary)"></div>
            </div>

            {{-- Validade CA --}}
            <div class="form-group">
                <label class="form-label">Validade do CA
                    <span style="font-size:10px;color:var(--text-muted);margin-left:4px">(auto pelo CA)</span>
                </label>
                <input type="date" id="novoEpiValidadeCa" class="form-control">
            </div>

            {{-- Fabricante --}}
            <div class="form-group">
                <label class="form-label">Fabricante</label>
                <input type="text" id="novoEpiFabricante" class="form-control">
            </div>

            {{-- Marca --}}
            <div class="form-group" style="position:relative">
                <label class="form-label">Marca</label>
                <input type="text" id="novoEpiMarca" class="form-control" autocomplete="off"
                       oninput="autocompleteMarca(this.value,'novoEpiMarca','marcaSugNovoEpi')">
                <div id="marcaSugNovoEpi" style="position:relative"></div>
            </div>

            {{-- Fornecedor --}}
            <div class="form-group">
                <label class="form-label">Fornecedor</label>
                <input type="text" id="novoEpiFornecedor" class="form-control" placeholder="Nome do fornecedor">
            </div>

            {{-- Unidade --}}
            <div class="form-group">
                <label class="form-label">Unidade</label>
                <select id="novoEpiUnidade" class="form-control">
                    <option value="un">un</option>
                    <option value="par">par</option>
                    <option value="kit">kit</option>
                    <option value="cx">cx</option>
                    <option value="rolo">rolo</option>
                </select>
            </div>

            {{-- Custo Unitário --}}
            <div class="form-group">
                <label class="form-label">Custo Unitário (R$)</label>
                <input type="number" id="novoEpiCusto" class="form-control" step="0.01" min="0" placeholder="0,00">
            </div>

            {{-- Vida Útil --}}
            <div class="form-group">
                <label class="form-label">Vida Útil (dias)</label>
                <input type="number" id="novoEpiVidaUtil" class="form-control" min="0" placeholder="Ex: 365">
            </div>

            {{-- Estoque Mínimo --}}
            <div class="form-group">
                <label class="form-label">Estoque Mínimo</label>
                <input type="number" id="novoEpiEstoqueMin" class="form-control" min="0" value="0">
            </div>

            {{-- Descrição --}}
            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Descrição</label>
                <textarea id="novoEpiDescricao" class="form-control" rows="2" placeholder="Descrição ou observações sobre o EPI"></textarea>
            </div>

            {{-- Tamanhos --}}
            <div class="form-group" style="grid-column:span 2">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;font-size:13px">
                    <input type="checkbox" id="novoEpiTemTamanho" style="width:16px;height:16px;cursor:pointer;accent-color:var(--brand)"
                           onchange="document.getElementById('novoEpiTamanhosBox').style.display=this.checked?'':'none'">
                    Este EPI requer tamanho (ex: botinas, luvas)
                </label>
                <div id="novoEpiTamanhosBox" style="display:none;margin-top:10px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:6px;padding:12px">
                    <div style="font-size:12px;font-weight:600;color:var(--text-2);margin-bottom:8px"><i class="fas fa-ruler-combined"></i> Tamanhos disponíveis</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach($tamanhos as $tam)
                        <label class="novo-epi-tam-chip" style="display:flex;align-items:center;gap:5px;padding:5px 10px;border:1.5px solid var(--border);border-radius:20px;cursor:pointer;font-size:12px;font-weight:500;transition:all .15s">
                            <input type="checkbox" class="novo-epi-tam" value="{{ $tam->id }}" data-codigo="{{ $tam->codigo }}"
                                   style="cursor:pointer;accent-color:var(--brand)" onchange="toggleNovoEpiChip(this)">
                            {{ $tam->codigo }}
                            @if($tam->descricao)<span style="font-size:10px;color:var(--text-muted)">({{ $tam->descricao }})</span>@endif
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>{{-- fim grid --}}

        <div id="novoEpiErro" class="alert alert-danger mt-10" style="display:none"></div>
        <div class="flex justify-end gap-10 mt-16">
            <button type="button" class="btn btn-secondary" onclick="voltarBusca()">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnCriarEpi" onclick="criarNovoEpi()">
                <i class="fas fa-save"></i> Criar EPI e Selecionar
            </button>
        </div>
    </div>

    {{-- Passo 2: grade e quantidades --}}
    <div id="epiStep2" style="display:none">
        <div class="flex items-center gap-10 mb-12">
            <button type="button" onclick="voltarBusca()" class="btn btn-xs btn-secondary"><i class="fas fa-arrow-left"></i></button>
            <div>
                <div class="font-500 text-14" id="step2EpiNome"></div>
                <div class="flex gap-6 mt-2" id="step2EpiBadges"></div>
            </div>
        </div>

        {{-- Valor unitário global --}}
        <div class="flex gap-12 items-end mb-14">
            <div class="form-group mb-0">
                <label class="form-label text-12">Valor Unitário (R$) <span class="text-danger">*</span></label>
                <input type="number" id="step2Vunit" class="form-control" step="0.01" min="0" value="0.00" style="max-width:130px" oninput="atualizarTotaisGrade()">
            </div>
            <div class="form-group mb-0">
                <label class="form-label text-12">Lote</label>
                <input type="text" id="step2Lote" class="form-control" maxlength="60" style="max-width:140px">
            </div>
            <div class="form-group mb-0">
                <label class="form-label text-12">Validade CA</label>
                <input type="date" id="step2ValidadeCa" class="form-control" style="max-width:150px">
            </div>
            <div class="form-group mb-0">
                <label class="form-label text-12">Validade Produto</label>
                <input type="date" id="step2DataValidade" class="form-control" style="max-width:150px">
            </div>
        </div>

        {{-- Grade --}}
        <div id="step2GradeWrap">
            {{-- sem tamanho --}}
            <div id="step2SemTamanho" style="display:none">
                <div class="form-group">
                    <label class="form-label">Quantidade <span class="text-danger">*</span></label>
                    <input type="number" id="step2QtdSemTam" class="form-control" min="1" step="1" value="1" style="max-width:100px">
                </div>
            </div>
            {{-- com tamanho --}}
            <div id="step2ComTamanho" style="display:none">
                <div class="flex gap-6 mb-10 flex-wrap">
                    <button type="button" class="btn btn-xs btn-secondary" onclick="selGrade('todos')">Todos</button>
                    <button type="button" class="btn btn-xs btn-secondary" onclick="selGrade('pares')">Pares</button>
                    <button type="button" class="btn btn-xs btn-secondary" onclick="selGrade('impares')">Ímpares</button>
                    <button type="button" class="btn btn-xs btn-secondary" onclick="selGrade('roupas')">Roupas</button>
                    <button type="button" class="btn btn-xs btn-secondary" onclick="selGrade('nenhum')">Limpar</button>
                </div>
                <div id="step2GradeItens" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;max-height:260px;overflow-y:auto"></div>
            </div>
        </div>

        <div class="flex justify-end gap-10 mt-16">
            <button type="button" class="btn btn-secondary" onclick="fecharModalEpi()">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="confirmarSelecao()"><i class="fas fa-check"></i> Adicionar à Nota</button>
        </div>
    </div>

</div>
</div>

<script>
const TAMANHOS = @json($tamanhos);
let itemIdx = 0;
let epiSelecionado = null; // EPI escolhido no modal

// ─── Linha de item ───────────────────────────────────────────────────────────

function adicionarItem(dados = {}) {
    const html  = document.getElementById('tplItem').innerHTML.replace(/__IDX__/g, itemIdx);
    const tbody = document.getElementById('corpoItens');
    tbody.insertAdjacentHTML('beforeend', html);
    const row = tbody.lastElementChild;
    row.dataset.idx = itemIdx;

    // Preenche campos ocultos
    if (dados.nome)              row.querySelector('.item-nome').value        = dados.nome;
    if (dados.tipo)              row.querySelector('.item-tipo').value        = dados.tipo;
    if (dados.marca)             row.querySelector('.item-marca').value       = dados.marca;
    if (dados.numero_ca)         row.querySelector('.item-ca').value          = dados.numero_ca;
    if (dados.unidade)           row.querySelector('.item-unidade').value     = dados.unidade;
    if (dados.codigo_fornecedor) row.querySelector('.item-cod-forn').value    = dados.codigo_fornecedor;
    if (dados.fabricante)        row.querySelector('.item-fabricante').value  = dados.fabricante;
    if (dados.tamanho_id)        row.querySelector('.item-tamanho-id').value  = dados.tamanho_id;

    // Campos editáveis
    if (dados.quantidade)    row.querySelector('.item-qty').value   = dados.quantidade;
    if (dados.valor_unitario)row.querySelector('.item-vunit').value = dados.valor_unitario;
    if (dados.lote)          { const l=row.querySelector('[name$="[lote]"]');          if(l) l.value=dados.lote; }
    if (dados.validade_ca)   { const v=row.querySelector('.item-validade-ca');         if(v) v.value=dados.validade_ca; }
    if (dados.data_validade) { const d=row.querySelector('[name$="[data_validade]"]'); if(d) d.value=dados.data_validade; }

    // Display
    row.querySelector('.item-nome-display').textContent = dados.nome || '';
    if (dados.tipo)  row.querySelector('.item-tipo-badge').textContent = dados.tipo;
    if (dados.marca) row.querySelector('.item-marca-display').textContent = dados.marca;

    if (dados.tamanho_id) {
        const tm = TAMANHOS.find(t => t.id == dados.tamanho_id);
        if (tm) row.querySelector('.item-tamanho-badge').innerHTML = `<span class="badge badge-secondary">${tm.codigo}</span>`;
    }

    // Badge de situação CA (se disponível no objeto epi)
    if (dados.ca_situacao) {
        const sit = (dados.ca_situacao || '').toUpperCase();
        const cor = sit === 'VÁLIDO' ? 'badge-success' : ['CANCELADO','VENCIDO','INATIVO'].includes(sit) ? 'badge-danger' : 'badge-warning';
        row.querySelector('.item-ca-badge').innerHTML = `<span class="badge ${cor}" style="font-size:10px">CA ${dados.numero_ca} — ${sit}</span>`;
    } else if (dados.numero_ca) {
        row.querySelector('.item-ca-badge').innerHTML = `<span class="badge badge-secondary" style="font-size:10px">CA ${dados.numero_ca}</span>`;
    }

    itemIdx++;
    atualizarUI();
    calcularLinha(row.querySelector('.item-qty'));
    return row;
}

function removerItem(btn) { btn.closest('tr').remove(); atualizarUI(); atualizarSubtotal(); }

function calcularLinha(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value)  || 0;
    const vunit = parseFloat(row.querySelector('.item-vunit').value) || 0;
    row.querySelector('.item-vtotal').value = (qty * vunit).toFixed(2);
    atualizarSubtotal();
}

function atualizarUI() {
    const n = document.querySelectorAll('.item-row').length;
    document.getElementById('semItens').style.display    = n ? 'none' : '';
    document.getElementById('tabelaItens').style.display = n ? '' : 'none';
    document.getElementById('totalItens').style.display  = n ? 'flex' : 'none';
    document.getElementById('contadorItens').textContent = n + (n===1?' item':' itens');
}

function atualizarSubtotal() {
    let t = 0;
    document.querySelectorAll('.item-vtotal').forEach(i => t += parseFloat(i.value)||0);
    document.getElementById('subtotalItens').textContent = 'R$ ' + t.toLocaleString('pt-BR',{minimumFractionDigits:2});
}

// ─── Modal Selecionar EPI ────────────────────────────────────────────────────

function abrirModalEpi() {
    epiSelecionado = null;
    mostrarStep(1);
    document.getElementById('buscaEpiInput').value = '';
    document.getElementById('epiResultados').innerHTML = '<div class="text-center py-16 text-muted text-13">Digite para buscar EPIs cadastrados.</div>';
    document.getElementById('modalEpi').style.display = 'flex';
    setTimeout(() => document.getElementById('buscaEpiInput').focus(), 50);
}

function fecharModalEpi() { document.getElementById('modalEpi').style.display = 'none'; }

function mostrarStep(n) {
    document.getElementById('epiStep1').style.display    = n===1 ? '' : 'none';
    document.getElementById('epiFormNovo').style.display = n===1.5 ? '' : 'none';
    document.getElementById('epiStep2').style.display    = n===2 ? '' : 'none';
}

function voltarBusca() { mostrarStep(1); }

function buscarEpis() {
    const q = document.getElementById('buscaEpiInput').value.trim();
    const box = document.getElementById('epiResultados');
    box.innerHTML = '<div class="text-center py-12 text-muted text-13"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
    fetch(`{{ route('epis.buscar') }}?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(lista => {
            if (!lista.length) { box.innerHTML='<div class="text-center py-16 text-muted text-13">Nenhum EPI encontrado. <a href="#" onclick="mostrarFormNovoEpi();return false">Criar novo?</a></div>'; return; }
            box.innerHTML = lista.map(e => `
                <div onclick='selecionarEpi(${JSON.stringify(e)})'
                     style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center"
                     onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background=''">
                    <div>
                        <div class="font-500 text-13">${e.nome}</div>
                        <div style="font-size:11px;color:var(--text-muted)">${e.tipo}${e.marca?' · '+e.marca:''}${e.numero_ca?' · CA '+e.numero_ca:''}</div>
                    </div>
                    <i class="fas fa-chevron-right text-muted" style="font-size:11px"></i>
                </div>`).join('');
        });
}

function selecionarEpi(epi) {
    epiSelecionado = epi;
    // Cabeçalho do step2
    document.getElementById('step2EpiNome').textContent = epi.nome;
    const badges = document.getElementById('step2EpiBadges');
    badges.innerHTML = `<span class="badge badge-secondary" style="font-size:11px">${epi.tipo}</span>`;
    if (epi.marca) badges.innerHTML += `<span class="badge badge-info" style="font-size:11px">${epi.marca}</span>`;
    if (epi.numero_ca) badges.innerHTML += `<span class="badge badge-secondary" style="font-size:11px">CA ${epi.numero_ca}</span>`;

    // Preenche validade CA se disponível
    if (epi.validade_ca) document.getElementById('step2ValidadeCa').value = epi.validade_ca.substring(0,10);
    if (epi.custo_unitario) document.getElementById('step2Vunit').value = epi.custo_unitario;

    // Grade
    const tamanhos = epi.tamanhos || [];
    if (epi.tem_tamanho && tamanhos.length) {
        document.getElementById('step2SemTamanho').style.display = 'none';
        document.getElementById('step2ComTamanho').style.display = '';
        const grid = document.getElementById('step2GradeItens');
        grid.innerHTML = tamanhos.map(tm => `
            <label style="border:1px solid var(--border);border-radius:8px;padding:8px;cursor:pointer;display:flex;flex-direction:column;gap:6px" class="grade-card-s2">
                <div style="display:flex;gap:6px;align-items:center">
                    <input type="checkbox" class="grade-check-s2" value="${tm.id}" data-codigo="${tm.codigo}"
                           data-num="${isNaN(tm.codigo)?0:parseInt(tm.codigo)}" onchange="toggleGradeS2(this)" style="width:14px;height:14px">
                    <span style="font-weight:700;font-size:14px">${tm.codigo}</span>
                </div>
                <input type="number" class="form-control form-control-sm grade-qty-s2" min="1" value="1" disabled style="font-size:12px;padding:4px 6px">
            </label>`).join('');
    } else {
        document.getElementById('step2SemTamanho').style.display = '';
        document.getElementById('step2ComTamanho').style.display = 'none';
    }

    mostrarStep(2);
}

function toggleGradeS2(cb) {
    const card = cb.closest('.grade-card-s2');
    const qty  = card.querySelector('.grade-qty-s2');
    qty.disabled = !cb.checked;
    card.style.borderColor = cb.checked ? 'var(--primary)' : 'var(--border)';
    card.style.background  = cb.checked ? 'rgba(var(--primary-rgb),.05)' : '';
    if (cb.checked) { qty.focus(); qty.select(); }
}

function selGrade(tipo) {
    document.querySelectorAll('.grade-check-s2').forEach(cb => {
        const num = parseInt(cb.dataset.num) || 0;
        const cod = cb.dataset.codigo;
        const roupas = ['PP','P','M','G','GG','G1','G2','G3','XG'];
        let sel = false;
        if (tipo==='todos')   sel = true;
        if (tipo==='nenhum')  sel = false;
        if (tipo==='pares')   sel = num > 0 && num % 2 === 0;
        if (tipo==='impares') sel = num > 0 && num % 2 !== 0;
        if (tipo==='roupas')  sel = roupas.includes(cod);
        cb.checked = sel;
        toggleGradeS2(cb);
    });
}

function atualizarTotaisGrade() { /* reservado */ }

function confirmarSelecao() {
    if (!epiSelecionado) return;
    const vunit       = document.getElementById('step2Vunit').value || '0.00';
    const lote        = document.getElementById('step2Lote').value;
    const validadeCa  = document.getElementById('step2ValidadeCa').value;
    const dataVal     = document.getElementById('step2DataValidade').value;

    const temTamanho  = epiSelecionado.tem_tamanho && (epiSelecionado.tamanhos||[]).length;

    const dadosBase = {
        nome:        epiSelecionado.nome,
        tipo:        epiSelecionado.tipo,
        marca:       epiSelecionado.marca       || '',
        numero_ca:   epiSelecionado.numero_ca   || '',
        ca_situacao: epiSelecionado.ca_situacao || '',
        unidade:     epiSelecionado.unidade     || 'un',
        fabricante:  epiSelecionado.fabricante  || '',
        valor_unitario: vunit,
        lote, validade_ca: validadeCa, data_validade: dataVal,
    };

    if (temTamanho) {
        const selecionados = [];
        document.querySelectorAll('.grade-check-s2:checked').forEach(cb => {
            const qty = parseInt(cb.closest('.grade-card-s2').querySelector('.grade-qty-s2').value) || 1;
            selecionados.push({ tamanho_id: cb.value, qty });
        });
        if (!selecionados.length) { alert('Selecione pelo menos um tamanho.'); return; }
        selecionados.forEach(s => adicionarItem({ ...dadosBase, quantidade: s.qty, tamanho_id: s.tamanho_id }));
    } else {
        const qty = parseInt(document.getElementById('step2QtdSemTam').value) || 1;
        adicionarItem({ ...dadosBase, quantidade: qty });
    }

    fecharModalEpi();
}

// ─── Mini form Novo EPI ──────────────────────────────────────────────────────

function mostrarFormNovoEpi() {
    // Preenche nome com o que foi digitado na busca
    document.getElementById('novoEpiNome').value       = document.getElementById('buscaEpiInput').value;
    document.getElementById('novoEpiTipo').value       = 'Capacete';
    document.getElementById('novoEpiStatus').value     = 'Ativo';
    document.getElementById('novoEpiCa').value         = '';
    document.getElementById('novoEpiValidadeCa').value = '';
    document.getElementById('novoEpiFabricante').value = '';
    document.getElementById('novoEpiMarca').value      = '';
    document.getElementById('novoEpiFornecedor').value = '';
    document.getElementById('novoEpiUnidade').value    = 'un';
    document.getElementById('novoEpiCusto').value      = '';
    document.getElementById('novoEpiVidaUtil').value   = '';
    document.getElementById('novoEpiEstoqueMin').value = '0';
    document.getElementById('novoEpiDescricao').value  = '';
    document.getElementById('novoEpiTemTamanho').checked = false;
    document.getElementById('novoEpiTamanhosBox').style.display = 'none';
    document.getElementById('novoEpiCaResultado').style.display = 'none';
    document.querySelectorAll('.novo-epi-tam').forEach(cb => { cb.checked = false; toggleNovoEpiChip(cb); });
    document.getElementById('marcaSugNovoEpi').innerHTML = '';
    document.getElementById('novoEpiErro').style.display = 'none';
    mostrarStep(1.5);
    document.getElementById('novoEpiNome').focus();
}

// ── CA CAEPI no modal ────────────────────────────────────────────────────────
let novoEpiCaTimer = null;
function novoEpiCaInput(val) {
    clearTimeout(novoEpiCaTimer);
    if (val.trim().length >= 3) {
        novoEpiCaTimer = setTimeout(() => novoEpiBuscarCa(), 800);
    } else {
        document.getElementById('novoEpiCaResultado').style.display = 'none';
    }
}

async function novoEpiBuscarCa() {
    const ca = document.getElementById('novoEpiCa').value.trim();
    if (!ca) return;
    const loading = document.getElementById('novoEpiCaLoading');
    const result  = document.getElementById('novoEpiCaResultado');
    loading.style.display = '';
    result.style.display  = 'none';
    try {
        const resp = await fetch(`/api/ca/${encodeURIComponent(ca)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const data = await resp.json();
        loading.style.display = 'none';
        result.style.display  = '';

        if (!resp.ok || !data.encontrado) {
            result.style.borderColor = 'var(--danger)';
            result.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i>
                <strong style="color:var(--danger)"> CA não encontrado no CAEPI.</strong>
                <span style="color:var(--text-muted);font-size:10px"> Preencha os campos manualmente.</span>`;
            return;
        }

        const sit = (data.situacao || '').toLowerCase();
        const cor = sit.includes('válido') ? 'var(--success)' : 'var(--danger)';
        const validade = data.data_validade
            ? new Date(data.data_validade + 'T00:00:00').toLocaleDateString('pt-BR')
            : '—';

        result.style.borderColor = sit.includes('válido') ? 'var(--success)' : 'var(--danger)';
        result.innerHTML = `
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <span style="font-weight:700;font-size:12px">${data.nome_equipamento ?? '—'}</span>
                <span style="background:${cor};color:#fff;padding:2px 7px;border-radius:10px;font-size:10px;font-weight:700">${data.situacao ?? '—'}</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;font-size:11px;color:var(--text-2)">
                <div><span style="color:var(--text-muted)">Fabricante:</span> ${data.razao_social ?? '—'}</div>
                <div><span style="color:var(--text-muted)">Validade:</span> <strong style="color:${cor}">${validade}</strong></div>
                <div><span style="color:var(--text-muted)">Marca:</span> ${data.marca ?? '—'}</div>
                <div><span style="color:var(--text-muted)">Norma:</span> ${data.norma ?? '—'}</div>
            </div>
            <div style="margin-top:6px;font-size:10px;color:var(--brand)"><i class="fas fa-magic"></i> Dados preenchidos automaticamente.</div>`;

        // Auto-preenche campos do formulário
        if (data.data_validade && !document.getElementById('novoEpiValidadeCa').value)
            document.getElementById('novoEpiValidadeCa').value = data.data_validade;
        if (data.razao_social && !document.getElementById('novoEpiFabricante').value)
            document.getElementById('novoEpiFabricante').value = data.razao_social;
        if (data.nome_equipamento && !document.getElementById('novoEpiNome').value)
            document.getElementById('novoEpiNome').value = data.nome_equipamento;

    } catch(e) {
        loading.style.display = 'none';
        result.style.display  = '';
        result.style.borderColor = 'var(--warning)';
        result.innerHTML = `<i class="fas fa-wifi" style="color:var(--warning)"></i>
            <span style="color:var(--text-2)"> API CAEPI indisponível. Preencha manualmente.</span>`;
    }
}

function toggleNovoEpiChip(cb) {
    const label = cb.closest('label');
    if (!label) return;
    if (cb.checked) {
        label.style.borderColor = 'var(--brand)';
        label.style.background  = 'rgba(var(--brand-rgb),.1)';
        label.style.color       = 'var(--brand)';
    } else {
        label.style.borderColor = 'var(--border)';
        label.style.background  = '';
        label.style.color       = '';
    }
}

async function criarNovoEpi() {
    const nome = document.getElementById('novoEpiNome').value.trim();
    const tipo = document.getElementById('novoEpiTipo').value;
    const errBox = document.getElementById('novoEpiErro');

    if (!nome) { errBox.textContent = 'Nome é obrigatório.'; errBox.style.display = ''; return; }
    if (!tipo) { errBox.textContent = 'Tipo é obrigatório.'; errBox.style.display = ''; return; }
    errBox.style.display = 'none';

    const temTamanho = document.getElementById('novoEpiTemTamanho').checked;
    const tamanhoIds = temTamanho
        ? Array.from(document.querySelectorAll('.novo-epi-tam:checked')).map(c => c.value)
        : [];

    const btn = document.getElementById('btnCriarEpi');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    const fd = new FormData();
    fd.append('_token',         '{{ csrf_token() }}');
    fd.append('nome',           nome);
    fd.append('tipo',           tipo);
    fd.append('status',         document.getElementById('novoEpiStatus').value);
    fd.append('numero_ca',      document.getElementById('novoEpiCa').value);
    fd.append('validade_ca',    document.getElementById('novoEpiValidadeCa').value);
    fd.append('fabricante',     document.getElementById('novoEpiFabricante').value);
    fd.append('marca',          document.getElementById('novoEpiMarca').value);
    fd.append('fornecedor',     document.getElementById('novoEpiFornecedor').value);
    fd.append('unidade',        document.getElementById('novoEpiUnidade').value);
    fd.append('custo_unitario', document.getElementById('novoEpiCusto').value);
    fd.append('vida_util_dias', document.getElementById('novoEpiVidaUtil').value);
    fd.append('estoque_minimo', document.getElementById('novoEpiEstoqueMin').value);
    fd.append('descricao',      document.getElementById('novoEpiDescricao').value);
    fd.append('tem_tamanho',    temTamanho ? '1' : '0');
    tamanhoIds.forEach(id => fd.append('tamanho_ids[]', id));

    try {
        const res = await fetch('{{ route('epis.store') }}', {
            method: 'POST', body: fd, headers: { Accept: 'application/json' }
        });
        const data = await res.json();
        if (!res.ok) {
            const msgs = Object.values(data.errors || {}).flat().join(' ');
            errBox.textContent = msgs || 'Erro ao criar EPI.';
            errBox.style.display = '';
            return;
        }
        // Seleciona o EPI recém-criado e vai para step 2
        selecionarEpi(data);
    } catch(e) {
        errBox.textContent = 'Erro de conexão ao criar EPI.';
        errBox.style.display = '';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Criar EPI e Selecionar';
    }
}

// ─── Autocomplete Marca no mini-form ────────────────────────────────────────

let marcaTimer;
function autocompleteMarca(q, inputId, boxId) {
    clearTimeout(marcaTimer);
    const box = document.getElementById(boxId);
    if (!q || q.length < 1) { box.innerHTML=''; return; }
    marcaTimer = setTimeout(() => {
        fetch(`{{ route('api.marcas') }}?q=${encodeURIComponent(q)}`)
            .then(r=>r.json())
            .then(lista => {
                if (!lista.length) { box.innerHTML=''; return; }
                box.innerHTML = '<div style="position:absolute;left:0;right:0;background:var(--bg-card);border:1px solid var(--border);border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);z-index:300;max-height:180px;overflow-y:auto">' +
                    lista.map(m=>`<div style="padding:7px 12px;cursor:pointer;font-size:13px" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background=''" onclick="selecionarMarca('${m.replace(/'/g,"\\'")}','${inputId}','${boxId}')">${m}</div>`).join('') + '</div>';
            });
    }, 200);
}
function selecionarMarca(val, inputId, boxId) {
    document.getElementById(inputId).value = val;
    document.getElementById(boxId).innerHTML = '';
}

// ─── Busca de fornecedor ────────────────────────────────────────────────────

function buscarFornecedor(q) {
    q = q.trim();
    const box = document.getElementById('fornecedorSugestoes');
    if (!q) { box.innerHTML = ''; return; }
    box.innerHTML = '<div style="padding:8px 14px;font-size:12px;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
    fetch(`{{ route('nf-entradas.buscar-fornecedor') }}?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(lista => {
            if (!lista.length) { box.innerHTML = '<div style="padding:8px 14px;font-size:12px;color:var(--text-muted)">Nenhum fornecedor encontrado.</div>'; return; }
            box.innerHTML = '<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:6px;max-height:220px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.15)">' +
                lista.map(f => `<div style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border)" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background=''" onclick='preencherFornecedor(${JSON.stringify(f)})'><strong>${f.razao_social}</strong>${f.cnpj?' <span style=\'color:var(--text-muted);font-size:11px\'>'+f.cnpj+'</span>':''}<br><span style="font-size:11px;color:var(--text-muted)">${[f.municipio,f.uf].filter(Boolean).join('/')}</span></div>`).join('') +
                '</div>';
        });
}

function preencherFornecedor(f) {
    document.getElementById('f_forn_id').value  = f.id;
    document.getElementById('f_razao').value     = f.razao_social      || '';
    document.getElementById('f_fantasia').value  = f.nome_fantasia     || '';
    document.getElementById('f_cnpj').value      = f.cnpj              || '';
    document.getElementById('f_ie').value        = f.inscricao_estadual|| '';
    document.getElementById('f_logr').value      = f.logradouro        || '';
    document.getElementById('f_mun').value       = f.municipio         || '';
    document.getElementById('f_uf').value        = f.uf                || '';
    document.getElementById('f_cep').value       = f.cep               || '';
    document.getElementById('f_tel').value       = f.telefone          || '';
    document.getElementById('f_email').value     = f.email             || '';
    document.getElementById('buscaFornecedor').value = '';
    document.getElementById('fornecedorSugestoes').innerHTML = '';
}

// ─── CEP do fornecedor ───────────────────────────────────────────────────────

async function buscarCepNf() {
    const cep = document.getElementById('f_cep').value.replace(/\D/g,'');
    const status = document.getElementById('nfCepStatus');
    if (cep.length !== 8) { status.textContent='CEP inválido'; status.style.color='var(--danger)'; return; }
    status.textContent = 'Buscando...';
    try {
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if (data.erro) { status.textContent='✖ Não encontrado'; status.style.color='var(--danger)'; return; }
        document.getElementById('f_logr').value = data.logradouro || '';
        document.getElementById('f_mun').value  = data.localidade || '';
        document.getElementById('f_uf').value   = data.uf         || '';
        status.textContent='✔ Preenchido'; status.style.color='var(--success)';
    } catch { status.textContent='✖ Erro'; }
}
document.getElementById('f_cep').addEventListener('input', function() {
    mascaraCepNf(this);
    if (this.value.replace(/\D/g,'').length === 8) buscarCepNf();
});
function mascaraCepNf(i) { let v=i.value.replace(/\D/g,'').slice(0,8); if(v.length>5)v=v.replace(/^(\d{5})(\d{0,3}).*/,'$1-$2'); i.value=v; }

// ─── XML ─────────────────────────────────────────────────────────────────────

function importarXml(input) {
    const file = input.files[0]; if (!file) return;
    const status = document.getElementById('xmlStatus');
    status.textContent = 'Processando...'; status.style.color='';
    const fd = new FormData();
    fd.append('xml', file); fd.append('_token', '{{ csrf_token() }}');
    fetch('{{ route("nf-entradas.parse-xml") }}', {method:'POST',body:fd})
        .then(r=>r.json()).then(data => {
            if (data.erro) { status.textContent='✖ '+data.erro; status.style.color='var(--danger)'; return; }
            preencherComXml(data); status.textContent='✔ XML importado!'; status.style.color='var(--success)';
        }).catch(()=>{ status.textContent='✖ Erro ao enviar'; status.style.color='var(--danger)'; });
}

function preencherComXml(data) {
    const n=data.nota, f=data.fornecedor;
    if(n.numero)            set('f_numero',n.numero);
    if(n.serie)             set('f_serie',n.serie);
    if(n.chave_acesso)      { set('f_chave',n.chave_acesso); validarChave(document.getElementById('f_chave')); }
    if(n.data_emissao)      set('f_emissao',n.data_emissao);
    if(n.natureza_operacao) set('f_natureza',n.natureza_operacao);
    if(n.valor_produtos)    set('f_vprod',n.valor_produtos);
    if(n.valor_frete)       set('f_vfrete',n.valor_frete);
    if(n.valor_desconto)    set('f_vdesc',n.valor_desconto);
    if(n.valor_total)       set('f_vtotal',n.valor_total);
    if(f.razao_social)      set('f_razao',f.razao_social);
    if(f.nome_fantasia)     set('f_fantasia',f.nome_fantasia);
    if(f.cnpj)              set('f_cnpj',f.cnpj);
    if(f.logradouro)        set('f_logr',f.logradouro);
    if(f.municipio)         set('f_mun',f.municipio);
    if(f.uf)                set('f_uf',f.uf);
    if(f.cep)               set('f_cep',f.cep);
    if(f.telefone)          set('f_tel',f.telefone);
    document.getElementById('corpoItens').innerHTML='';
    itemIdx=0;
    (data.itens||[]).forEach(i => adicionarItem(i));
}

function set(id,val) { const el=document.getElementById(id); if(el) el.value=val; }

// ─── Helpers ─────────────────────────────────────────────────────────────────

function calcularTotal() {
    const p=parseFloat(document.getElementById('f_vprod').value)||0;
    const fr=parseFloat(document.getElementById('f_vfrete').value)||0;
    const d=parseFloat(document.getElementById('f_vdesc').value)||0;
    document.getElementById('f_vtotal').value=(p+fr-d).toFixed(2);
}
function validarChave(i) {
    i.value=i.value.replace(/\D/g,'');
    const msg=document.getElementById('chaveMsg');
    if(!i.value){msg.textContent='';return;}
    if(i.value.length===44){msg.textContent='✔ Chave válida';msg.style.color='var(--success)';}
    else{msg.textContent=i.value.length+'/44';msg.style.color='var(--danger)';}
}
function mascaraCnpj(i) {
    let v=i.value.replace(/\D/g,'').slice(0,14);
    if(v.length>12)v=v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2}).*/,'$1.$2.$3/$4-$5');
    else if(v.length>8)v=v.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4}).*/,'$1.$2.$3/$4');
    else if(v.length>5)v=v.replace(/^(\d{2})(\d{3})(\d{0,3}).*/,'$1.$2.$3');
    else if(v.length>2)v=v.replace(/^(\d{2})(\d{0,3}).*/,'$1.$2');
    i.value=v;
}
function validarForm() {
    if(!document.querySelectorAll('.item-row').length){alert('Adicione pelo menos um item.');return false;}
    return true;
}

// Fecha modal ao clicar no overlay
document.getElementById('modalEpi').addEventListener('click', function(e) { if(e.target===this)fecharModalEpi(); });
document.getElementById('buscaEpiInput').addEventListener('keydown', function(e) { if(e.key==='Enter'){e.preventDefault();buscarEpis();} });
</script>

<style>
.grade-card:hover { border-color: var(--primary) !important; background: rgba(var(--primary-rgb),.03); }
</style>
@endsection
