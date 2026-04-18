@extends('layouts.app')
@section('title','Manutenções — '.$maquina->nome)
@php
$tipos = [
    'preventiva'            => ['label'=>'Preventiva',             'badge'=>'badge-info'],
    'corretiva'             => ['label'=>'Corretiva',              'badge'=>'badge-danger'],
    'preditiva'             => ['label'=>'Preditiva',              'badge'=>'badge-warning'],
    'inspecao'              => ['label'=>'Inspeção NR12',          'badge'=>'badge-secondary'],
    'prensa_preventiva'     => ['label'=>'Prensa — Preventiva',    'badge'=>'badge-info'],
    'prensa_corretiva'      => ['label'=>'Prensa — Corretiva',     'badge'=>'badge-danger'],
    'ferramenta_preventiva' => ['label'=>'Ferramenta — Preventiva','badge'=>'badge-info'],
    'ferramenta_corretiva'  => ['label'=>'Ferramenta — Corretiva', 'badge'=>'badge-danger'],
];
@endphp
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Manutenções — {{ $maquina->nome }}</h1>
        <p class="page-sub">
            {{ $maquina->marca }}{{ $maquina->modelo ? ' / '.$maquina->modelo : '' }}
            @if($maquina->numero_serie) &nbsp;·&nbsp; S/N: {{ $maquina->numero_serie }}@endif
            &nbsp;·&nbsp; Setor: {{ $maquina->setor?->nome ?? '—' }}
        </p>
    </div>
    <a href="{{ route('maquinas.index') }}" class="btn btn-secondary">← Voltar</a>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

{{-- HISTÓRICO --}}
<div class="card p-0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:600;font-size:14px">
        <i class="fas fa-history" style="color:var(--primary)"></i> Histórico de Manutenções
    </div>
    <div class="table-wrap"><table class="table">
    <thead><tr><th>DATA</th><th>TIPO</th><th>DURAÇÃO</th><th>RESPONSÁVEL</th><th>DESCRIÇÃO</th><th></th></tr></thead>
    <tbody>
    @forelse($manutencoes as $man)
    <tr>
        <td class="font-mono text-12">{{ $man->data_manutencao->format('d/m/Y') }}
            @if($man->hora_inicio)<div class="text-11 text-muted">{{ substr($man->hora_inicio,0,5) }} → {{ substr($man->hora_fim??'',0,5) }}</div>@endif
        </td>
        <td>
            <span class="badge {{ $tipos[$man->tipo]['badge']??'badge-secondary' }}">
                {{ $tipos[$man->tipo]['label']??ucfirst($man->tipo) }}
            </span>
        </td>
        <td class="text-12 font-mono">
            @if($man->duracao_minutos !== null)
                @if($man->duracao_minutos >= 60) {{ intdiv($man->duracao_minutos,60) }}h {{ $man->duracao_minutos % 60 }}min
                @else {{ $man->duracao_minutos }} min @endif
            @else — @endif
        </td>
        <td class="text-12">{{ $man->nomes_responsaveis }}</td>
        <td class="text-12" style="max-width:200px;white-space:normal">{{ $man->descricao ?? '—' }}</td>
        <td>
            <form method="POST" action="{{ route('manutencoes.destroy',$man->id) }}" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir registro?"><i class="fas fa-trash-alt"></i></button>
            </form>
        </td>
    </tr>
    @empty
    <tr><td colspan="6">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-wrench"></i></div>
            <h3>Nenhuma manutenção registrada</h3>
            <p class="text-muted">Use o formulário ao lado para registrar.</p>
        </div>
    </td></tr>
    @endforelse
    </tbody></table></div>
    @if($manutencoes->hasPages())
    <div style="padding:12px 20px">{{ $manutencoes->links() }}</div>
    @endif
</div>

{{-- FORMULÁRIO --}}
<div class="card">
    <div style="font-weight:600;font-size:14px;margin-bottom:16px">
        <i class="fas fa-plus-circle" style="color:var(--primary)"></i> Registrar Manutenção
    </div>
    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:12px">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('maquinas.manutencoes.store',$maquina->id) }}">
        @csrf
        <div class="form-group">
            <label class="form-label">Tipo *</label>
            <select name="tipo" class="form-select" required>
                <optgroup label="Geral">
                    <option value="preventiva" {{ old('tipo')==='preventiva'?'selected':'' }}>Preventiva</option>
                    <option value="corretiva" {{ old('tipo')==='corretiva'?'selected':'' }}>Corretiva</option>
                    <option value="preditiva" {{ old('tipo')==='preditiva'?'selected':'' }}>Preditiva</option>
                    <option value="inspecao" {{ old('tipo')==='inspecao'?'selected':'' }}>Inspeção NR12</option>
                </optgroup>
                <optgroup label="Prensa">
                    <option value="prensa_preventiva" {{ old('tipo')==='prensa_preventiva'?'selected':'' }}>Prensa — Preventiva</option>
                    <option value="prensa_corretiva" {{ old('tipo')==='prensa_corretiva'?'selected':'' }}>Prensa — Corretiva</option>
                </optgroup>
                <optgroup label="Ferramenta">
                    <option value="ferramenta_preventiva" {{ old('tipo')==='ferramenta_preventiva'?'selected':'' }}>Ferramenta — Preventiva</option>
                    <option value="ferramenta_corretiva" {{ old('tipo')==='ferramenta_corretiva'?'selected':'' }}>Ferramenta — Corretiva</option>
                </optgroup>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Data da Manutenção *</label>
            <input type="date" name="data_manutencao" value="{{ old('data_manutencao',date('Y-m-d')) }}" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Hora de Início</label>
            <input type="time" name="hora_inicio" id="horaInicioMaquina" value="{{ old('hora_inicio') }}" class="form-control" oninput="calcDuracao('maquina')">
        </div>
        <div class="form-group">
            <label class="form-label">Hora de Término</label>
            <input type="time" name="hora_fim" id="horaFimMaquina" value="{{ old('hora_fim') }}" class="form-control" oninput="calcDuracao('maquina')">
        </div>
        <div class="form-group">
            <label class="form-label">Duração calculada</label>
            <div id="duracaoMaquina" class="form-control" style="background:var(--bg-secondary);color:var(--text-muted);cursor:default">—</div>
        </div>
        <div class="form-group">
            <label class="form-label">Responsáveis</label>
            <div id="mecanicosSidebarLista" style="display:flex;flex-wrap:wrap;gap:5px;min-height:36px;padding:6px;background:var(--bg-sec);border:1px solid var(--border);border-radius:var(--r-sm);margin-bottom:6px">
                <span style="color:var(--text-muted);font-size:11px;align-self:center">Carregando...</span>
            </div>
            <input type="text" name="responsavel_externo" value="{{ old('responsavel_externo') }}" class="form-control" placeholder="Externo / empresa terceirizada (opcional)" style="font-size:12px">
        </div>
        <div class="form-group">
            <label class="form-label">Descrição / Serviços Realizados</label>
            <textarea name="descricao" class="form-control" rows="3" placeholder="Descreva os serviços realizados...">{{ old('descricao') }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Registrar</button>
    </form>

    @if($maquina->ultima_manutencao)
    <div style="margin-top:16px;padding:12px;background:var(--bg-secondary);border-radius:var(--r-sm);font-size:12px">
        <div class="text-muted" style="margin-bottom:4px;font-weight:600">ÚLTIMA MANUTENÇÃO</div>
        <div><strong>{{ $maquina->ultima_manutencao->format('d/m/Y') }}</strong></div>
    </div>
    @endif
</div>

</div>
@endsection
@push('scripts')
<script>
const empresaIdSidebar = {{ $maquina->empresa_id ?? 'null' }};

async function loadMecanicosSidebar() {
    const lista = document.getElementById('mecanicosSidebarLista');
    lista.innerHTML = '<span style="color:var(--text-muted);font-size:11px">Carregando...</span>';
    try {
        const url = `/api/mecanicos${empresaIdSidebar ? '?empresa_id='+empresaIdSidebar : ''}`;
        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        const data = await r.json();
        if (data.length === 0) {
            lista.innerHTML = '<span style="color:var(--text-muted);font-size:11px">Nenhum mecânico cadastrado. <a href="{{ route("mecanicos.index") }}" target="_blank">Cadastrar</a></span>';
            return;
        }
        lista.innerHTML = '';
        data.forEach(m => {
            const label = document.createElement('label');
            label.style.cssText = 'display:flex;align-items:center;gap:5px;padding:4px 9px;background:var(--bg-card);border:1px solid var(--border);border-radius:20px;cursor:pointer;font-size:12px;user-select:none';
            label.innerHTML = `<input type="checkbox" name="mecanicos[]" value="${m.id}" style="cursor:pointer"> ${m.nome}`;
            label.querySelector('input').addEventListener('change', function() {
                label.style.background = this.checked ? 'var(--brand-l)' : 'var(--bg-card)';
                label.style.borderColor = this.checked ? 'var(--brand)' : 'var(--border)';
                label.style.color = this.checked ? 'var(--brand)' : '';
                label.style.fontWeight = this.checked ? '600' : '';
            });
            lista.appendChild(label);
        });
    } catch(e) { lista.innerHTML = '<span style="color:var(--danger);font-size:11px">Erro ao carregar</span>'; }
}

document.addEventListener('DOMContentLoaded', loadMecanicosSidebar);

function calcDuracao(prefix) {
    const p = prefix.charAt(0).toUpperCase() + prefix.slice(1);
    const inicio = document.getElementById('horaInicio'+p)?.value;
    const fim    = document.getElementById('horaFim'+p)?.value;
    const el     = document.getElementById('duracao'+p);
    if (!el) return;
    if (!inicio || !fim) { el.textContent = '—'; return; }
    const [ih,im] = inicio.split(':').map(Number);
    let   [fh,fm] = fim.split(':').map(Number);
    let total = (fh*60+fm) - (ih*60+im);
    if (total < 0) total += 1440;
    const h = Math.floor(total/60), m = total%60;
    el.textContent = h > 0 ? `${h}h ${m}min (${total} min)` : `${total} min`;
}
</script>
@endpush
