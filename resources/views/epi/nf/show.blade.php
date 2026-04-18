@extends('layouts.app')
@section('title','NF ' . $nfEntrada->numero . '/' . $nfEntrada->serie)
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-file-invoice"></i>
            NF {{ $nfEntrada->numero }}/{{ $nfEntrada->serie }}
            @if($nfEntrada->status === 'cancelada')
                <span class="badge badge-danger ml-8">Cancelada</span>
            @else
                <span class="badge badge-success ml-8">Ativa</span>
            @endif
        </h1>
        <div class="text-muted text-13">Cadastrada em {{ $nfEntrada->created_at->format('d/m/Y H:i') }} por {{ $nfEntrada->usuario?->name ?? '—' }}</div>
    </div>
    <div class="flex gap-8">
        <a href="{{ route('nf-entradas.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        @if($nfEntrada->status === 'ativa')
            <form method="POST" action="{{ route('nf-entradas.cancelar', $nfEntrada) }}"
                  onsubmit="return confirm('Cancelar esta nota?\nO estoque dos itens será REVERTIDO.')">
                @csrf
                <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Cancelar NF</button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-16">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

    {{-- Dados da Nota --}}
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-file-alt"></i> Dados da Nota</div></div>
        <table class="table" style="font-size:13px">
            <tr><td class="text-muted" style="width:45%">Número/Série</td><td><strong>{{ $nfEntrada->numero }}/{{ $nfEntrada->serie }}</strong></td></tr>
            @if($nfEntrada->chave_acesso)
            <tr><td class="text-muted">Chave de Acesso</td><td style="font-family:monospace;font-size:11px;word-break:break-all">{{ $nfEntrada->chave_acesso }}</td></tr>
            @endif
            <tr><td class="text-muted">Data de Emissão</td><td>{{ $nfEntrada->data_emissao->format('d/m/Y') }}</td></tr>
            <tr><td class="text-muted">Data de Entrada</td><td>{{ $nfEntrada->data_entrada->format('d/m/Y') }}</td></tr>
            @if($nfEntrada->natureza_operacao)
            <tr><td class="text-muted">Natureza Operação</td><td>{{ $nfEntrada->natureza_operacao }}</td></tr>
            @endif
            <tr><td class="text-muted">Valor Produtos</td><td>R$ {{ number_format($nfEntrada->valor_produtos, 2, ',', '.') }}</td></tr>
            @if($nfEntrada->valor_frete > 0)
            <tr><td class="text-muted">Frete</td><td>R$ {{ number_format($nfEntrada->valor_frete, 2, ',', '.') }}</td></tr>
            @endif
            @if($nfEntrada->valor_desconto > 0)
            <tr><td class="text-muted">Desconto</td><td>R$ {{ number_format($nfEntrada->valor_desconto, 2, ',', '.') }}</td></tr>
            @endif
            <tr><td class="text-muted">Valor Total</td><td><strong style="font-size:16px;color:var(--success)">R$ {{ number_format($nfEntrada->valor_total, 2, ',', '.') }}</strong></td></tr>
        </table>
    </div>

    {{-- Fornecedor --}}
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-truck"></i> Fornecedor</div></div>
        @if($nfEntrada->fornecedor)
        @php $f = $nfEntrada->fornecedor @endphp
        <table class="table" style="font-size:13px">
            <tr><td class="text-muted" style="width:40%">Razão Social</td><td><strong>{{ $f->razao_social }}</strong></td></tr>
            @if($f->nome_fantasia)<tr><td class="text-muted">Nome Fantasia</td><td>{{ $f->nome_fantasia }}</td></tr>@endif
            @if($f->cnpj)<tr><td class="text-muted">CNPJ</td><td>{{ $f->cnpj }}</td></tr>@endif
            @if($f->inscricao_estadual)<tr><td class="text-muted">IE</td><td>{{ $f->inscricao_estadual }}</td></tr>@endif
            @if($f->logradouro)<tr><td class="text-muted">Endereço</td><td>{{ $f->logradouro }}, {{ $f->numero }}{{ $f->complemento ? ' '.$f->complemento : '' }} — {{ $f->bairro }}</td></tr>@endif
            @if($f->municipio)<tr><td class="text-muted">Município/UF</td><td>{{ $f->municipio }}/{{ $f->uf }}</td></tr>@endif
            @if($f->telefone)<tr><td class="text-muted">Telefone</td><td>{{ $f->telefone }}</td></tr>@endif
            @if($f->email)<tr><td class="text-muted">E-mail</td><td>{{ $f->email }}</td></tr>@endif
        </table>
        @else
            <div class="text-muted text-center py-20">Fornecedor não informado</div>
        @endif
    </div>
</div>

{{-- Itens --}}
<div class="card mb-16">
    <div class="card-header"><div class="card-title"><i class="fas fa-hard-hat"></i> Itens da Nota ({{ $nfEntrada->itens->count() }})</div></div>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>EPI</th>
                <th>Código Forn.</th>
                <th>CA</th>
                <th>Unidade</th>
                <th>Qtd</th>
                <th>Vlr Unit.</th>
                <th>Vlr Total</th>
                <th>Lote</th>
                <th>Val. Produto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nfEntrada->itens as $i => $item)
            <tr>
                <td class="text-muted text-12">{{ $i+1 }}</td>
                <td>
                    <div class="font-500">
                        <a href="{{ route('epis.edit', $item->epi) }}" class="text-primary">{{ $item->epi->nome }}</a>
                    </div>
                    <div class="text-11 text-muted">{{ $item->epi->tipo }}</div>
                    @if($item->tamanho)<span class="badge badge-secondary" style="font-size:10px">{{ $item->tamanho->nome }}</span>@endif
                </td>
                <td class="text-12 text-muted">{{ $item->codigo_fornecedor ?: '—' }}</td>
                <td class="text-12">{{ $item->epi->numero_ca ?: '—' }}</td>
                <td class="text-12">{{ $item->unidade }}</td>
                <td class="font-500">{{ number_format($item->quantidade, 0, ',', '.') }}</td>
                <td>R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                <td class="font-500">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                <td class="text-12 text-muted">{{ $item->lote ?: '—' }}</td>
                <td class="text-12">{{ $item->data_validade ? $item->data_validade->format('d/m/Y') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:var(--bg-secondary)">
                <td colspan="5" class="text-right font-500">Total</td>
                <td class="font-500">{{ $nfEntrada->itens->sum(fn($i)=>(int)$i->quantidade) }}</td>
                <td></td>
                <td class="font-500">R$ {{ number_format($nfEntrada->itens->sum('valor_total'), 2, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Movimentações geradas --}}
@if($nfEntrada->movimentacoes->isNotEmpty())
<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-exchange-alt"></i> Movimentações de Estoque Geradas</div></div>
    <table class="table" style="font-size:12px">
        <thead><tr><th>EPI</th><th>Tipo</th><th>Qtd</th><th>Data</th><th>Usuário</th></tr></thead>
        <tbody>
            @foreach($nfEntrada->movimentacoes as $mov)
            <tr>
                <td>{{ $mov->epi->nome ?? '—' }}</td>
                <td><span class="badge {{ $mov->tipo==='entrada'?'badge-success':'badge-danger' }}">{{ ucfirst($mov->tipo) }}</span></td>
                <td>{{ $mov->quantidade }}</td>
                <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $mov->usuario }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
