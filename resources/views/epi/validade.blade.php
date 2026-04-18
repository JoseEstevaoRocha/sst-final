@extends('layouts.app')
@section('title','Controle de Validade — EPI')
@section('content')
<div class="page-header"><div><h1 class="page-title">Controle de Validade — EPI</h1></div><a href="{{ route('epis.index') }}" class="btn btn-secondary">← Voltar</a></div>
<div class="tabs mb-16">
    <button class="tab-btn active" data-tab="vencidos">🔴 Vencidos ({{ $vencidos->total() }})</button>
    <button class="tab-btn" data-tab="avencer">🟡 A Vencer ({{ $aVencer->total() }})</button>
</div>
<div id="tab-vencidos" class="tc active">
    <div class="card p-0"><div class="table-wrap"><table class="table">
    <thead><tr><th>COLABORADOR</th><th>EPI</th><th>PREV. TROCA</th><th>DIAS VENCIDO</th></tr></thead>
    <tbody>
    @forelse($vencidos as $e)
    <tr class="tr-danger"><td><div class="font-bold">{{ $e->colaborador->nome??'—' }}</div></td><td>{{ $e->epi->nome??'—' }}</td><td class="font-mono text-12 text-danger">{{ $e->data_prevista_troca?->format('d/m/Y') }}</td><td><span class="badge badge-danger">{{ today()->diffInDays($e->data_prevista_troca) }}d vencido</span></td></tr>
    @empty
    <tr><td colspan="4"><div class="empty-state py-24"><i class="fas fa-check-circle" style="font-size:32px;color:var(--success)"></i><p>Nenhum EPI vencido 🎉</p></div></td></tr>
    @endforelse
    </tbody></table></div></div>
</div>
<div id="tab-avencer" class="tc">
    <div class="card p-0"><div class="table-wrap"><table class="table">
    <thead><tr><th>COLABORADOR</th><th>EPI</th><th>PREV. TROCA</th><th>DIAS RESTANTES</th></tr></thead>
    <tbody>
    @forelse($aVencer as $e)
    <tr class="tr-warning"><td><div class="font-bold">{{ $e->colaborador->nome??'—' }}</div></td><td>{{ $e->epi->nome??'—' }}</td><td class="font-mono text-12 text-warning">{{ $e->data_prevista_troca?->format('d/m/Y') }}</td><td><span class="badge badge-warning">{{ today()->diffInDays($e->data_prevista_troca) }}d</span></td></tr>
    @empty
    <tr><td colspan="4"><div class="empty-state py-24"><p>Nenhum EPI a vencer em 60 dias</p></div></td></tr>
    @endforelse
    </tbody></table></div></div>
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('.tab-btn').forEach(btn=>{btn.addEventListener('click',()=>{document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));document.querySelectorAll('.tc').forEach(c=>c.classList.remove('active'));btn.classList.add('active');document.getElementById('tab-'+btn.dataset.tab).classList.add('active');});});
</script>
@endpush
