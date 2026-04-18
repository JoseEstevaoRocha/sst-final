@extends('layouts.app')
@section('title','Notas Fiscais de Entrada')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-file-invoice"></i> Notas Fiscais de Entrada</h1>
        <div class="text-muted text-13">Entradas de EPIs com atualização automática de estoque</div>
    </div>
    <a href="{{ route('nf-entradas.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nova Nota Fiscal
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success mb-16"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-16"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
@endif

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px">
    <div class="card" style="padding:16px">
        <div class="text-11 text-muted text-uppercase">Notas este mês</div>
        <div style="font-size:28px;font-weight:700;color:var(--primary)">
            {{ \App\Models\NfEntrada::where('status','ativa')->whereMonth('data_entrada',now()->month)->count() }}
        </div>
    </div>
    <div class="card" style="padding:16px">
        <div class="text-11 text-muted text-uppercase">Valor total este mês</div>
        <div style="font-size:28px;font-weight:700;color:var(--success)">
            R$ {{ number_format($totalMes, 2, ',', '.') }}
        </div>
    </div>
    <div class="card" style="padding:16px">
        <div class="text-11 text-muted text-uppercase">Total de notas</div>
        <div style="font-size:28px;font-weight:700;color:var(--text)">
            {{ \App\Models\NfEntrada::count() }}
        </div>
    </div>
</div>

{{-- Filtros --}}
<div class="card mb-16">
    <form method="GET" class="flex gap-12 flex-wrap items-end">
        <div class="form-group mb-0">
            <label class="form-label">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Número ou fornecedor..." style="min-width:200px">
        </div>
        <div class="form-group mb-0">
            <label class="form-label">Status</label>
            <select name="status" class="form-control" style="width:130px">
                <option value="">Todos</option>
                <option value="ativa"     {{ request('status')==='ativa'     ? 'selected':'' }}>Ativa</option>
                <option value="cancelada" {{ request('status')==='cancelada' ? 'selected':'' }}>Cancelada</option>
            </select>
        </div>
        <div class="form-group mb-0">
            <label class="form-label">Entrada de</label>
            <input type="date" name="de" value="{{ request('de') }}" class="form-control" style="width:145px">
        </div>
        <div class="form-group mb-0">
            <label class="form-label">Até</label>
            <input type="date" name="ate" value="{{ request('ate') }}" class="form-control" style="width:145px">
        </div>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filtrar</button>
        <a href="{{ route('nf-entradas.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i></a>
    </form>
</div>

<div class="card">
    @if($notas->isEmpty())
        <div class="empty-state text-center py-48 text-muted">
            <i class="fas fa-file-invoice" style="font-size:48px;opacity:.3;display:block;margin-bottom:12px"></i>
            Nenhuma nota fiscal cadastrada.
            <br><a href="{{ route('nf-entradas.create') }}" class="btn btn-primary mt-16">Cadastrar primeira nota</a>
        </div>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Nota / Série</th>
                    <th>Fornecedor</th>
                    <th>Data Entrada</th>
                    <th>Itens</th>
                    <th>Valor Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($notas as $nf)
                <tr>
                    <td>
                        <div class="font-500">{{ $nf->numero }}<span class="text-muted">/{{ $nf->serie }}</span></div>
                        @if($nf->chave_acesso)
                            <div class="text-10 text-muted" style="font-family:monospace">{{ substr($nf->chave_acesso,0,20) }}...</div>
                        @endif
                    </td>
                    <td>
                        <div class="text-13">{{ $nf->fornecedor?->nome_display ?? '—' }}</div>
                        @if($nf->fornecedor?->cnpj)
                            <div class="text-11 text-muted">{{ $nf->fornecedor->cnpj }}</div>
                        @endif
                    </td>
                    <td class="text-13">{{ $nf->data_entrada->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge badge-info">{{ $nf->itens_count }} {{ Str::plural('item', $nf->itens_count) }}</span>
                    </td>
                    <td class="font-500">R$ {{ number_format($nf->valor_total, 2, ',', '.') }}</td>
                    <td>
                        @if($nf->status === 'ativa')
                            <span class="badge badge-success">Ativa</span>
                        @else
                            <span class="badge badge-danger">Cancelada</span>
                        @endif
                    </td>
                    <td class="flex gap-6">
                        <a href="{{ route('nf-entradas.show', $nf) }}" class="btn btn-xs btn-secondary" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($nf->status === 'ativa')
                            <form method="POST" action="{{ route('nf-entradas.cancelar', $nf) }}"
                                  onsubmit="return confirm('Cancelar NF {{ $nf->numero }}/{{ $nf->serie }}?\nIsto irá REVERTER o estoque dos itens.')">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-danger" title="Cancelar nota">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-12">{{ $notas->links() }}</div>
    @endif
</div>
@endsection
