<div class="card p-0">
<div class="table-wrap"><table class="table">
<thead><tr><th>COLABORADOR</th><th>EMPRESA</th><th>TIPO</th><th>VENCIMENTO</th><th>SITUAÇÃO</th><th>AÇÕES</th></tr></thead>
<tbody>
@forelse($asos as $a)
@php $dias = $a->dias_restantes; @endphp
<tr class="{{ $dias !== null && $dias < 0 ? 'tr-danger' : '' }}">
    <td><div class="font-bold text-13">{{ $a->colaborador->nome ?? '—' }}</div><div class="text-11 text-muted">{{ $a->colaborador->funcao->nome ?? '' }}</div></td>
    <td class="text-12">{{ $a->empresa->nome_display ?? '—' }}</td>
    <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$a->tipo)) }}</span></td>
    <td class="font-mono text-12">{{ $a->data_vencimento?->format('d/m/Y') ?? '—' }}</td>
    <td><span class="badge {{ $a->situacao==='Vencido'?'badge-danger':($a->situacao==='A Vencer'?'badge-warning':'badge-success') }}">{{ $a->situacao }}</span></td>
    <td><a href="{{ route('asos.edit',$a->id) }}" class="btn btn-secondary btn-sm">Editar</a></td>
</tr>
@empty
<tr><td colspan="6"><div class="empty-state"><div class="empty-icon"><i class="fas fa-clipboard-list"></i></div><h3>Nenhum registro</h3></div></td></tr>
@endforelse
</tbody></table></div>
</div>
