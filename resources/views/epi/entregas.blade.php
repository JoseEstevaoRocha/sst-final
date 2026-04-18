@extends('layouts.app')
@section('title','Entregas de EPI')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">Entregas de EPI</h1><p class="page-sub">{{ $entregas->total() }} registros</p></div>
    <button class="btn btn-primary" onclick="openModal('modalEntrega')"><i class="fas fa-plus"></i> Nova Entrega</button>
</div>
<form method="GET"><div class="filter-bar">
    <select name="empresa_id" class="filter-select" onchange="this.form.submit()" style="width:200px"><option value="">Todas as empresas</option>@foreach($empresas as $e)<option value="{{ $e->id }}" {{ request('empresa_id')==$e->id?'selected':'' }}>{{ $e->nome_display }}</option>@endforeach</select>
    <select name="epi_id" class="filter-select" onchange="this.form.submit()" style="width:200px"><option value="">Todos os EPIs</option>@foreach($epis_list as $e)<option value="{{ $e->id }}" {{ request('epi_id')==$e->id?'selected':'' }}>{{ $e->nome }}</option>@endforeach</select>
    @if(request()->hasAny(['empresa_id','epi_id']))<a href="{{ route('epis.entregas') }}" class="btn btn-ghost btn-sm">✕</a>@endif
</div></form>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>COLABORADOR</th><th>EPI</th><th>QTD</th><th>TAMANHO</th><th>DATA ENTREGA</th><th>PREV. TROCA</th><th>RESPONSÁVEL</th><th>STATUS</th></tr></thead>
<tbody>
@forelse($entregas as $e)
@php $hoje=today();$troca=$e->data_prevista_troca;$s=$troca?($troca->isPast()?'Vencido':($troca->lte($hoje->addDays(30))?'A Vencer':'Ativo')):'Ativo'; @endphp
<tr class="{{ $s==='Vencido'?'tr-danger':($s==='A Vencer'?'tr-warning':'') }}">
    <td><div class="font-bold text-13">{{ $e->colaborador->nome??'—' }}</div></td>
    <td><div class="font-bold text-13">{{ $e->epi->nome??'—' }}</div><div class="text-11 text-muted">{{ $e->epi->tipo??'' }}</div></td>
    <td class="font-bold text-16" style="color:var(--brand)">{{ $e->quantidade }}</td>
    <td class="text-12">{{ $e->tamanho??'—' }}</td>
    <td class="font-mono text-12">{{ $e->data_entrega->format('d/m/Y') }}</td>
    <td class="font-mono text-12 {{ $s==='Vencido'?'text-danger':($s==='A Vencer'?'text-warning':'') }}">{{ $troca?$troca->format('d/m/Y'):'—' }}</td>
    <td class="text-12">{{ $e->responsavel??'—' }}</td>
    <td><span class="badge {{ ['Ativo'=>'badge-success','A Vencer'=>'badge-warning','Vencido'=>'badge-danger'][$s] }}">{{ $s }}</span></td>
</tr>
@empty
<tr><td colspan="7"><div class="empty-state"><div class="empty-icon"><i class="fas fa-box-open"></i></div><h3>Nenhuma entrega</h3></div></td></tr>
@endforelse
</tbody></table></div></div>

{{-- Modal --}}
<div class="modal-overlay" id="modalEntrega"><div class="modal modal-lg">
<div class="modal-header"><div class="modal-title"><i class="fas fa-box-open"></i> Nova Entrega de EPI</div><button class="modal-close" onclick="closeModal('modalEntrega')"><i class="fas fa-times"></i></button></div>
<div class="modal-body">
<form method="POST" action="{{ route('epis.entregas.store') }}">@csrf
<div class="form-grid">
    <div class="form-group"><label class="form-label">Empresa *</label><select name="empresa_id" id="empresa_id" class="form-select" required><option value="">Selecione</option>@foreach($empresas as $e)<option value="{{ $e->id }}">{{ $e->nome_display }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Colaborador *</label><select name="colaborador_id" id="colabSelect" class="form-select" required><option value="">Selecione a empresa</option></select></div>
    <div class="form-group"><label class="form-label">EPI *</label><select name="epi_id" id="epiSelect" class="form-select" required onchange="carregarTamanhosEpi()"><option value="">Selecione</option>@foreach($epis_list as $e)<option value="{{ $e->id }}">{{ $e->nome }} ({{ $e->tipo }})</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Quantidade</label><input type="number" name="quantidade" class="form-control" value="1" min="1" required></div>
    <div class="form-group" id="tamanhoGroup" style="display:none">
        <label class="form-label">Tamanho *</label>
        <select name="tamanho_id" id="tamanhoSelect" class="form-select">
            <option value="">Selecione o tamanho</option>
        </select>
    </div>
    <div class="form-group"><label class="form-label">Data de Entrega *</label><input type="date" name="data_entrega" class="form-control" value="{{ date('Y-m-d') }}" required></div>
    <div class="form-group"><label class="form-label">Prev. Troca (auto)</label><input type="date" name="data_prevista_troca" class="form-control"></div>
    <div class="form-group"><label class="form-label">Responsável</label><input type="text" name="responsavel" class="form-control" value="{{ auth()->user()->name }}"></div>
    <div class="form-group"><label class="form-label">Observações</label><input type="text" name="observacoes" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalEntrega')">Cancelar</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar</button></div>
</form>
</div></div></div>
@endsection
@push('scripts')
<script>
async function carregarTamanhosEpi() {
    const epiId = document.getElementById('epiSelect').value;
    const group = document.getElementById('tamanhoGroup');
    const sel   = document.getElementById('tamanhoSelect');
    if (!epiId) { group.style.display='none'; return; }
    try {
        const r    = await fetch(`/api/epi-tamanhos?epi_id=${epiId}`, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
        const data = await r.json();
        if (data.tem_tamanho && data.tamanhos.length) {
            sel.innerHTML = '<option value="">Selecione o tamanho</option>';
            data.tamanhos.forEach(t => {
                const o = document.createElement('option');
                o.value = t.id;
                o.textContent = t.codigo + (t.descricao ? ' — '+t.descricao : '');
                sel.appendChild(o);
            });
            group.style.display = '';
            sel.required = true;
        } else {
            group.style.display = 'none';
            sel.required = false;
            sel.value = '';
        }
    } catch(e) {
        group.style.display = 'none';
    }
}
document.getElementById('empresa_id')?.addEventListener('change',async function(){
    const r=await fetch(`/api/colaboradores?empresa_id=${this.value}`,{headers:{'X-Requested-With':'XMLHttpRequest'}});
    const data=await r.json();const sel=document.getElementById('colabSelect');sel.innerHTML='<option value="">Selecione</option>';
    data.forEach(c=>{const o=document.createElement('option');o.value=c.id;o.textContent=c.nome;sel.appendChild(o);});
});
</script>
@endpush
