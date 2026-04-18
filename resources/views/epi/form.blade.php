@extends('layouts.app')
@section('title', $epi ? 'Editar EPI' : 'Novo EPI')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">{{ $epi ? 'Editar EPI' : 'Novo EPI' }}</h1></div>
    <a href="{{ route('epis.index') }}" class="btn btn-secondary">← Voltar</a>
</div>
<div class="card">
<form method="POST" action="{{ $epi ? route('epis.update',$epi->id) : route('epis.store') }}">
@csrf @if($epi)@method('PUT')@endif
<div class="form-grid">
    <div class="form-group form-full">
        <label class="form-label">Nome *</label>
        <input type="text" name="nome" value="{{ old('nome',$epi->nome??'') }}" class="form-control" required>
    </div>
    <div class="form-group">
        <label class="form-label">Tipo *</label>
        <select name="tipo" class="form-select" required>
            @foreach(['Capacete','Luva','Óculos','Protetor Auricular','Calçado de Segurança','Respirador','Cinto de Segurança','Colete','Uniforme','Outros'] as $t)
            <option value="{{ $t }}" {{ old('tipo',$epi->tipo??'')===$t?'selected':'' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Número do CA
            <span id="ca_loading" style="display:none;font-size:10px;color:var(--brand);margin-left:6px">
                <i class="fas fa-spinner fa-spin"></i> Consultando CAEPI...
            </span>
        </label>
        <div style="display:flex;gap:6px">
            <input type="text" name="numero_ca" id="numero_ca" value="{{ old('numero_ca',$epi->numero_ca??'') }}"
                class="form-control" placeholder="Ex: 498232" oninput="caInput(this.value)">
            <button type="button" class="btn btn-secondary" onclick="buscarCa()" title="Consultar CAEPI">
                <i class="fas fa-search"></i>
            </button>
        </div>
        {{-- Card de resultado do CA --}}
        <div id="ca_resultado" style="display:none;margin-top:8px;padding:10px 14px;border-radius:var(--r-sm);font-size:12px;border:1px solid var(--border);background:var(--bg-secondary)">
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Validade do CA
            <span style="font-size:10px;color:var(--text-3);margin-left:4px">(preenchida automaticamente pelo CA)</span>
        </label>
        <input type="date" name="validade_ca" id="validade_ca" value="{{ old('validade_ca',$epi?->validade_ca?->format('Y-m-d')??'') }}" class="form-control">
    </div>
    <div class="form-group">
        <label class="form-label">Fornecedor</label>
        <input type="text" name="fornecedor" value="{{ old('fornecedor',$epi->fornecedor??'') }}" class="form-control">
    </div>
    <div class="form-group">
        <label class="form-label">Fabricante</label>
        <input type="text" name="fabricante" value="{{ old('fabricante',$epi->fabricante??'') }}" class="form-control">
    </div>
    <div class="form-group" style="position:relative">
        <label class="form-label">Marca</label>
        <input type="text" id="inputMarca" name="marca" value="{{ old('marca',$epi->marca??'') }}" class="form-control" autocomplete="off" oninput="autocompleteMarca(this.value)">
        <div id="marcaSugestoes"></div>
    </div>
    <div class="form-group">
        <label class="form-label">Vida Útil (dias)</label>
        <input type="number" name="vida_util_dias" value="{{ old('vida_util_dias',$epi->vida_util_dias??'') }}" class="form-control" placeholder="Ex: 365">
    </div>
    <div class="form-group">
        <label class="form-label">Estoque Mínimo</label>
        <input type="number" name="estoque_minimo" value="{{ old('estoque_minimo',$epi->estoque_minimo??0) }}" class="form-control" min="0">
    </div>
    <div class="form-group">
        <label class="form-label">Unidade</label>
        <select name="unidade" class="form-select">
            @foreach(['un','par','kit','cx','rolo'] as $u)
            <option value="{{ $u }}" {{ old('unidade',$epi->unidade??'un')===$u?'selected':'' }}>{{ $u }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Custo Unitário (R$)</label>
        <input type="number" name="custo_unitario" value="{{ old('custo_unitario',$epi->custo_unitario??'') }}" class="form-control" step="0.01">
    </div>
    <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="Ativo"  {{ old('status',$epi->status??'Ativo')==='Ativo'?'selected':'' }}>Ativo</option>
            <option value="Inativo"{{ old('status',$epi->status??'')   ==='Inativo'?'selected':'' }}>Inativo</option>
        </select>
    </div>
    <div class="form-group form-full">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control" rows="2">{{ old('descricao',$epi->descricao??'') }}</textarea>
    </div>

    {{-- ── Tamanhos ─────────────────────────────────────────────────────────── --}}
    <div class="form-group form-full">
        <div style="display:flex;align-items:center;gap:10px;padding:14px 0 8px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;font-size:14px">
                <input type="checkbox" name="tem_tamanho" id="temTamanho" value="1"
                    {{ old('tem_tamanho', $epi?->tem_tamanho ?? false) ? 'checked' : '' }}
                    onchange="toggleTamanhos()"
                    style="width:18px;height:18px;cursor:pointer;accent-color:var(--brand)">
                Este EPI requer tamanho (ex: botinas, luvas)
            </label>
        </div>

        <div id="tamanhosSection" style="{{ old('tem_tamanho', $epi?->tem_tamanho ?? false) ? '' : 'display:none' }}">
            <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--r);padding:16px">
                <div style="font-size:13px;font-weight:600;color:var(--text-2);margin-bottom:12px">
                    <i class="fas fa-ruler-combined"></i> Tamanhos disponíveis para este EPI
                </div>
                @php $selecionados = old('tamanho_ids', $epi?->tamanhos?->pluck('id')->toArray() ?? []); @endphp
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    @forelse($tamanhos as $t)
                    <label style="display:flex;align-items:center;gap:6px;padding:7px 12px;background:var(--bg-card);border:1.5px solid var(--border);border-radius:20px;cursor:pointer;font-size:13px;font-weight:500;user-select:none;transition:all .15s"
                        class="tam-chip {{ in_array($t->id, $selecionados) ? 'tam-chip-sel' : '' }}">
                        <input type="checkbox" name="tamanho_ids[]" value="{{ $t->id }}"
                            {{ in_array($t->id, $selecionados) ? 'checked' : '' }}
                            onchange="toggleChip(this)"
                            style="cursor:pointer;accent-color:var(--brand)">
                        {{ $t->codigo }}
                        @if($t->descricao)<span style="font-size:11px;color:var(--text-3)">({{ $t->descricao }})</span>@endif
                    </label>
                    @empty
                    <div class="text-13 text-muted">
                        Nenhum tamanho cadastrado.
                        <a href="{{ route('tamanhos.index') }}" target="_blank">Cadastrar tamanhos</a>
                    </div>
                    @endforelse
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:10px">
                    <i class="fas fa-info-circle"></i>
                    Marque todos os tamanhos que serão controlados no estoque e entregues para colaboradores.
                    Gerencie tamanhos em <a href="{{ route('tamanhos.index') }}" target="_blank">Configurações → Tamanhos</a>.
                </div>
            </div>
        </div>
    </div>
</div>
<div class="form-footer">
    <a href="{{ route('epis.index') }}" class="btn btn-ghost">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
</div>
</form>
</div>

@push('styles')
<style>
.tam-chip-sel {
    background: rgba(var(--brand-rgb),.1) !important;
    border-color: var(--brand) !important;
    color: var(--brand) !important;
}
</style>
@endpush

@push('scripts')
<script>
// ── Consulta CA CAEPI ────────────────────────────────────────────────────────
let caTimer = null;

function caInput(val) {
    clearTimeout(caTimer);
    const v = val.trim();
    if (v.length >= 3) {
        caTimer = setTimeout(() => buscarCa(), 800); // aguarda parar de digitar
    } else {
        document.getElementById('ca_resultado').style.display = 'none';
    }
}

async function buscarCa() {
    const ca = document.getElementById('numero_ca').value.trim();
    if (!ca) return;

    const loading = document.getElementById('ca_loading');
    const result  = document.getElementById('ca_resultado');
    loading.style.display = '';

    try {
        const resp = await fetch(`/api/ca/${encodeURIComponent(ca)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const data = await resp.json();
        loading.style.display = 'none';

        if (!resp.ok || !data.encontrado) {
            result.style.display = '';
            result.style.borderColor = 'var(--danger)';
            result.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i>
                <strong style="color:var(--danger)">CA não encontrado na base CAEPI.</strong>
                <div style="color:var(--text-3);margin-top:4px">Verifique o número ou cadastre manualmente.</div>`;
            return;
        }

        // Preenche campos automaticamente
        const sit = (data.situacao || '').toLowerCase();
        const corSit = sit.includes('válido') ? 'var(--success)' : 'var(--danger)';
        const validade = data.data_validade
            ? new Date(data.data_validade + 'T00:00:00').toLocaleDateString('pt-BR')
            : '—';

        result.style.display = '';
        result.style.borderColor = sit.includes('válido') ? 'var(--success)' : 'var(--danger)';
        result.innerHTML = `
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <span style="font-weight:700;font-size:13px">${data.nome_equipamento ?? '—'}</span>
                <span style="background:${corSit};color:#fff;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700">${data.situacao ?? '—'}</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;color:var(--text-2)">
                <div><span style="color:var(--text-3)">Fabricante:</span> ${data.razao_social ?? '—'}</div>
                <div><span style="color:var(--text-3)">Validade:</span> <strong style="color:${corSit}">${validade}</strong></div>
                <div><span style="color:var(--text-3)">Marca:</span> ${data.marca ?? '—'}</div>
                <div><span style="color:var(--text-3)">Norma:</span> ${data.norma ?? '—'}</div>
                ${data.descricao_equipamento ? `<div style="grid-column:span 2;color:var(--text-3)">${data.descricao_equipamento}</div>` : ''}
            </div>
            <div style="margin-top:8px;font-size:11px;color:var(--brand)">
                <i class="fas fa-magic"></i> Dados preenchidos automaticamente — você pode editar se necessário.
            </div>`;

        // Preenche campos do formulário
        if (data.data_validade) {
            const vEl = document.getElementById('validade_ca');
            if (vEl && !vEl.value) vEl.value = data.data_validade;
        }
        const fabEl = document.querySelector('input[name="fabricante"]');
        if (fabEl && !fabEl.value && data.razao_social) fabEl.value = data.razao_social;
        const nomeEl = document.querySelector('input[name="nome"]');
        if (nomeEl && !nomeEl.value && data.nome_equipamento) nomeEl.value = data.nome_equipamento;

    } catch (e) {
        loading.style.display = 'none';
        result.style.display = '';
        result.style.borderColor = 'var(--warning)';
        result.innerHTML = `<i class="fas fa-wifi" style="color:var(--warning)"></i>
            <span style="color:var(--text-2)"> API CAEPI indisponível. Preencha manualmente.</span>`;
    }
}

// Ao abrir em modo edição, mostra dados do CA já salvo
@if($epi && $epi->numero_ca && $epi->ca_situacao)
document.addEventListener('DOMContentLoaded', () => buscarCa());
@endif

function toggleTamanhos() {
    const checked = document.getElementById('temTamanho').checked;
    document.getElementById('tamanhosSection').style.display = checked ? '' : 'none';
}
function toggleChip(cb) {
    const label = cb.closest('label');
    if (cb.checked) label.classList.add('tam-chip-sel');
    else label.classList.remove('tam-chip-sel');
}

// ── Autocomplete Marca ────────────────────────────────────────────────────────
let marcaTimer;
function autocompleteMarca(q) {
    clearTimeout(marcaTimer);
    const box = document.getElementById('marcaSugestoes');
    if (!q || q.length < 1) { box.innerHTML = ''; return; }
    marcaTimer = setTimeout(() => {
        fetch(`{{ route('api.marcas') }}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(lista => {
                if (!lista.length) { box.innerHTML = ''; return; }
                box.innerHTML = '<div style="position:absolute;left:0;right:0;background:var(--bg-card);border:1px solid var(--border);border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);z-index:100;max-height:200px;overflow-y:auto">' +
                    lista.map(m => `<div style="padding:8px 14px;cursor:pointer;font-size:13px" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background=''" onclick="selecionarMarca('${m.replace(/'/g,"\\'")}', 'inputMarca', 'marcaSugestoes')">${m}</div>`).join('') +
                    '</div>';
            });
    }, 200);
}
function selecionarMarca(valor, inputId, boxId) {
    document.getElementById(inputId).value = valor;
    document.getElementById(boxId).innerHTML = '';
}
document.addEventListener('click', e => {
    if (!e.target.closest('#inputMarca') && !e.target.closest('#marcaSugestoes')) {
        document.getElementById('marcaSugestoes').innerHTML = '';
    }
});
</script>
@endpush
@endsection
