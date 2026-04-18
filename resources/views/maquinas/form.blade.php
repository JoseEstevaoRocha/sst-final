@extends('layouts.app')
@section('title', isset($maquina) ? 'Editar Máquina' : 'Nova Máquina')
@section('content')
<div class="page-header"><div><h1 class="page-title">{{ isset($maquina) ? 'Editar' : 'Nova' }} Máquina — NR12</h1></div><a href="{{ route('maquinas.index') }}" class="btn btn-secondary">← Voltar</a></div>
<div class="card"><form method="POST" action="{{ isset($maquina) ? route('maquinas.update',$maquina->id) : route('maquinas.store') }}">@csrf @if(isset($maquina))@method('PUT')@endif
<div class="form-grid">
    @if(auth()->user()->hasRole('super-admin'))
    <div class="form-group form-full"><label class="form-label">Empresa *</label><select name="empresa_id" class="form-select" required><option value="">Selecione</option>@foreach($empresas as $emp)<option value="{{ $emp->id }}" {{ old('empresa_id',isset($maquina)?$maquina->empresa_id:'')==$emp->id?'selected':'' }}>{{ $emp->nome_display }}</option>@endforeach</select></div>
    @endif
    <div class="form-group form-full"><label class="form-label">Nome da Máquina *</label><input type="text" name="nome" value="{{ old('nome',isset($maquina)?$maquina->nome:'') }}" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Marca</label><input type="text" name="marca" value="{{ old('marca',isset($maquina)?$maquina->marca:'') }}" class="form-control"></div>
    <div class="form-group"><label class="form-label">Modelo</label><input type="text" name="modelo" value="{{ old('modelo',isset($maquina)?$maquina->modelo:'') }}" class="form-control"></div>
    <div class="form-group"><label class="form-label">Número de Série</label><input type="text" name="numero_serie" value="{{ old('numero_serie',isset($maquina)?$maquina->numero_serie:'') }}" class="form-control"></div>
    <div class="form-group"><label class="form-label">Ano de Fabricação</label><input type="number" name="ano_fabricacao" value="{{ old('ano_fabricacao',isset($maquina)?$maquina->ano_fabricacao:'') }}" class="form-control" placeholder="Ex: 2020"></div>
    <div class="form-group"><label class="form-label">Setor</label><select name="setor_id" class="form-select"><option value="">Nenhum</option>@foreach($setores as $s)<option value="{{ $s->id }}" {{ old('setor_id',isset($maquina)?$maquina->setor_id:'')==$s->id?'selected':'' }}>{{ $s->nome }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['operacional'=>'Operacional','inativo'=>'Inativo'] as $v=>$l)<option value="{{ $v }}" {{ old('status',isset($maquina)?$maquina->status:'operacional')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
    <div class="form-group form-full"><label class="form-label">Observações</label><textarea name="observacoes" class="form-control" rows="2">{{ old('observacoes',isset($maquina)?$maquina->observacoes:'') }}</textarea></div>
</div>
<div class="form-footer"><a href="{{ route('maquinas.index') }}" class="btn btn-ghost">Cancelar</a><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button></div>
</form></div>
@endsection
