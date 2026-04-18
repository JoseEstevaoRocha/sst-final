@extends('layouts.app')
@section('title','Tamanhos')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-ruler"></i> Tamanhos</h1>
        <div class="text-muted text-13">Grade de tamanhos usada em EPIs, uniformes e notas fiscais.</div>
    </div>
    <div class="flex gap-8">
        <form method="POST" action="{{ route('tamanhos.seed') }}">
            @csrf
            <input type="hidden" name="tipo" value="roupas">
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Criar tamanhos padrão de roupas (PP→G3)?')">
                <i class="fas fa-tshirt"></i> Grade Roupas
            </button>
        </form>
        <form method="POST" action="{{ route('tamanhos.seed') }}">
            @csrf
            <input type="hidden" name="tipo" value="calcados">
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Criar tamanhos de calçados (33 ao 48)?')">
                <i class="fas fa-shoe-prints"></i> Grade Calçados (33–48)
            </button>
        </form>
        <button class="btn btn-primary" onclick="abrirNovo()">
            <i class="fas fa-plus"></i> Novo Tamanho
        </button>
    </div>
</div>

@foreach(['success','error'] as $t)
    @if(session($t))<div class="alert alert-{{ $t==='error'?'danger':'success' }} mb-16">{{ session($t) }}</div>@endif
@endforeach

{{-- Grade visual --}}
<div class="card mb-16">
    <div class="card-header"><div class="card-title"><i class="fas fa-th"></i> Tamanhos Cadastrados</div></div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;padding:4px 0">
        @forelse($tamanhos as $t)
        <div onclick="editTam({{ json_encode($t) }})" title="Clique para editar"
             style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:64px;padding:10px 8px;border:1px solid var(--border);border-radius:8px;cursor:pointer;background:var(--bg-secondary);transition:.15s"
             onmouseover="this.style.borderColor='var(--primary)';this.style.background='var(--bg-card)'"
             onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--bg-secondary)'">
            <span style="font-size:18px;font-weight:700;line-height:1">{{ $t->codigo }}</span>
            @if($t->descricao && $t->descricao !== $t->codigo)
                <span style="font-size:9px;color:var(--text-muted);margin-top:2px;text-align:center;max-width:64px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $t->descricao }}</span>
            @endif
        </div>
        @empty
        <div class="text-muted text-13">Nenhum tamanho. Use os botões acima para criar a grade padrão.</div>
        @endforelse

        <div onclick="abrirNovo()" title="Novo tamanho"
             style="display:flex;align-items:center;justify-content:center;min-width:64px;padding:10px 8px;border:2px dashed var(--border);border-radius:8px;cursor:pointer;color:var(--text-muted);font-size:22px"
             onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
             onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-muted)'">+</div>
    </div>
</div>

{{-- Tabela --}}
<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-list"></i> Lista Completa</div></div>
    @if($tamanhos->isEmpty())
        <div class="empty-state text-center py-32 text-muted">
            <i class="fas fa-ruler" style="font-size:40px;opacity:.3;display:block;margin-bottom:12px"></i>
            Nenhum tamanho cadastrado. Use os botões acima para criar a grade padrão.
        </div>
    @else
    <table class="table">
        <thead>
            <tr>
                <th style="width:80px">Ordem</th>
                <th style="width:120px">Código</th>
                <th>Descrição</th>
                <th style="width:100px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($tamanhos as $t)
            <tr>
                <td class="text-muted text-12">{{ $t->ordem }}</td>
                <td><span style="font-weight:700;font-size:15px">{{ $t->codigo }}</span></td>
                <td class="text-13">{{ $t->descricao ?: '—' }}</td>
                <td>
                    <div class="flex gap-6">
                        <button onclick="editTam({{ json_encode($t) }})" class="btn btn-xs btn-secondary"><i class="fas fa-edit"></i></button>
                        <form method="POST" action="{{ route('tamanhos.destroy', $t->id) }}" onsubmit="return confirm('Excluir tamanho {{ $t->codigo }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Modal --}}
<div class="modal-overlay" id="tamModal" style="display:none" onclick="if(event.target===this)fecharModal()">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title" id="tamTitle"><i class="fas fa-ruler"></i> Novo Tamanho</div>
            <button class="modal-close" onclick="fecharModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" id="tamForm" action="{{ route('tamanhos.store') }}">
                @csrf
                <div id="tamMethod"></div>
                <div class="flex flex-col gap-14">
                    <div class="form-group">
                        <label class="form-label">Código <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" id="tamCod" class="form-control" required placeholder="Ex: 37, XGG, 3G..." style="text-transform:uppercase;font-size:18px;font-weight:700">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" id="tamDesc" class="form-control" placeholder="Ex: Nº 37, Extra Grande...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ordem <span class="text-12 text-muted">(define a sequência na grade)</span></label>
                        <input type="number" name="ordem" id="tamOrdem" class="form-control" value="{{ $tamanhos->count() + 1 }}">
                    </div>
                </div>
                <div class="modal-footer mt-16">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
function abrirNovo() {
    document.getElementById('tamTitle').innerHTML = '<i class="fas fa-ruler"></i> Novo Tamanho';
    document.getElementById('tamForm').action = '{{ route('tamanhos.store') }}';
    document.getElementById('tamMethod').innerHTML = '';
    document.getElementById('tamCod').value = '';
    document.getElementById('tamDesc').value = '';
    document.getElementById('tamOrdem').value = {{ $tamanhos->count() + 1 }};
    document.getElementById('tamModal').style.display = 'flex';
    document.getElementById('tamCod').focus();
}

function editTam(t) {
    document.getElementById('tamTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Tamanho';
    document.getElementById('tamForm').action = `/tamanhos/${t.id}`;
    document.getElementById('tamMethod').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('tamCod').value = t.codigo;
    document.getElementById('tamDesc').value = t.descricao || '';
    document.getElementById('tamOrdem').value = t.ordem;
    document.getElementById('tamModal').style.display = 'flex';
    document.getElementById('tamCod').focus();
}

function fecharModal() {
    document.getElementById('tamModal').style.display = 'none';
}
</script>
@endpush
