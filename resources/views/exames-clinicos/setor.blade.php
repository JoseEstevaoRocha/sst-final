@extends('layouts.app')
@section('title','Exames — '.$setor->nome)
@php
$tipos = \App\Models\ExameClinico::TIPOS;
$badgeTipo = ['audiometria'=>'badge-info','laboratorial'=>'badge-warning','imagem'=>'badge-secondary','clinico'=>'badge-success','espirometria'=>'badge-info','ecg'=>'badge-danger','outros'=>'badge-secondary'];
@endphp
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Exames — {{ $setor->nome }}</h1>
        <p class="page-sub">{{ $setor->empresa?->nome_display }} &nbsp;·&nbsp; {{ $setor->exames->count() }} exame(s) associado(s)</p>
    </div>
    <div class="flex gap-8">
        <a href="{{ route('setores.index') }}" class="btn btn-secondary">← Voltar</a>
        <button class="btn btn-primary" onclick="openModal('modalAddExame')"><i class="fas fa-plus"></i> Associar Exame</button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-16">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

{{-- Lista de exames associados --}}
<div class="card p-0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:600;font-size:14px">
        <i class="fas fa-stethoscope" style="color:var(--primary)"></i> Exames deste Setor
    </div>
    <div class="table-wrap"><table class="table">
    <thead><tr><th>EXAME</th><th>TIPO</th><th>PERIODICIDADE</th><th>OBRIGATÓRIO</th><th></th></tr></thead>
    <tbody>
    @forelse($setor->exames as $ex)
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
            <form method="POST" action="{{ route('setores.exames.remove',[$setor->id,$ex->id]) }}" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Remover exame?"><i class="fas fa-times"></i></button>
            </form>
        </td>
    </tr>
    @empty
    <tr><td colspan="5">
        <div class="empty-state" style="padding:30px">
            <div class="empty-icon"><i class="fas fa-stethoscope"></i></div>
            <h3>Nenhum exame associado</h3>
            <p class="text-muted">Clique em "Associar Exame" para começar.</p>
        </div>
    </td></tr>
    @endforelse
    </tbody></table></div>
</div>

{{-- Painel informativo --}}
<div>
    <div class="card" style="margin-bottom:16px">
        <div style="font-weight:600;font-size:13px;margin-bottom:12px;color:var(--text-muted)">SOBRE ESTE SETOR</div>
        <div class="flex flex-col gap-8">
            <div><span class="text-muted text-12">Empresa:</span><br><strong>{{ $setor->empresa?->nome_display ?? '—' }}</strong></div>
            <div><span class="text-muted text-12">Funções:</span><br><strong>{{ $setor->funcoes()->count() }} função(ões)</strong></div>
            <div><span class="text-muted text-12">Exames associados:</span><br><strong>{{ $setor->exames->count() }}</strong></div>
        </div>
        @if($setor->funcoes()->count() > 0)
        <div style="margin-top:14px;padding:10px;background:var(--bg-secondary);border-radius:var(--r-sm);font-size:12px;color:var(--text-muted)">
            <i class="fas fa-info-circle"></i> As funções deste setor podem herdar estes exames automaticamente.
        </div>
        @endif
    </div>

    <div class="card">
        <div style="font-weight:600;font-size:13px;margin-bottom:12px;color:var(--text-muted)">FUNÇÕES DESTE SETOR</div>
        @forelse($setor->funcoes as $f)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
            <span class="text-13">{{ $f->nome }}</span>
            <a href="{{ route('funcoes.exames',$f->id) }}" class="btn btn-ghost btn-sm" style="font-size:11px">
                <i class="fas fa-stethoscope"></i> {{ $f->exames()->count() }} exame(s)
            </a>
        </div>
        @empty
        <p class="text-muted text-12">Nenhuma função neste setor.</p>
        @endforelse
    </div>
</div>

</div>

{{-- Modal Associar Exame --}}
<div class="modal-overlay" id="modalAddExame">
<div class="modal modal-sm">
<div class="modal-header">
    <div class="modal-title"><i class="fas fa-plus-circle"></i> Associar Exame ao Setor</div>
    <button class="modal-close" onclick="closeModal('modalAddExame')"><i class="fas fa-times"></i></button>
</div>
<div class="modal-body">
<form method="POST" action="{{ route('setores.exames.add',$setor->id) }}">@csrf
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
    <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> Associar</button>
</div>
</form>
</div>
</div>
</div>

@endsection
