@extends('layouts.app')
@section('title', $extintor ? 'Editar Extintor' : 'Novo Extintor')
@section('content')
<div class="page-header"><div><h1 class="page-title">{{ $extintor ? 'Editar' : 'Novo' }} Extintor</h1></div><a href="{{ route('extintores.index') }}" class="btn btn-secondary">← Voltar</a></div>
<div class="card"><form method="POST" action="{{ $extintor ? route('extintores.update',$extintor->id) : route('extintores.store') }}">@csrf @if($extintor)@method('PUT')@endif
<div class="form-grid">
    <div class="form-group"><label class="form-label">Número de Série</label><input type="text" name="numero_serie" value="{{ old('numero_serie',$extintor->numero_serie??'') }}" class="form-control"></div>
    <div class="form-group"><label class="form-label">Tipo *</label><select name="tipo" class="form-select" required>@foreach(['agua'=>'Água','po_quimico'=>'Pó Químico','co2'=>'CO₂','espuma'=>'Espuma','halogenado'=>'Halogenado'] as $v=>$l)<option value="{{ $v }}" {{ old('tipo',$extintor->tipo??'')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Capacidade</label><input type="text" name="capacidade" value="{{ old('capacidade',$extintor->capacidade??'') }}" class="form-control" placeholder="Ex: 6kg, 10L"></div>
    <div class="form-group"><label class="form-label">Setor</label><select name="setor_id" class="form-select"><option value="">Selecione</option>@foreach($setores as $s)<option value="{{ $s->id }}" {{ old('setor_id',$extintor->setor_id??'')==$s->id?'selected':'' }}>{{ $s->nome }}</option>@endforeach</select></div>
    <div class="form-group form-full"><label class="form-label">Localização</label><input type="text" name="localizacao" value="{{ old('localizacao',$extintor->localizacao??'') }}" class="form-control" placeholder="Ex: Setor A, próximo à porta de saída"></div>
    <div class="form-group"><label class="form-label">Última Recarga</label><input type="date" name="ultima_recarga" value="{{ old('ultima_recarga',$extintor->ultima_recarga?->format('Y-m-d')??'') }}" class="form-control"></div>
    <div class="form-group"><label class="form-label">Próxima Recarga</label><input type="date" name="proxima_recarga" value="{{ old('proxima_recarga',$extintor->proxima_recarga?->format('Y-m-d')??'') }}" class="form-control"></div>
    <div class="form-group"><label class="form-label">Último Teste Hidrostático</label><input type="date" name="ultimo_teste_hidrostatico" value="{{ old('ultimo_teste_hidrostatico',$extintor->ultimo_teste_hidrostatico?->format('Y-m-d')??'') }}" class="form-control"></div>
    <div class="form-group"><label class="form-label">Próximo Teste Hidrostático</label><input type="date" name="proximo_teste_hidrostatico" value="{{ old('proximo_teste_hidrostatico',$extintor->proximo_teste_hidrostatico?->format('Y-m-d')??'') }}" class="form-control"></div>
    <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['regular'=>'Regular','vencido'=>'Vencido','manutencao'=>'Em Manutenção'] as $v=>$l)<option value="{{ $v }}" {{ old('status',$extintor->status??'regular')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
</div>
<div class="form-footer"><a href="{{ route('extintores.index') }}" class="btn btn-ghost">Cancelar</a><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button></div>
</form></div>
@endsection
