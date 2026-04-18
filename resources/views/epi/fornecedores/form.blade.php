@extends('layouts.app')
@section('title', $fornecedor ? 'Editar Fornecedor' : 'Novo Fornecedor')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-truck"></i> {{ $fornecedor ? 'Editar Fornecedor' : 'Novo Fornecedor' }}</h1>
    </div>
    <a href="{{ route('fornecedores.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

@if($errors->any())
    <div class="alert alert-danger mb-16"><ul class="mb-0" style="padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ $fornecedor ? route('fornecedores.update', $fornecedor) : route('fornecedores.store') }}">
@csrf @if($fornecedor) @method('PUT') @endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    {{-- ── Dados Principais ── --}}
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-building"></i> Dados da Empresa</div></div>
        <div class="flex flex-col gap-14">

            <div class="form-group">
                <label class="form-label">Razão Social <span class="text-danger">*</span></label>
                <input type="text" name="razao_social" value="{{ old('razao_social', $fornecedor?->razao_social) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nome Fantasia</label>
                <input type="text" name="nome_fantasia" value="{{ old('nome_fantasia', $fornecedor?->nome_fantasia) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">CNPJ</label>
                <input type="text" name="cnpj" id="cnpj" value="{{ old('cnpj', $fornecedor?->cnpj) }}" class="form-control" placeholder="00.000.000/0000-00" maxlength="18" oninput="mascaraCnpj(this)">
            </div>
            <div class="form-group">
                <label class="form-label">Inscrição Estadual</label>
                <input type="text" name="inscricao_estadual" value="{{ old('inscricao_estadual', $fornecedor?->inscricao_estadual) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Telefone</label>
                <input type="text" name="telefone" id="telefone" value="{{ old('telefone', $fornecedor?->telefone) }}" class="form-control" placeholder="(00) 00000-0000" maxlength="15" oninput="mascaraTelefone(this)">
            </div>
            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $fornecedor?->email) }}" class="form-control">
            </div>
        </div>
    </div>

    {{-- ── Endereço ── --}}
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-map-marker-alt"></i> Endereço</div></div>
        <div class="flex flex-col gap-14">

            <div class="form-group">
                <label class="form-label">CEP</label>
                <div class="flex gap-8">
                    <input type="text" name="cep" id="cep" value="{{ old('cep', $fornecedor?->cep) }}" class="form-control" placeholder="00000-000" maxlength="9" oninput="mascaraCep(this)" style="max-width:130px">
                    <button type="button" class="btn btn-secondary" onclick="buscarCep()" id="btnCep">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <span id="cepStatus" class="text-12 text-muted" style="align-self:center"></span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Logradouro</label>
                <input type="text" name="logradouro" id="logradouro" value="{{ old('logradouro', $fornecedor?->logradouro) }}" class="form-control">
            </div>

            <div style="display:grid;grid-template-columns:80px 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Número</label>
                    <input type="text" name="numero" id="numero" value="{{ old('numero', $fornecedor?->numero) }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Complemento</label>
                    <input type="text" name="complemento" id="complemento" value="{{ old('complemento', $fornecedor?->complemento) }}" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Bairro</label>
                <input type="text" name="bairro" id="bairro" value="{{ old('bairro', $fornecedor?->bairro) }}" class="form-control">
            </div>

            <div style="display:grid;grid-template-columns:1fr 60px;gap:12px">
                <div class="form-group">
                    <label class="form-label">Município</label>
                    <input type="text" name="municipio" id="municipio" value="{{ old('municipio', $fornecedor?->municipio) }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">UF</label>
                    <input type="text" name="uf" id="uf" value="{{ old('uf', $fornecedor?->uf) }}" class="form-control" maxlength="2" oninput="this.value=this.value.toUpperCase()">
                </div>
            </div>

        </div>
    </div>
</div>

<div class="flex justify-end gap-12 mt-20">
    <a href="{{ route('fornecedores.index') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ $fornecedor ? 'Salvar Alterações' : 'Cadastrar Fornecedor' }}</button>
</div>
</form>

<script>
// ── Busca CEP ────────────────────────────────────────────────────────────────
async function buscarCep() {
    const cep = document.getElementById('cep').value.replace(/\D/g,'');
    const status = document.getElementById('cepStatus');
    const btn = document.getElementById('btnCep');

    if (cep.length !== 8) { status.textContent = 'CEP inválido'; status.style.color='var(--danger)'; return; }

    btn.disabled = true;
    status.textContent = 'Buscando...';
    status.style.color = 'var(--text-muted)';

    try {
        const res  = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();

        if (data.erro) {
            status.textContent = '✖ CEP não encontrado';
            status.style.color = 'var(--danger)';
        } else {
            document.getElementById('logradouro').value = data.logradouro || '';
            document.getElementById('bairro').value     = data.bairro     || '';
            document.getElementById('municipio').value  = data.localidade || '';
            document.getElementById('uf').value         = data.uf         || '';
            document.getElementById('complemento').value= data.complemento|| '';
            document.getElementById('numero').focus();
            status.textContent = '✔ Endereço preenchido';
            status.style.color = 'var(--success)';
        }
    } catch {
        status.textContent = '✖ Erro ao consultar ViaCEP';
        status.style.color = 'var(--danger)';
    } finally {
        btn.disabled = false;
    }
}

// Busca automática ao completar 8 dígitos do CEP
document.getElementById('cep').addEventListener('input', function() {
    if (this.value.replace(/\D/g,'').length === 8) buscarCep();
});

// ── Máscaras ─────────────────────────────────────────────────────────────────
function mascaraCnpj(i) {
    let v = i.value.replace(/\D/g,'').slice(0,14);
    if (v.length>12) v=v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2}).*/,'$1.$2.$3/$4-$5');
    else if (v.length>8) v=v.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4}).*/,'$1.$2.$3/$4');
    else if (v.length>5) v=v.replace(/^(\d{2})(\d{3})(\d{0,3}).*/,'$1.$2.$3');
    else if (v.length>2) v=v.replace(/^(\d{2})(\d{0,3}).*/,'$1.$2');
    i.value = v;
}
function mascaraCep(i) {
    let v = i.value.replace(/\D/g,'').slice(0,8);
    if (v.length>5) v = v.replace(/^(\d{5})(\d{0,3}).*/,'$1-$2');
    i.value = v;
}
function mascaraTelefone(i) {
    let v = i.value.replace(/\D/g,'').slice(0,11);
    if (v.length>10) v=v.replace(/^(\d{2})(\d{5})(\d{4}).*/,'($1) $2-$3');
    else if (v.length>6) v=v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/,'($1) $2-$3');
    else if (v.length>2) v=v.replace(/^(\d{2})(\d{0,5}).*/,'($1) $2');
    i.value = v;
}
</script>
@endsection
