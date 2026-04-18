@extends('layouts.app')
@section('title','Ficha — '.$colaborador->nome)
@section('content')
<div class="page-header">
    <div><h1 class="page-title">Ficha do Funcionário</h1></div>
    <div class="flex gap-8">
        <a href="{{ route('ficha.index') }}" class="btn btn-secondary">← Voltar</a>
        <a href="{{ route('ficha.pdf',$colaborador->id) }}" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> PDF</a>
        <a href="{{ route('colaboradores.edit',$colaborador->id) }}" class="btn btn-primary"><i class="fas fa-pencil-alt"></i> Editar</a>
        @if($colaborador->status === 'Contratado')
        <button class="btn btn-danger" onclick="document.getElementById('modalDemitir').style.display='flex'"><i class="fas fa-user-minus"></i> Demitir</button>
        @endif
    </div>
</div>

{{-- Alerta PPP ─────────────────────────────────────────────────────────── --}}
@if($alertaPpp)
<div style="background:rgba(220,38,38,.06);border:1.5px solid rgba(220,38,38,.3);border-radius:var(--r);padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
    <div style="width:40px;height:40px;border-radius:10px;background:rgba(220,38,38,.12);color:var(--danger);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px">
        <i class="fas fa-file-contract"></i>
    </div>
    <div style="flex:1;min-width:200px">
        <div style="font-size:14px;font-weight:700;color:var(--danger)">PPP Pendente — Perfil Profissiográfico Previdenciário</div>
        <div style="font-size:12px;color:var(--text-2);margin-top:2px">{{ $alertaPpp->descricao }}</div>
        @if($alertaPpp->data_prevista)
        <div style="font-size:11px;color:var(--text-3);margin-top:3px"><i class="fas fa-calendar"></i> Prazo: {{ $alertaPpp->data_prevista->format('d/m/Y') }}</div>
        @endif
    </div>
    <form method="POST" action="{{ route('colaboradores.resolver-ppp', $colaborador) }}">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm" style="white-space:nowrap">
            <i class="fas fa-check-circle"></i> Marcar PPP como concluído
        </button>
    </form>
</div>
@endif

{{-- Header --}}
<div class="card mb-20">
    <div class="flex align-center gap-20 mb-20">
        <div class="avatar-xl">{{ $colaborador->initials }}</div>
        <div>
            <h2 style="font-size:22px;font-weight:800">{{ $colaborador->nome }}</h2>
            <div class="flex flex-wrap gap-12 mt-8 text-13 text-muted">
                <span><i class="fas fa-briefcase"></i> {{ $colaborador->funcao->nome??'—' }}</span>
                <span><i class="fas fa-layer-group"></i> {{ $colaborador->setor->nome??'—' }}</span>
                <span><i class="fas fa-building"></i> {{ $colaborador->empresa->nome_display??'—' }}</span>
            </div>
        </div>
    </div>
    <div class="ficha-grid">
        @foreach([['CPF',$colaborador->cpf],['PIS',$colaborador->pis??'—'],['Matrícula',$colaborador->matricula??'—'],['eSocial',$colaborador->matricula_esocial??'—'],['CBO',$colaborador->cbo??'—'],['Nascimento',$colaborador->data_nascimento?->format('d/m/Y')??'—'],['Idade',$resumo['idadeAnos'].' anos'],['Sexo',$colaborador->sexo==='M'?'Masculino':'Feminino'],['Admissão',$colaborador->data_admissao?->format('d/m/Y')??'—'],['Tempo',$resumo['tempoMeses']<12?$resumo['tempoMeses'].'m':floor($resumo['tempoMeses']/12).'a '.($resumo['tempoMeses']%12).'m'],['Escolaridade',$colaborador->escolaridade??'—'],['Status',$colaborador->status]] as [$k,$v])
        <div class="ficha-dado"><div class="ficha-label">{{ $k }}</div><div class="ficha-val">{{ $v }}</div></div>
        @endforeach
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-row mb-20" style="grid-template-columns:repeat(5,1fr)">
    <div class="kpi kpi-{{ $resumo['asoVencido']?'red':'green' }}"><div class="kpi-label">ASOs</div><div class="kpi-val">{{ $asos->count() }}</div></div>
    <div class="kpi kpi-{{ $resumo['epiVencidos']>0?'red':'blue' }}"><div class="kpi-label">EPIs Ativos</div><div class="kpi-val">{{ $epiEntregas->count() }}</div></div>
    <div class="kpi kpi-red {{ $resumo['epiVencidos']>0?'kpi-pulse':'' }}"><div class="kpi-label">EPIs Vencidos</div><div class="kpi-val">{{ $resumo['epiVencidos'] }}</div></div>
    <div class="kpi kpi-purple"><div class="kpi-label">Uniformes</div><div class="kpi-val">{{ $resumo['totalUniformes'] }}</div></div>
    <div class="kpi kpi-cyan"><div class="kpi-label">WhatsApp</div><div class="kpi-val">{{ $waMsgs->count() }}</div></div>
</div>

{{-- Tabs --}}
<div class="tabs mb-16">
    <button class="tab-btn active" data-tab="epi">🦺 EPI ({{ $epiEntregas->count() }})</button>
    <button class="tab-btn" data-tab="uni">👕 Uniformes ({{ $uniEntregas->count() }})</button>
    <button class="tab-btn" data-tab="aso">📋 ASO ({{ $asos->count() }})</button>
    <button class="tab-btn" data-tab="wpp">💬 WhatsApp ({{ $waMsgs->count() }})</button>
</div>

{{-- EPI --}}
<div id="tab-epi" class="tc active">
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>QTD</th><th>EPI</th><th>CA</th><th>DATA ENTREGA</th><th>PREV. TROCA</th><th>STATUS</th></tr></thead>
<tbody>
@forelse($epiEntregas as $e)
@php $s=$e->data_prevista_troca?($e->data_prevista_troca->isPast()?'Vencido':($e->data_prevista_troca->lte(today()->addDays(30))?'A Vencer':'Ativo')):'Ativo'; @endphp
<tr class="{{ $s==='Vencido'?'tr-danger':($s==='A Vencer'?'tr-warning':'') }}">
    <td class="font-bold text-16" style="color:var(--brand)">{{ $e->quantidade }}</td>
    <td><div class="font-bold text-13">{{ $e->epi->nome??'—' }}</div><div class="text-11 text-muted">{{ $e->epi->tipo??'' }}</div></td>
    <td class="font-mono text-11">{{ $e->epi->numero_ca??'—' }}</td>
    <td class="font-mono text-12">{{ $e->data_entrega->format('d/m/Y') }}</td>
    <td class="font-mono text-12 {{ $s==='Vencido'?'text-danger':($s==='A Vencer'?'text-warning':'') }}">{{ $e->data_prevista_troca?->format('d/m/Y')??'—' }}</td>
    <td><span class="badge {{ ['Ativo'=>'badge-success','A Vencer'=>'badge-warning','Vencido'=>'badge-danger'][$s] }}">{{ $s }}</span></td>
</tr>
@empty
<tr><td colspan="6"><div class="empty-state py-24"><p>Nenhum EPI registrado</p></div></td></tr>
@endforelse
</tbody></table></div></div>
</div>

{{-- Uniforme --}}
<div id="tab-uni" class="tc">
<div class="card mb-16" style="border-left:3px solid var(--brand)">
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--brand)">{{ $colaborador->empresa->razao_social??'' }}</div>
    <div style="font-size:17px;font-weight:800;margin-top:2px">Termo de Responsabilidade — Uniforme</div>
    <div style="font-size:12px;color:var(--text-2);margin-top:8px;line-height:1.7;font-style:italic">Declaro ter recebido da empresa os uniformes abaixo relacionados, comprometendo-me a mantê-los em bom estado de conservação e limpeza.</div>
</div>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>DATA</th><th>QTD</th><th>UNIFORME</th><th>TAMANHO</th><th>MOTIVO</th></tr></thead>
<tbody>
@forelse($uniEntregas as $e)
<tr><td class="font-mono text-12">{{ $e->data_entrega->format('d/m/Y') }}</td><td class="font-bold text-16" style="color:var(--brand)">{{ $e->quantidade }}</td><td class="font-bold text-13">{{ $e->uniforme->nome??'—' }}</td><td><span class="badge badge-info">{{ $e->tamanho->codigo??'—' }}</span></td><td class="text-12">{{ ucfirst($e->motivo??'—') }}</td></tr>
@empty
<tr><td colspan="5"><div class="empty-state py-24"><p>Nenhum uniforme registrado</p></div></td></tr>
@endforelse
</tbody></table></div></div>
</div>

{{-- ASO --}}
<div id="tab-aso" class="tc">
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>TIPO</th><th>DATA EXAME</th><th>VENCIMENTO</th><th>RESULTADO</th><th>CLÍNICA</th></tr></thead>
<tbody>
@forelse($asos as $a)
<tr class="{{ $a->dias_restantes !== null && $a->dias_restantes < 0 ? 'tr-danger' : '' }}">
    <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$a->tipo)) }}</span></td>
    <td class="font-mono text-12">{{ $a->data_exame?->format('d/m/Y')??'—' }}</td>
    <td class="font-mono text-12 {{ $a->dias_restantes !== null && $a->dias_restantes < 0 ? 'text-danger' : '' }}">{{ $a->data_vencimento?->format('d/m/Y')??'—' }}</td>
    <td><span class="badge {{ ['apto'=>'badge-success','inapto'=>'badge-danger','pendente'=>'badge-secondary','apto_restricoes'=>'badge-warning'][$a->resultado]??'badge-secondary' }}">{{ ucfirst(str_replace('_',' ',$a->resultado)) }}</span></td>
    <td class="text-12">{{ $a->clinica_nome??'—' }}</td>
</tr>
@empty
<tr><td colspan="5"><div class="empty-state py-24"><p>Nenhum ASO</p></div></td></tr>
@endforelse
</tbody></table></div></div>
</div>

{{-- WhatsApp --}}
<div id="tab-wpp" class="tc">
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>DATA</th><th>TIPO EXAME</th><th>CLÍNICA</th><th>STATUS</th></tr></thead>
<tbody>
@forelse($waMsgs as $m)
<tr><td class="font-mono text-12">{{ $m->data_envio?->format('d/m/Y H:i')??'—' }}</td><td class="text-12">{{ $m->tipo_exame??'—' }}</td><td class="text-12">{{ $m->clinica?->nome??'—' }}</td><td><span class="badge badge-secondary">{{ $m->status }}</span></td></tr>
@empty
<tr><td colspan="4"><div class="empty-state py-24"><p>Nenhuma mensagem</p></div></td></tr>
@endforelse
</tbody></table></div></div>
</div>
{{-- Modal Demitir ──────────────────────────────────────────────────────── --}}
@if($colaborador->status === 'Contratado')
<div id="modalDemitir" class="modal-overlay" style="display:none">
<div class="modal" style="max-width:480px">
    <div class="modal-header">
        <h3 class="modal-title" style="color:var(--danger)"><i class="fas fa-user-minus"></i> Demitir Colaborador</h3>
        <button class="modal-close" onclick="document.getElementById('modalDemitir').style.display='none'">&times;</button>
    </div>
    <form method="POST" action="{{ route('colaboradores.demitir', $colaborador) }}">
    @csrf
    <div class="modal-body" style="display:flex;flex-direction:column;gap:16px">
        <div style="background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.2);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--danger)">
            <i class="fas fa-exclamation-triangle"></i> Esta ação irá marcar <strong>{{ $colaborador->nome }}</strong> como Demitido.
        </div>
        <div class="form-group">
            <label class="form-label">Data da Demissão *</label>
            <input type="date" name="data_demissao" class="form-control" required value="{{ date('Y-m-d') }}">
        </div>
        <div class="form-group">
            <label class="form-label">Motivo da Demissão</label>
            <textarea name="demissao_motivo" class="form-control" rows="3" maxlength="300" placeholder="Ex: Pedido de demissão, dispensa sem justa causa..."></textarea>
        </div>
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600">
                <input type="checkbox" name="periodo_experiencia" value="1" style="width:16px;height:16px">
                Período de Experiência (até 90 dias)
            </label>
            <div style="font-size:11px;color:var(--text-3);margin-top:4px;margin-left:24px">Marque se o colaborador está sendo demitido dentro do período de experiência.</div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalDemitir').style.display='none'">Cancelar</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-check"></i> Confirmar Demissão</button>
    </div>
    </form>
</div>
</div>
@endif
@endsection
@push('scripts')
<script>document.querySelectorAll('.tab-btn').forEach(b=>{b.addEventListener('click',()=>{document.querySelectorAll('.tab-btn').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.tc').forEach(x=>x.classList.remove('active'));b.classList.add('active');document.getElementById('tab-'+b.dataset.tab).classList.add('active');});});</script>
@endpush
