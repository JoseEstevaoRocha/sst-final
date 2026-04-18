@extends('layouts.app')
@section('title','Nova Clínica')
@section('content')
<div class="page-header"><div><h1 class="page-title">Nova Clínica</h1></div><a href="{{ route('clinicas.index') }}" class="btn btn-secondary">← Voltar</a></div>
<div class="card" style="max-width:600px"><form method="POST" action="{{ route('clinicas.store') }}">@csrf
<div class="form-grid">
<div class="form-group form-full"><label class="form-label">Nome *</label><input type="text" name="nome" class="form-control" required></div>
<div class="form-group"><label class="form-label">WhatsApp *</label><input type="text" name="whatsapp" class="form-control" placeholder="(11) 99999-0000" required></div>
<div class="form-group"><label class="form-label">Telefone</label><input type="text" name="telefone" class="form-control"></div>
<div class="form-group"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control"></div>
<div class="form-group"><label class="form-label">Cidade</label><input type="text" name="cidade" class="form-control"></div>
<div class="form-group"><label class="form-label">Estado</label><input type="text" name="estado" class="form-control" maxlength="2" placeholder="SP"></div>
<div class="form-group"><label class="form-label">Responsável</label><input type="text" name="responsavel" class="form-control"></div>
<div class="form-group form-full"><label class="form-label">Endereço</label><input type="text" name="endereco" class="form-control"></div>
</div>
<div class="form-footer"><a href="{{ route('clinicas.index') }}" class="btn btn-ghost">Cancelar</a><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button></div>
</form></div>
@endsection
