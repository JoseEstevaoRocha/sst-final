@extends('layouts.app')
@section('title','Gestão de EPIs')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">Gestão de EPIs</h1><p class="page-sub">Equipamentos de Proteção Individual</p></div>
    <div class="flex gap-8">
        <a href="{{ route('epis.grade') }}"    class="btn btn-secondary"><i class="fas fa-table"></i> Grade de Estoque</a>
        <a href="{{ route('epis.entregas') }}" class="btn btn-secondary"><i class="fas fa-box-open"></i> Entregas</a>
        <a href="{{ route('epis.validade') }}" class="btn btn-secondary"><i class="fas fa-calendar-check"></i> Validade</a>
        <a href="{{ route('epis.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Novo EPI</a>
    </div>
</div>
<div class="kpi-row mb-20" style="grid-template-columns:repeat(5,1fr)">
    @foreach([['Ativos','total_ativos','blue'],['Vencidos','vencidos','red'],['A Vencer (60d)','a_vencer_60','yellow'],['Entregas/Mês','entregas_mes','green'],['Estoque Baixo','estoque_baixo','yellow']] as [$l,$k,$c])
    <div class="kpi kpi-{{ $c }} {{ $k==='vencidos'&&$dash[$k]>0?'kpi-pulse':'' }}">
        <div class="kpi-label">{{ $l }}</div><div class="kpi-val">{{ $dash[$k] }}</div>
    </div>
    @endforeach
</div>
<form method="GET"><div class="filter-bar">
    <div class="filter-search-wrap"><i class="fas fa-search"></i><input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, CA ou fornecedor..." class="filter-input filter-input-icon" style="width:240px"></div>
    <select name="tipo" class="filter-select" onchange="this.form.submit()"><option value="">Todos os tipos</option>@foreach(['Capacete','Luva','Óculos','Protetor Auricular','Calçado','Respirador','Cinto','Colete','Outros'] as $t)<option value="{{ $t }}" {{ request('tipo')===$t?'selected':'' }}>{{ $t }}</option>@endforeach</select>
    <select name="status" class="filter-select" onchange="this.form.submit()"><option value="">Todos</option><option value="Ativo" {{ request('status')==='Ativo'?'selected':'' }}>Ativo</option><option value="Inativo" {{ request('status')==='Inativo'?'selected':'' }}>Inativo</option></select>
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-filter"></i></button>
</div></form>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>EPI</th><th>TIPO</th><th>CA / FORNECEDOR</th><th>VIDA ÚTIL</th><th>ESTOQUE MÍN.</th><th>CUSTO</th><th>STATUS</th><th>AÇÕES</th></tr></thead>
<tbody>
@forelse($epis as $epi)
<tr>
    <td><div class="font-bold text-13">{{ $epi->nome }}</div>@if($epi->descricao)<div class="text-11 text-muted">{{ Str::limit($epi->descricao,50) }}</div>@endif</td>
    <td><span class="badge badge-secondary">{{ $epi->tipo }}</span></td>
    <td>
        @if($epi->numero_ca)
        <div class="font-mono text-11">CA: {{ $epi->numero_ca }}</div>
        @php
            $sit = strtolower($epi->ca_situacao ?? '');
            $badgeCa = match(true) {
                str_contains($sit,'válido')    => 'badge-success',
                str_contains($sit,'vencido')   => 'badge-danger',
                str_contains($sit,'cancelado') => 'badge-danger',
                $epi->ca_situacao !== null     => 'badge-warning',
                default                        => 'badge-secondary',
            };
            $labelCa = $epi->ca_situacao ?? '—';
        @endphp
        <span class="badge {{ $badgeCa }}" style="font-size:9px;margin-top:2px">{{ $labelCa }}</span>
        @else
        <div class="text-11 text-muted">—</div>
        @endif
        <div class="text-11 text-muted">{{ $epi->fornecedor }}</div>
    </td>
    <td class="text-12">{{ $epi->vida_util_dias ? $epi->vida_util_dias.'d' : '—' }}</td>
    <td class="font-bold text-13">{{ $epi->estoque_minimo }}</td>
    <td class="text-12">{{ $epi->custo_unitario ? 'R$ '.number_format($epi->custo_unitario,2,',','.') : '—' }}</td>
    <td><span class="badge {{ $epi->status==='Ativo'?'badge-success':'badge-danger' }}">{{ $epi->status }}</span></td>
    <td><div class="flex gap-4">
        <button onclick="openMovModal({{ $epi->id }},'{{ addslashes($epi->nome) }}')" class="btn btn-ghost btn-icon" title="Estoque"><i class="fas fa-boxes"></i></button>
        <a href="{{ route('epis.edit',$epi->id) }}" class="btn btn-secondary btn-icon"><i class="fas fa-pencil-alt"></i></a>
        <form method="POST" action="{{ route('epis.destroy',$epi->id) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Inativar {{ $epi->nome }}?"><i class="fas fa-ban"></i></button></form>
    </div></td>
</tr>
@empty
<tr><td colspan="8"><div class="empty-state"><div class="empty-icon"><i class="fas fa-hard-hat"></i></div><h3>Nenhum EPI</h3></div></td></tr>
@endforelse
</tbody></table></div></div>

{{-- Modal movimentar estoque --}}
<div class="modal-overlay" id="movModal">
<div class="modal modal-sm">
<div class="modal-header"><div class="modal-title"><i class="fas fa-boxes"></i> <span id="movTitle">Movimentar Estoque</span></div><button class="modal-close" onclick="closeModal('movModal')"><i class="fas fa-times"></i></button></div>
<div class="modal-body">
<form method="POST" id="movForm" action="">@csrf
    <div class="flex flex-col gap-16">
        <div class="form-group"><label class="form-label">Empresa *</label>
            <select name="empresa_id" class="form-select" required><option value="">Selecione</option>@foreach($empresas as $e)<option value="{{ $e->id }}">{{ $e->nome_display }}</option>@endforeach</select>
        </div>
        <div class="form-group"><label class="form-label">Tipo *</label>
            <select name="tipo" class="form-select" required><option value="entrada">Entrada</option><option value="saida">Saída</option><option value="ajuste">Ajuste</option></select>
        </div>
        <div class="form-group"><label class="form-label">Quantidade *</label><input type="number" name="quantidade" class="form-control" min="1" value="1" required></div>
        <div class="form-group"><label class="form-label">Motivo</label><input type="text" name="motivo" class="form-control" placeholder="Motivo da movimentação"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('movModal')">Cancelar</button><button type="submit" class="btn btn-primary">Confirmar</button></div>
</form>
</div></div></div>
@endsection
@push('scripts')
<script>
function openMovModal(id,nome){document.getElementById('movTitle').textContent='Estoque — '+nome;document.getElementById('movForm').action=`/epis/${id}/movimentar`;openModal('movModal');}
</script>
@endpush
