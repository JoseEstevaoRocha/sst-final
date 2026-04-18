@extends('layouts.app')
@section('title','Usuários')
@section('content')
<div class="page-header"><div><h1 class="page-title">Gestão de Usuários</h1><p class="page-sub">{{ $usuarios->total() }} usuários</p></div><button class="btn btn-primary" onclick="openModal('modalUser')"><i class="fas fa-plus"></i> Novo Usuário</button></div>
<div class="card p-0"><div class="table-wrap"><table class="table">
<thead><tr><th>USUÁRIO</th><th>E-MAIL</th><th>EMPRESA</th><th>CARGO</th><th>PERFIL</th><th>ÚLTIMO LOGIN</th><th>STATUS</th><th>AÇÕES</th></tr></thead>
<tbody>
@forelse($usuarios as $u)
<tr>
    <td><div class="flex align-center gap-10"><div class="avatar-sm">{{ $u->initials }}</div><div class="font-bold text-13">{{ $u->name }}</div></div></td>
    <td class="text-12">{{ $u->email }}</td>
    <td class="text-12">{{ $u->empresa?->nome_display??'(Global)' }}</td>
    <td class="text-12">{{ $u->cargo??'—' }}</td>
    <td><span class="badge badge-info">{{ $u->getRoleNames()->first()??'—' }}</span></td>
    <td class="font-mono text-11">{{ $u->last_login_at?->format('d/m/Y H:i')??'Nunca' }}</td>
    <td><span class="badge {{ $u->active?'badge-success':'badge-danger' }}">{{ $u->active?'Ativo':'Inativo' }}</span></td>
    <td><div class="flex gap-4">
        <button onclick="editUser({{ json_encode(['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'cargo'=>$u->cargo,'empresa_id'=>$u->empresa_id,'role'=>$u->getRoleNames()->first()??'operador','active'=>$u->active]) }})" class="btn btn-secondary btn-icon"><i class="fas fa-pencil-alt"></i></button>
        @if($u->id !== auth()->id())<form method="POST" action="{{ route('config.usuarios.destroy',$u->id) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon text-danger" data-confirm="Excluir {{ $u->name }}?"><i class="fas fa-trash-alt"></i></button></form>@endif
    </div></td>
</tr>
@empty
<tr><td colspan="8"><div class="empty-state"><div class="empty-icon"><i class="fas fa-user-cog"></i></div><h3>Nenhum usuário</h3></div></td></tr>
@endforelse
</tbody></table></div></div>

<div class="modal-overlay" id="modalUser"><div class="modal modal-md">
<div class="modal-header"><div class="modal-title" id="userTitle"><i class="fas fa-user-cog"></i> Novo Usuário</div><button class="modal-close" onclick="closeModal('modalUser')"><i class="fas fa-times"></i></button></div>
<div class="modal-body"><form method="POST" id="userForm" action="{{ route('config.usuarios.store') }}">@csrf<div id="userMethod"></div>
<div class="form-grid">
    <div class="form-group form-full"><label class="form-label">Nome *</label><input type="text" name="name" id="uName" class="form-control" required></div>
    <div class="form-group"><label class="form-label">E-mail *</label><input type="email" name="email" id="uEmail" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Cargo</label><input type="text" name="cargo" id="uCargo" class="form-control" placeholder="Ex: Técnico de Segurança"></div>
    <div class="form-group"><label class="form-label">Empresa</label><select name="empresa_id" id="uEmpresa" class="form-select"><option value="">Global (todas as empresas)</option>@foreach($empresas as $e)<option value="{{ $e->id }}">{{ $e->nome_display }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label">Perfil *</label><select name="role" id="uRole" class="form-select" required><option value="visualizador">Visualizador</option><option value="operador">Operador</option><option value="gestor">Gestor</option><option value="admin">Admin</option><option value="super-admin">Super Admin</option></select></div>
    <div class="form-group"><label class="form-label">Status</label><select name="active" id="uActive" class="form-select"><option value="1">Ativo</option><option value="0">Inativo</option></select></div>
    <div class="form-group"><label class="form-label">Senha <span id="pwRequired">*</span></label><input type="password" name="password" id="uPw" class="form-control" placeholder="Mínimo 8 caracteres"></div>
    <div class="form-group"><label class="form-label">Confirmar Senha</label><input type="password" name="password_confirmation" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalUser')">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form></div></div></div>
@endsection
@push('scripts')
<script>
let editUserId = null;
function editUser(u){
    editUserId = u.id;
    document.getElementById('userTitle').innerHTML='<i class="fas fa-user-cog"></i> Editar Usuário';
    document.getElementById('userForm').action=`/configuracoes/usuarios/${u.id}`;
    document.getElementById('userMethod').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('uName').value=u.name;document.getElementById('uEmail').value=u.email;
    document.getElementById('uCargo').value=u.cargo||'';document.getElementById('uEmpresa').value=u.empresa_id||'';
    document.getElementById('uRole').value=u.role||'operador';document.getElementById('uActive').value=u.active?'1':'0';
    document.getElementById('uPw').removeAttribute('required');document.getElementById('pwRequired').textContent='(deixe vazio para manter)';
    openModal('modalUser');
}
</script>
@endpush
