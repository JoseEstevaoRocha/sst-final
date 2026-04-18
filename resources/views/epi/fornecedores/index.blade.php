@extends('layouts.app')
@section('title','Fornecedores')
@section('content')

<div class="page-header">
    <div><h1 class="page-title"><i class="fas fa-truck"></i> Fornecedores</h1></div>
    <a href="{{ route('fornecedores.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Fornecedor</a>
</div>

@foreach(['success','error'] as $t)
    @if(session($t))<div class="alert alert-{{ $t==='error'?'danger':'success' }} mb-16">{{ session($t) }}</div>@endif
@endforeach

<div class="card mb-16">
    <form method="GET" class="flex gap-12 flex-wrap items-end">
        <div class="form-group mb-0">
            <label class="form-label">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Razão social, fantasia ou CNPJ..." style="min-width:260px">
        </div>
        <div class="form-group mb-0">
            <label class="form-label">UF</label>
            <input type="text" name="uf" value="{{ request('uf') }}" class="form-control" maxlength="2" style="width:60px" oninput="this.value=this.value.toUpperCase()">
        </div>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filtrar</button>
        <a href="{{ route('fornecedores.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i></a>
    </form>
</div>

<div class="card">
    @if($fornecedores->isEmpty())
        <div class="empty-state text-center py-48 text-muted">
            <i class="fas fa-truck" style="font-size:48px;opacity:.3;display:block;margin-bottom:12px"></i>
            Nenhum fornecedor cadastrado.
            <br><a href="{{ route('fornecedores.create') }}" class="btn btn-primary mt-16">Cadastrar fornecedor</a>
        </div>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Fornecedor</th>
                    <th>CNPJ</th>
                    <th>Município/UF</th>
                    <th>Telefone</th>
                    <th>Notas</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($fornecedores as $f)
                <tr>
                    <td>
                        <div class="font-500">{{ $f->razao_social }}</div>
                        @if($f->nome_fantasia && $f->nome_fantasia !== $f->razao_social)
                            <div class="text-11 text-muted">{{ $f->nome_fantasia }}</div>
                        @endif
                    </td>
                    <td class="text-13">{{ $f->cnpj ?: '—' }}</td>
                    <td class="text-13">{{ $f->municipio ? $f->municipio.'/'.$f->uf : '—' }}</td>
                    <td class="text-13">{{ $f->telefone ?: '—' }}</td>
                    <td>
                        @if($f->notas_count)
                            <span class="badge badge-info">{{ $f->notas_count }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="flex gap-6">
                        <a href="{{ route('fornecedores.edit', $f) }}" class="btn btn-xs btn-secondary"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('fornecedores.destroy', $f) }}" onsubmit="return confirm('Excluir fornecedor?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-12">{{ $fornecedores->links() }}</div>
    @endif
</div>
@endsection
