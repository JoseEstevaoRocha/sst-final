@extends('layouts.app')
@section('title','Mecânicos Responsáveis')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Mecânicos Responsáveis</h1>
        <p class="page-sub">Cadastre os colaboradores que aparecerão como responsável no registro de manutenções</p>
    </div>
</div>

@if(session('success'))
<div class="flash flash-success mb-16"><i class="fas fa-check-circle"></i> {{ session('success') }}<button onclick="this.parentElement.remove()" class="flash-close">×</button></div>
@endif

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start">

{{-- COLUNA ESQUERDA: Selecionar colaboradores --}}
<div class="card">
    <div style="font-weight:700;font-size:14px;margin-bottom:16px;display:flex;align-items:center;gap:8px">
        <i class="fas fa-user-plus" style="color:var(--brand)"></i>
        Adicionar Mecânico
    </div>

    <form method="GET" style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:16px">
        @if(auth()->user()->hasRole('super-admin'))
        <select name="empresa_id" class="form-select" onchange="this.form.submit()">
            <option value="">Selecione a empresa</option>
            @foreach($empresas as $emp)
            <option value="{{ $emp->id }}" {{ request('empresa_id')==$emp->id?'selected':'' }}>{{ $emp->nome_display }}</option>
            @endforeach
        </select>
        @endif

        <select name="setor_id" class="form-select" {{ !$empresaId?'disabled':'' }} onchange="this.form.submit()">
            <option value="">Todos os setores</option>
            @foreach($setores as $s)
            <option value="{{ $s->id }}" {{ request('setor_id')==$s->id?'selected':'' }}>{{ $s->nome }}</option>
            @endforeach
        </select>

        @if(request('setor_id'))
        <a href="{{ route('mecanicos.index', array_filter(['empresa_id'=>request('empresa_id')])) }}" class="btn btn-ghost btn-sm" style="white-space:nowrap">✕ Limpar setor</a>
        @endif
    </form>

    @if(!$empresaId)
    <div class="empty-state" style="padding:32px 0">
        <div class="empty-icon"><i class="fas fa-building"></i></div>
        <h3>Selecione uma empresa</h3>
        <p class="text-muted">Escolha a empresa para ver os colaboradores disponíveis.</p>
    </div>
    @elseif($colaboradores->isEmpty())
    <div class="empty-state" style="padding:32px 0">
        <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
        <h3>Nenhum colaborador encontrado</h3>
        <p class="text-muted">Não há colaboradores ativos {{ request('setor_id') ? 'neste setor' : 'nesta empresa' }}.</p>
    </div>
    @else
    <div class="table-wrap"><table class="table">
        <thead><tr><th>COLABORADOR</th><th>SETOR</th><th>FUNÇÃO</th><th></th></tr></thead>
        <tbody>
        @foreach($colaboradores as $c)
        @php $jaCadastrado = in_array($c->id, $mecanicoIds); @endphp
        <tr>
            <td>
                <div class="font-bold text-13">{{ $c->nome }}</div>
                @if($c->matricula)<div class="text-11 text-muted">Mat: {{ $c->matricula }}</div>@endif
            </td>
            <td class="text-12">{{ $c->setor?->nome ?? '—' }}</td>
            <td class="text-12">{{ $c->funcao?->nome ?? '—' }}</td>
            <td>
                @if($jaCadastrado)
                    <span class="badge badge-success"><i class="fas fa-check"></i> Cadastrado</span>
                @else
                    <form method="POST" action="{{ route('mecanicos.add', $c->id) }}" style="display:inline">
                        @csrf
                        @if(request('empresa_id'))<input type="hidden" name="empresa_id" value="{{ request('empresa_id') }}">@endif
                        @if(request('setor_id'))<input type="hidden" name="setor_id" value="{{ request('setor_id') }}">@endif
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </form>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
    @endif
</div>

{{-- COLUNA DIREITA: Mecânicos cadastrados --}}
<div class="card">
    <div style="font-weight:700;font-size:14px;margin-bottom:16px;display:flex;align-items:center;gap:8px">
        <i class="fas fa-hard-hat" style="color:var(--brand)"></i>
        Mecânicos Cadastrados
        <span class="badge badge-secondary">{{ $mecanicosCadastrados->count() }}</span>
    </div>

    @if($mecanicosCadastrados->isEmpty())
    <div style="text-align:center;padding:32px 0;color:var(--text-muted);font-size:13px">
        <i class="fas fa-hard-hat" style="font-size:32px;margin-bottom:8px;display:block;opacity:.3"></i>
        Nenhum mecânico cadastrado ainda.
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:8px">
        @foreach($mecanicosCadastrados->sortBy('colaborador.nome') as $m)
        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg-sec);border-radius:var(--r-sm);border:1px solid var(--border)">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--brand-l);color:var(--brand);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">
                {{ strtoupper(substr($m->colaborador->nome ?? '?', 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0">
                <div class="font-bold text-13" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $m->colaborador?->nome ?? '—' }}</div>
                <div class="text-11 text-muted">{{ $m->colaborador?->setor?->nome ?? '—' }}</div>
            </div>
            <form method="POST" action="{{ route('mecanicos.remove', $m->colaborador_id) }}" style="flex-shrink:0">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Remover" data-confirm="Remover {{ $m->colaborador?->nome }} dos mecânicos?">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        </div>
        @endforeach
    </div>
    @endif
</div>

</div>
@endsection
