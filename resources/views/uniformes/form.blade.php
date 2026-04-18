@extends('layouts.app')
@section('title', isset($uniforme) && $uniforme ? 'Editar Uniforme' : 'Novo Uniforme')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">{{ isset($uniforme) && $uniforme ? 'Editar' : 'Novo' }} Uniforme</h1></div>
    <a href="{{ route('uniformes.index') }}" class="btn btn-secondary">← Voltar</a>
</div>

<form method="POST" action="{{ isset($uniforme) && $uniforme ? route('uniformes.update',$uniforme->id) : route('uniformes.store') }}">
@csrf @if(isset($uniforme) && $uniforme)@method('PUT')@endif

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-tshirt"></i> Dados do Uniforme</h3></div>
    <div class="form-grid" style="padding:1.5rem">
        <div class="form-group">
            <label class="form-label">Empresa</label>
            <select name="empresa_id" class="form-select">
                <option value="">Todas / Geral</option>
                @foreach($empresas as $emp)
                <option value="{{ $emp->id }}" {{ old('empresa_id', $uniforme->empresa_id ?? '') == $emp->id ? 'selected' : '' }}>{{ $emp->nome_display }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Tipo *</label>
            <select name="tipo" class="form-select" required>
                @foreach(['Camisa','Calça','Bota','Jaleco','Colete','Cinto','Boné','Macacão','Luva','Outros'] as $t)
                <option value="{{ $t }}" {{ old('tipo', $uniforme->tipo ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group form-full">
            <label class="form-label">Nome *</label>
            <input type="text" name="nome" value="{{ old('nome', $uniforme->nome ?? '') }}" class="form-control" required placeholder="Ex: Camisa Polo Azul">
        </div>
        <div class="form-group">
            <label class="form-label">Fornecedor</label>
            <input type="text" name="fornecedor" value="{{ old('fornecedor', $uniforme->fornecedor ?? '') }}" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">Custo Unitário (R$)</label>
            <input type="number" name="custo_unitario" value="{{ old('custo_unitario', $uniforme->custo_unitario ?? '') }}" class="form-control" step="0.01" min="0">
        </div>
        <div class="form-group">
            <label class="form-label">Estoque Mínimo Padrão</label>
            <input type="number" name="estoque_minimo_padrao" id="estMinPadrao" value="{{ old('estoque_minimo_padrao', $uniforme->estoque_minimo_padrao ?? 0) }}" class="form-control" min="0">
            <small class="text-muted">Quantidade mínima aplicada à grade padrão</small>
        </div>
        <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="Ativo"  {{ old('status', $uniforme->status ?? 'Ativo') === 'Ativo'  ? 'selected' : '' }}>Ativo</option>
                <option value="Inativo"{{ old('status', $uniforme->status ?? 'Ativo') === 'Inativo' ? 'selected' : '' }}>Inativo</option>
            </select>
        </div>
        <div class="form-group form-full">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" class="form-control" rows="2">{{ old('descricao', $uniforme->descricao ?? '') }}</textarea>
        </div>
    </div>
</div>

@if(!isset($uniforme) || !$uniforme)
{{-- Grade padrão só para novos uniformes --}}
<div class="card" style="margin-top:1rem">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <h3 class="card-title"><i class="fas fa-ruler"></i> Grade Padrão de Estoque</h3>
        <div class="flex gap-8">
            <button type="button" class="btn btn-ghost btn-sm" onclick="selecionarTodos()">Selecionar todos</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="limparTodos()">Limpar</button>
        </div>
    </div>
    <div style="padding:1.5rem">
        <p class="text-muted text-13" style="margin-bottom:1rem">Selecione os tamanhos que fazem parte da grade deste uniforme. O estoque inicial será 0 e o mínimo virá do campo acima (pode ajustar individualmente).</p>
        <div class="grade-setup-grid" id="gradeGrid">
            @foreach($tamanhos as $tam)
            @php $checked = false; $minimo = old("grade_tamanhos.{$tam->id}", ''); @endphp
            <div class="grade-setup-item {{ $checked ? 'selected' : '' }}" id="gItem_{{ $tam->id }}" onclick="toggleTam({{ $tam->id }})">
                <div class="grade-setup-check"><i class="fas fa-check"></i></div>
                <div class="grade-setup-code">{{ $tam->codigo }}</div>
                <div class="grade-setup-desc">{{ $tam->descricao }}</div>
                <input type="checkbox" name="grade_check[]" value="{{ $tam->id }}" id="gCheck_{{ $tam->id }}" style="display:none" {{ $checked ? 'checked' : '' }}>
                <div class="grade-setup-min" onclick="event.stopPropagation()" style="display:{{ $checked ? 'block' : 'none' }}" id="gMin_{{ $tam->id }}">
                    <label style="font-size:11px;color:var(--text-3)">Mínimo</label>
                    <input type="number" name="grade_tamanhos[{{ $tam->id }}]" id="gMinVal_{{ $tam->id }}" class="form-control form-control-sm" min="0" value="{{ $minimo }}" placeholder="Auto" style="width:100%;margin-top:2px">
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@else
{{-- Em modo edição: só exibe o estoque atual (gerenciado na tela de catálogo) --}}
<div class="card" style="margin-top:1rem">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-ruler"></i> Grade de Estoque</h3></div>
    <div style="padding:1.5rem">
        <p class="text-muted text-13">A grade de estoque é gerenciada diretamente no catálogo. <a href="{{ route('uniformes.index') }}" class="link">Ir para o catálogo</a></p>
        <div class="grade-chips" style="margin-top:1rem">
            @forelse($uniforme->estoques as $est)
            @php $nivel = $est->quantidade<=0?'danger':($est->baixo_estoque?'warn':'ok'); @endphp
            <span class="grade-chip grade-{{ $nivel }}">
                <span class="grade-code">{{ $est->tamanho->codigo }}</span>
                <span class="grade-qty">{{ $est->quantidade }}</span>
            </span>
            @empty
            <span class="text-muted text-13">Nenhum tamanho cadastrado ainda.</span>
            @endforelse
        </div>
    </div>
</div>
@endif

<div class="card" style="margin-top:1rem">
    <div class="form-footer">
        <a href="{{ route('uniformes.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
    </div>
</div>
</form>
@endsection

@push('styles')
<style>
.grade-setup-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px,1fr));
    gap: 10px;
}
.grade-setup-item {
    border: 2px solid var(--border);
    border-radius: var(--r);
    padding: 12px 10px 10px;
    cursor: pointer;
    text-align: center;
    position: relative;
    transition: border-color .15s, background .15s;
    user-select: none;
}
.grade-setup-item:hover { border-color: var(--brand); background: rgba(var(--brand-rgb),.04); }
.grade-setup-item.selected { border-color: var(--brand); background: rgba(var(--brand-rgb),.08); }
.grade-setup-check {
    position: absolute; top: 6px; right: 6px;
    width: 18px; height: 18px;
    border: 2px solid var(--border); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 9px; color: #fff; background: transparent;
    transition: all .15s;
}
.grade-setup-item.selected .grade-setup-check { background: var(--brand); border-color: var(--brand); }
.grade-setup-code { font-size: 18px; font-weight: 700; color: var(--text-1); margin-bottom: 2px; }
.grade-setup-desc { font-size: 10px; color: var(--text-3); margin-bottom: 6px; }
.grade-setup-min { margin-top: 8px; }
</style>
@endpush

@push('scripts')
<script>
function toggleTam(id) {
    const item  = document.getElementById('gItem_'+id);
    const check = document.getElementById('gCheck_'+id);
    const minDiv= document.getElementById('gMin_'+id);
    const minVal= document.getElementById('gMinVal_'+id);
    const sel   = item.classList.toggle('selected');
    check.checked = sel;
    minDiv.style.display = sel ? 'block' : 'none';
    if (sel && !minVal.value) {
        const pad = document.getElementById('estMinPadrao');
        minVal.value = pad ? pad.value : '';
    }
    if (!sel) { minVal.value = ''; minVal.name = 'grade_skip['+id+']'; }
    else { minVal.name = 'grade_tamanhos['+id+']'; }
}
function selecionarTodos() {
    document.querySelectorAll('.grade-setup-item').forEach(el => {
        const id = el.id.replace('gItem_','');
        if (!el.classList.contains('selected')) toggleTam(id);
    });
}
function limparTodos() {
    document.querySelectorAll('.grade-setup-item.selected').forEach(el => {
        const id = el.id.replace('gItem_','');
        toggleTam(id);
    });
}
// Sync min padrao to empty grade fields
document.getElementById('estMinPadrao')?.addEventListener('input', function() {
    document.querySelectorAll('.grade-setup-item.selected').forEach(el => {
        const id = el.id.replace('gItem_','');
        const inp = document.getElementById('gMinVal_'+id);
        if (inp && !inp.value) inp.value = this.value;
    });
});
</script>
@endpush
