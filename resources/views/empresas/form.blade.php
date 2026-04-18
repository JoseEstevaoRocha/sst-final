@extends('layouts.app')
@section('title', $empresa ? 'Editar Empresa' : 'Nova Empresa')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">{{ $empresa ? 'Editar' : 'Nova' }} Empresa</h1></div>
    <a href="{{ route('empresas.index') }}" class="btn btn-secondary">← Voltar</a>
</div>

<div class="card">
    <form method="POST" action="{{ $empresa ? route('empresas.update',$empresa->id) : route('empresas.store') }}">
        @csrf @if($empresa) @method('PUT') @endif
        <div class="form-grid">

            {{-- Dados básicos --}}
            <div class="form-group form-full">
                <label class="form-label">Razão Social *</label>
                <input type="text" name="razao_social" value="{{ old('razao_social',$empresa->razao_social??'') }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nome Fantasia</label>
                <input type="text" name="nome_fantasia" value="{{ old('nome_fantasia',$empresa->nome_fantasia??'') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">CNPJ * (14 dígitos)</label>
                <input type="text" name="cnpj" value="{{ old('cnpj',$empresa->cnpj??'') }}" class="form-control" maxlength="14" required>
            </div>
            <div class="form-group">
                <label class="form-label">Telefone</label>
                <input type="text" name="telefone" value="{{ old('telefone',$empresa->telefone??'') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" value="{{ old('email',$empresa->email??'') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Cidade</label>
                <input type="text" name="cidade" value="{{ old('cidade',$empresa->cidade??'') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Estado</label>
                <input type="text" name="estado" value="{{ old('estado',$empresa->estado??'') }}" class="form-control" maxlength="2" placeholder="SP">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="ativa"   {{ old('status',$empresa->status??'ativa')==='ativa'   ?'selected':'' }}>Ativa</option>
                    <option value="inativa" {{ old('status',$empresa->status??'')==='inativa'?'selected':'' }}>Inativa</option>
                </select>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Endereço</label>
                <input type="text" name="endereco" value="{{ old('endereco',$empresa->endereco??'') }}" class="form-control">
            </div>

            {{-- ── Segurança / Brigada (NBR 14276) ── --}}
            <div class="form-group form-full" style="margin-top:8px">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-3);padding-bottom:8px;border-bottom:1px solid var(--border);margin-bottom:4px">
                    <i class="fas fa-fire-extinguisher" style="margin-right:5px;color:#db2777"></i>
                    Brigada de Incêndio — NBR 14276
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    CNAE
                    <span style="font-weight:400;font-size:11px;color:var(--text-3)">(ex: 2011-8/00)</span>
                </label>
                <input type="text" name="cnae" id="cnaeInput"
                    value="{{ old('cnae',$empresa->cnae??'') }}"
                    class="form-control" maxlength="10"
                    placeholder="0000-0/00"
                    oninput="detectarGrupo(this.value)">
                <div style="font-size:11px;color:var(--text-3);margin-top:4px">
                    Informe os 4+ primeiros dígitos para auto-detectar o grupo de risco
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Grau de Risco de Incêndio
                    <span style="font-weight:400;font-size:11px;color:var(--text-3)">(NBR 14276)</span>
                </label>
                <select name="grau_risco_incendio" id="grauRiscoSelect" class="form-select" onchange="mostrarInfoGrupo(this.value)">
                    <option value="">Auto (pelo CNAE)</option>
                    <option value="A" {{ old('grau_risco_incendio',$empresa->grau_risco_incendio??'')==='A'?'selected':'' }}>A — Risco Baixo (mín. 5%)</option>
                    <option value="B" {{ old('grau_risco_incendio',$empresa->grau_risco_incendio??'')==='B'?'selected':'' }}>B — Risco Médio (mín. 10%)</option>
                    <option value="C" {{ old('grau_risco_incendio',$empresa->grau_risco_incendio??'')==='C'?'selected':'' }}>C — Risco Alto (mín. 15%)</option>
                    <option value="D" {{ old('grau_risco_incendio',$empresa->grau_risco_incendio??'')==='D'?'selected':'' }}>D — Risco Elevado (mín. 20%)</option>
                </select>
            </div>

            {{-- Info dinâmica do grupo --}}
            <div class="form-group form-full" id="grupoInfoBox" style="display:none">
                <div id="grupoInfoContent" style="border-radius:var(--r-sm);padding:12px 16px;font-size:12px;border-left:4px solid #64748b;background:var(--bg-secondary)">
                </div>
            </div>

        </div>
        <div class="form-footer">
            <a href="{{ route('empresas.index') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
const GRUPOS = {
    'A': { label: 'Grupo A — Risco Baixo',    pct: 5,  cor: '#16a34a', desc: 'Escritórios, atividades financeiras, TI, serviços administrativos. Incêndio com propagação lenta.' },
    'B': { label: 'Grupo B — Risco Médio',    pct: 10, cor: '#2563eb', desc: 'Comércio, transporte, alojamento, têxtil, educação. Risco moderado de ignição.' },
    'C': { label: 'Grupo C — Risco Alto',     pct: 15, cor: '#d97706', desc: 'Indústria alimentícia, farmacêutica, metalurgia, construção civil, saúde. Carga de incêndio elevada.' },
    'D': { label: 'Grupo D — Risco Elevado',  pct: 20, cor: '#dc2626', desc: 'Petroquímica, produtos químicos, mineração, geração de energia. Risco crítico de incêndio ou explosão.' },
};

// Mapeamento divisão CNAE → grupo (espelho do PHP)
function grupoPorCnae(cnae) {
    const digits = cnae.replace(/\D/g,'');
    if (digits.length < 2) return null;
    const div = parseInt(digits.substring(0,2));
    if ([5,6,7,8,9,19,20,35].includes(div))                                                                      return 'D';
    if ([10,11,12,16,17,18,21,22,23,24,25,29,30,41,42,43,86,87,88].includes(div))                                return 'C';
    if ([1,2,3,13,14,15,26,27,28,31,32,33,36,37,38,39,45,46,47,49,50,51,52,53,55,56,85,90,91,92,93].includes(div)) return 'B';
    if ([58,59,60,61,62,63,64,65,66,68,69,70,71,72,73,74,75,77,78,79,80,81,82,84,94,95,96,97,98,99].includes(div)) return 'A';
    return 'B';
}

function detectarGrupo(cnae) {
    const manualSel = document.getElementById('grauRiscoSelect');
    // Só auto-preenche se o campo manual estiver em "Auto"
    if (manualSel.value !== '') { mostrarInfoGrupo(manualSel.value); return; }
    const grupo = grupoPorCnae(cnae);
    if (grupo) mostrarInfoGrupo(grupo, true);
    else document.getElementById('grupoInfoBox').style.display = 'none';
}

function mostrarInfoGrupo(grupo, isAuto) {
    const box  = document.getElementById('grupoInfoBox');
    const cont = document.getElementById('grupoInfoContent');
    const cnae = document.getElementById('cnaeInput')?.value ?? '';
    // Se seleção manual vazia, tenta auto pelo CNAE
    if (!grupo) grupo = grupoPorCnae(cnae) ?? 'B';
    const g = GRUPOS[grupo];
    if (!g) { box.style.display = 'none'; return; }
    box.style.display = '';
    cont.style.borderLeftColor = g.cor;
    cont.innerHTML = `
        <div style="font-weight:700;color:${g.cor};font-size:13px;margin-bottom:4px">
            ${isAuto ? '<i class="fas fa-magic" style="margin-right:4px"></i>Auto-detectado: ' : ''}${g.label}
        </div>
        <div style="color:var(--text-2);margin-bottom:6px">${g.desc}</div>
        <div style="display:flex;align-items:center;gap:12px">
            <span style="font-size:12px;font-weight:700">Mínimo recomendado (NBR 14276):</span>
            <span style="font-size:18px;font-weight:900;color:${g.cor}">${g.pct}%</span>
            <span style="font-size:11px;color:var(--text-3)">da força de trabalho por turno</span>
        </div>
    `;
}

// Inicializa ao carregar
document.addEventListener('DOMContentLoaded', () => {
    const cnae  = document.getElementById('cnaeInput')?.value ?? '';
    const grau  = document.getElementById('grauRiscoSelect')?.value ?? '';
    if (grau) mostrarInfoGrupo(grau);
    else if (cnae) detectarGrupo(cnae);
});
</script>
@endpush
@endsection
