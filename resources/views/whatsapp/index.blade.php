@extends('layouts.app')
@section('title','WhatsApp API')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">WhatsApp API</h1><p class="page-sub">Agendamentos via WhatsApp</p></div>
    <div class="flex gap-8">
        <a href="{{ route('whatsapp.config') }}" class="btn btn-secondary"><i class="fas fa-cog"></i> Configuração</a>
        <button class="btn btn-primary" onclick="openModal('modalWpp')"><i class="fas fa-plus"></i> Nova Solicitação</button>
    </div>
</div>
<div class="kpi-row mb-20" style="grid-template-columns:repeat(5,1fr)">
    @foreach([['Total','total','blue'],['Pendentes','pendentes','yellow'],['Enviados','enviados','cyan'],['Agendados','agendados','green'],['Concluídos','concluidos','green']] as [$l,$k,$c])
    <div class="kpi kpi-{{ $c }}"><div class="kpi-label">{{ $l }}</div><div class="kpi-val">{{ $stats[$k]??0 }}</div></div>
    @endforeach
</div>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>COLABORADOR</th><th>TIPO EXAME</th><th>CLÍNICA</th><th>ASO</th><th>STATUS</th><th>ENVIADO</th><th>AGENDADO</th><th>AÇÕES</th></tr></thead>
<tbody>
@forelse($mensagens as $m)
<tr>
    <td><div class="font-bold text-13">{{ $m->colaborador->nome??'—' }}</div><div class="text-11 text-muted">{{ $m->colaborador->empresa->nome_display??'' }}</div></td>
    <td><span class="badge badge-secondary">{{ $m->tipo_exame??'—' }}</span></td>
    <td class="text-12">{{ $m->clinica?->nome??'—' }}</td>
    <td>{{ $m->aso_id ? '🔗 ASO #'.$m->aso_id : '—' }}</td>
    <td><span class="badge {{ ['pendente'=>'badge-secondary','enviado'=>'badge-info','agendado'=>'badge-success','concluido'=>'badge-success','cancelado'=>'badge-danger'][$m->status]??'badge-secondary' }}">{{ ucfirst($m->status) }}</span></td>
    <td class="font-mono text-11">{{ $m->data_envio?->format('d/m/Y H:i')??'—' }}</td>
    <td class="font-mono text-11">{{ $m->data_agendada?->format('d/m/Y')??'—' }}</td>
    <td><div class="flex gap-4">
        @if(in_array($m->status,['pendente','enviado']))<a href="{{ route('whatsapp.url',$m->id) }}" target="_blank" onclick="markSent({{ $m->id }})" class="btn btn-success btn-sm" style="font-size:11px"><i class="fab fa-whatsapp"></i></a>@endif
        @if($m->status==='agendado')<form method="POST" action="{{ route('whatsapp.concluir',$m->id) }}" style="display:inline">@csrf<button type="submit" class="btn btn-success btn-sm" style="font-size:11px" data-confirm="Confirmar conclusão?">✅</button></form>@endif
    </div></td>
</tr>
@empty
<tr><td colspan="8"><div class="empty-state"><div class="empty-icon"><i class="fab fa-whatsapp"></i></div><h3>Nenhuma mensagem</h3></div></td></tr>
@endforelse
</tbody></table></div></div>

<div class="modal-overlay" id="modalWpp"><div class="modal modal-lg">
<div class="modal-header"><div class="modal-title"><i class="fab fa-whatsapp"></i> Nova Solicitação de Agendamento</div><button class="modal-close" onclick="closeModal('modalWpp')"><i class="fas fa-times"></i></button></div>
<div class="modal-body"><form method="POST" action="{{ route('whatsapp.preparar') }}">@csrf
<div class="form-grid">
    <div class="form-group"><label class="form-label">Filtrar Empresa</label><select id="wppEmp" class="form-select"><option value="">Todas</option>@foreach($empresas as $e)<option value="{{ $e->id }}">{{ $e->nome_display }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Buscar Colaborador</label><input type="text" id="wppSearch" class="form-control" placeholder="Nome ou CPF..."></div>
    <div id="wppResults" class="form-full" style="display:none"><label class="form-label">Resultados:</label><div id="wppResultList" class="flex flex-col gap-6 max-h-200 overflow-y-auto"></div></div>
    <input type="hidden" name="colaborador_id" id="wppColabId" required>
    <div id="wppSelected" class="form-full" style="display:none;padding:10px;background:rgba(22,163,74,.1);border:1px solid var(--success);border-radius:var(--r-sm);color:var(--success);font-weight:600"></div>
    <div class="form-group"><label class="form-label">Tipo de Exame *</label><select name="tipo_exame" class="form-select" required>@foreach(['Admissional','Periódico','Demissional','Retorno ao Trabalho','Mudança de Função'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Clínica *</label><select name="clinica_id" class="form-select" required><option value="">Selecione</option>@foreach($clinicas as $cl)<option value="{{ $cl->id }}">{{ $cl->nome }} — {{ $cl->whatsapp }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Data Agendada</label><input type="date" name="data_agendada" class="form-control" value="{{ date('Y-m-d') }}"></div>
    <div class="form-group"><label class="form-label">Horário</label><input type="text" name="horario_agendado" class="form-control" placeholder="Ex: 08H00" value="08H00"></div>
</div>
<div style="padding:10px;background:var(--bg-alt);border-radius:var(--r-sm);font-size:12px;color:var(--text-3);margin-top:10px"><i class="fas fa-info-circle"></i> ASO será criado automaticamente. A mensagem usará o modelo configurado em <a href="{{ route('whatsapp.config') }}" style="color:var(--brand)">Configuração</a>.</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalWpp')">Cancelar</button><button type="submit" class="btn btn-primary"><i class="fab fa-whatsapp"></i> Gerar solicitação</button></div>
</form></div></div></div>
@endsection
@push('scripts')
<script>
let swt;
document.getElementById('wppSearch')?.addEventListener('input',function(){
    clearTimeout(swt);const q=this.value.trim();const eid=document.getElementById('wppEmp').value;
    if(!q&&!eid){document.getElementById('wppResults').style.display='none';return;}
    swt=setTimeout(async()=>{
        const r=await fetch(`/api/search?q=${encodeURIComponent(q)}&empresa_id=${eid}`,{headers:{'X-Requested-With':'XMLHttpRequest'}});
        const d=await r.json();const list=document.getElementById('wppResultList');list.innerHTML='';
        d.results?.forEach(c=>{const div=document.createElement('div');div.style.cssText='padding:10px;background:var(--bg-secondary);border-radius:6px;cursor:pointer;border:1px solid transparent';div.innerHTML=`<strong>${c.nome}</strong><br><small style="color:var(--text-3)">${c.meta}</small>`;div.onclick=()=>{document.getElementById('wppColabId').value=c.id;document.getElementById('wppSelected').style.display='block';document.getElementById('wppSelected').textContent='✅ '+c.nome;document.getElementById('wppResults').style.display='none';};list.appendChild(div);});
        document.getElementById('wppResults').style.display=d.results?.length?'block':'none';
    },300);
});
document.getElementById('wppEmp')?.addEventListener('change',function(){document.getElementById('wppSearch').dispatchEvent(new Event('input'));});
function markSent(id){fetch(`/whatsapp/${id}/enviar`,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'X-Requested-With':'XMLHttpRequest'}});}
</script>
@endpush
