@extends('layouts.app')
@section('title','Exames — '.$funcao->nome)
@php
$tipos = \App\Models\ExameClinico::TIPOS;
$badgeTipo = ['audiometria'=>'badge-info','laboratorial'=>'badge-warning','imagem'=>'badge-secondary','clinico'=>'badge-success','espirometria'=>'badge-info','ecg'=>'badge-danger','outros'=>'badge-secondary'];
$examesDoSetor = $funcao->setor?->exames ?? collect();
$qtdNaoImportados = $examesDoSetor->filter(fn($e) => !$funcao->exames->pluck('id')->contains($e->id))->count();
@endphp
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Exames — {{ $funcao->nome }}</h1>
        <p class="page-sub">
            {{ $funcao->empresa?->nome_display }} &nbsp;·&nbsp;
            Setor: {{ $funcao->setor?->nome ?? '—' }} &nbsp;·&nbsp;
            {{ $funcao->exames->count() }} exame(s) associado(s)
        </p>
    </div>
    <div class="flex gap-8">
        <a href="{{ route('funcoes.index') }}" class="btn btn-secondary">← Voltar</a>
        @if($qtdNaoImportados > 0)
        <form method="POST" action="{{ route('funcoes.exames.importar',$funcao->id) }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-secondary" title="Importa {{ $qtdNaoImportados }} exame(s) do setor {{ $funcao->setor?->nome }}">
                <i class="fas fa-download"></i> Importar do Setor ({{ $qtdNaoImportados }})
            </button>
        </form>
        @endif
        <button class="btn btn-primary" onclick="openModal('modalAddExame')"><i class="fas fa-plus"></i> Adicionar Exame</button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-16">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

{{-- Lista de exames associados --}}
<div class="card p-0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:600;font-size:14px">
        <i class="fas fa-stethoscope" style="color:var(--primary)"></i> Exames desta Função
    </div>
    <div class="table-wrap"><table class="table">
    <thead><tr><th>EXAME</th><th>TIPO</th><th>PERIODICIDADE</th><th>OBRIGATÓRIO</th><th>ORIGEM</th><th></th></tr></thead>
    <tbody>
    @forelse($funcao->exames as $ex)
    <tr>
        <td>
            <div class="font-bold text-13">{{ $ex->nome }}</div>
            @if($ex->obrigatorio_nr)<div class="text-11 text-muted">{{ $ex->obrigatorio_nr }}</div>@endif
        </td>
        <td><span class="badge {{ $badgeTipo[$ex->tipo]??'badge-secondary' }}">{{ $tipos[$ex->tipo]??ucfirst($ex->tipo) }}</span></td>
        <td class="text-12">
            {{ $ex->pivot->periodicidade_meses ? $ex->pivot->periodicidade_meses.' meses' : 'Conforme ASO' }}
        </td>
        <td>
            <span class="badge {{ $ex->pivot->obrigatorio ? 'badge-success' : 'badge-secondary' }}">
                {{ $ex->pivot->obrigatorio ? 'Sim' : 'Não' }}
            </span>
        </td>
        <td>
            <span class="badge {{ $ex->pivot->origem==='setor' ? 'badge-info' : 'badge-secondary' }}" style="font-size:10px">
                {{ $ex->pivot->origem==='setor' ? 'Setor' : 'Função' }}
            </span>
        </td>
        <td>
            <form method="POST" action="{{ route('funcoes.exames.remove',[$funcao->id,$ex->id]) }}" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Remover exame?"><i class="fas fa-times"></i></button>
            </form>
        </td>
    </tr>
    @empty
    <tr><td colspan="6">
        <div class="empty-state" style="padding:30px">
            <div class="empty-icon"><i class="fas fa-stethoscope"></i></div>
            <h3>Nenhum exame associado</h3>
            @if($examesDoSetor->count() > 0)
            <p class="text-muted">Este setor tem {{ $examesDoSetor->count() }} exame(s). Clique em "Importar do Setor" para trazer automaticamente.</p>
            @else
            <p class="text-muted">Adicione exames individualmente ou configure o setor primeiro.</p>
            @endif
        </div>
    </td></tr>
    @endforelse
    </tbody></table></div>
</div>

{{-- Painel lateral --}}
<div class="flex flex-col gap-16">

    {{-- Info da função --}}
    <div class="card">
        <div style="font-weight:600;font-size:13px;margin-bottom:12px;color:var(--text-muted)">INFORMAÇÕES</div>
        <div class="flex flex-col gap-8">
            <div><span class="text-muted text-12">Função:</span><br><strong>{{ $funcao->nome }}</strong></div>
            <div><span class="text-muted text-12">Setor:</span><br>
                <a href="{{ route('setores.exames',$funcao->setor_id) }}" style="color:var(--primary)">
                    {{ $funcao->setor?->nome ?? '—' }}
                </a>
            </div>
            <div><span class="text-muted text-12">CBO:</span><br><strong>{{ $funcao->cbo ?? '—' }}</strong></div>
            <div><span class="text-muted text-12">Periodicidade ASO:</span><br><strong>{{ $funcao->periodicidade_aso_dias ? $funcao->periodicidade_aso_dias.' dias' : '—' }}</strong></div>
        </div>
    </div>

    {{-- Exames do setor (referência) --}}
    @if($examesDoSetor->count() > 0)
    <div class="card">
        <div style="font-weight:600;font-size:13px;margin-bottom:12px;color:var(--text-muted)">
            EXAMES DO SETOR ({{ $funcao->setor?->nome }})
        </div>
        @foreach($examesDoSetor as $ex)
        @php $jaAssociado = $funcao->exames->pluck('id')->contains($ex->id); @endphp
        <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border)">
            <div>
                <div class="text-12 font-bold">{{ $ex->nome }}</div>
                <div class="text-11 text-muted">{{ $tipos[$ex->tipo]??$ex->tipo }}</div>
            </div>
            @if($jaAssociado)
            <span class="badge badge-success" style="font-size:10px">Herdado</span>
            @else
            <span class="badge badge-secondary" style="font-size:10px">Não importado</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

</div>
</div>

{{-- Modal Adicionar Exame --}}
<div class="modal-overlay" id="modalAddExame">
<div class="modal modal-sm">
<div class="modal-header">
    <div class="modal-title"><i class="fas fa-plus-circle"></i> Adicionar Exame à Função</div>
    <button class="modal-close" onclick="closeModal('modalAddExame')"><i class="fas fa-times"></i></button>
</div>
<div class="modal-body">
<form method="POST" action="{{ route('funcoes.exames.add',$funcao->id) }}">@csrf
<div class="flex flex-col gap-14">
    <div class="form-group">
        <label class="form-label">Exame *</label>
        <select name="exame_id" class="form-select" required>
            <option value="">Selecione</option>
            @foreach($examesDisponiveis->groupBy('tipo') as $tipo=>$lista)
            <optgroup label="{{ $tipos[$tipo]??ucfirst($tipo) }}">
                @foreach($lista as $ex)
                <option value="{{ $ex->id }}" {{ $idsAssociados->contains($ex->id)?'disabled':'' }}>
                    {{ $ex->nome }}{{ $idsAssociados->contains($ex->id)?' (já associado)':'' }}
                </option>
                @endforeach
            </optgroup>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Periodicidade (meses)</label>
        <input type="number" name="periodicidade_meses" class="form-control" placeholder="Deixe em branco = conforme ASO" min="1" max="60">
    </div>
    <div class="form-group">
        <label class="form-label">Obrigatório</label>
        <select name="obrigatorio" class="form-select">
            <option value="1">Sim</option>
            <option value="0">Não</option>
        </select>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-ghost" onclick="closeModal('modalAddExame')">Cancelar</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> Adicionar</button>
</div>
</form>
</div>
</div>
</div>

@endsection
