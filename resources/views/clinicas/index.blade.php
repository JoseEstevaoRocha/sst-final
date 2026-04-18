@extends('layouts.app')
@section('title','Clínicas')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Clínicas Parceiras</h1>
        <p class="page-sub">{{ $clinicas->total() }} cadastradas</p>
    </div>
    <a href="{{ route('clinicas.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Nova Clínica</a>
</div>

<div class="card p-0">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>CLÍNICA</th>
                    <th>WHATSAPP</th>
                    <th>TELEFONE</th>
                    <th>CIDADE</th>
                    <th>RESPONSÁVEL</th>
                    <th>AÇÕES</th>
                </tr>
            </thead>
            <tbody>
            @forelse($clinicas as $c)
            <tr>
                <td>
                    <div class="font-bold text-13">{{ $c->nome }}</div>
                    @if($c->email)<div class="text-11 text-muted">{{ $c->email }}</div>@endif
                </td>
                <td>
                    @if($c->whatsapp)
                    @php $num = '55'.preg_replace('/\D/','',$c->whatsapp); @endphp
                    <div style="display:flex;align-items:center;gap:8px">
                        <span class="font-mono text-12">{{ $c->whatsapp }}</span>
                        <a href="https://wa.me/{{ $num }}" target="_blank"
                           title="Abrir WhatsApp"
                           style="width:28px;height:28px;border-radius:50%;background:#25d366;color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;text-decoration:none">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                    @else
                    <span style="color:var(--danger);font-size:12px"><i class="fas fa-exclamation-triangle"></i> Sem WhatsApp</span>
                    @endif
                </td>
                <td class="font-mono text-12">{{ $c->telefone ?? '—' }}</td>
                <td class="text-12">{{ $c->cidade ?? '—' }}{{ $c->estado ? ' — '.$c->estado : '' }}</td>
                <td class="text-12">{{ $c->responsavel ?? '—' }}</td>
                <td>
                    <div class="flex gap-4">
                        <a href="{{ route('clinicas.edit', $c->id) }}" class="btn btn-secondary btn-icon" title="Editar">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        @if($c->whatsapp)
                        <a href="{{ route('whatsapp.index') }}" class="btn btn-ghost btn-icon" title="Agendar via WhatsApp" style="color:#25d366">
                            <i class="fas fa-paper-plane"></i>
                        </a>
                        @endif
                        <form method="POST" action="{{ route('clinicas.destroy', $c->id) }}" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger"
                                data-confirm="Excluir definitivamente a clínica {{ $c->nome }}? Esta ação não pode ser desfeita."
                                title="Excluir">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-hospital"></i></div>
                    <h3>Nenhuma clínica cadastrada</h3>
                    <a href="{{ route('clinicas.create') }}" class="btn btn-primary btn-sm mt-8">
                        <i class="fas fa-plus"></i> Cadastrar primeira clínica
                    </a>
                </div>
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $clinicas->links() }}
@endsection
