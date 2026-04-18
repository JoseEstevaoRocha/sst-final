@extends('layouts.app')
@section('title','Editar Clínica')
@section('content')
<div class="page-header"><div><h1 class="page-title">Editar Clínica</h1></div><a href="{{ route('clinicas.index') }}" class="btn btn-secondary">← Voltar</a></div>
<div class="card" style="max-width:600px"><form method="POST" action="{{ route('clinicas.update',$clinica->id) }}">@csrf @method('PUT')
<div class="form-grid">
<div class="form-group form-full"><label class="form-label">Nome *</label><input type="text" name="nome" value="{{ $clinica->nome }}" class="form-control" required></div>
<div class="form-group"><label class="form-label">WhatsApp *</label><input type="text" name="whatsapp" value="{{ $clinica->whatsapp }}" class="form-control" required></div>
<div class="form-group"><label class="form-label">Telefone</label><input type="text" name="telefone" value="{{ $clinica->telefone }}" class="form-control"></div>
<div class="form-group"><label class="form-label">E-mail</label><input type="email" name="email" value="{{ $clinica->email }}" class="form-control"></div>
<div class="form-group"><label class="form-label">Cidade</label><input type="text" name="cidade" value="{{ $clinica->cidade }}" class="form-control"></div>
<div class="form-group"><label class="form-label">Estado</label><input type="text" name="estado" value="{{ $clinica->estado }}" class="form-control" maxlength="2"></div>
<div class="form-group"><label class="form-label">Responsável</label><input type="text" name="responsavel" value="{{ $clinica->responsavel }}" class="form-control"></div>
<div class="form-group form-full"><label class="form-label">Endereço</label><input type="text" name="endereco" value="{{ $clinica->endereco }}" class="form-control"></div>
</div>
<div class="form-footer"><a href="{{ route('clinicas.index') }}" class="btn btn-ghost">Cancelar</a><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button></div>
</form></div>
@endsection
