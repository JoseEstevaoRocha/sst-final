@extends('layouts.app')
@section('title','Importação em Lote')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Importação em Lote</h1>
        <p class="page-sub">Colaboradores, funções e EPIs via CSV ou XLSX</p>
    </div>
</div>

@if(session('importResult'))
@php $res = session('importResult'); $temErros = count($res['erros']) > 0; $temOk = count($res['ok'] ?? []) > 0; @endphp
<div class="card mb-20">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-chart-bar"></i> Resultado da Importação</div>
        <a href="{{ route('importacao.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i> Nova Importação</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px">
        <div style="text-align:center;padding:16px;background:rgba(63,185,80,.08);border-radius:var(--r-sm);border:1px solid rgba(63,185,80,.2)">
            <div style="font-size:32px;font-weight:800;color:var(--success)">{{ $res['sucesso'] }}</div>
            <div style="font-size:11px;text-transform:uppercase;color:var(--text-3);margin-top:4px">Importados / Atualizados</div>
        </div>
        <div style="text-align:center;padding:16px;background:{{ $temErros ? 'rgba(248,81,73,.08)' : 'var(--bg-secondary)' }};border-radius:var(--r-sm);border:1px solid {{ $temErros ? 'rgba(248,81,73,.2)' : 'var(--border)' }}">
            <div style="font-size:32px;font-weight:800;color:{{ $temErros ? 'var(--danger)' : 'var(--text-3)' }}">{{ count($res['erros']) }}</div>
            <div style="font-size:11px;text-transform:uppercase;color:var(--text-3);margin-top:4px">Erros</div>
        </div>
        <div style="text-align:center;padding:16px;background:var(--bg-secondary);border-radius:var(--r-sm);border:1px solid var(--border)">
            <div style="font-size:32px;font-weight:800">{{ $res['total'] }}</div>
            <div style="font-size:11px;text-transform:uppercase;color:var(--text-3);margin-top:4px">Total de linhas</div>
        </div>
    </div>

    @if($temErros)
    <div style="margin-bottom:16px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
            <i class="fas fa-times-circle" style="color:var(--danger)"></i>
            <strong style="color:var(--danger)">{{ count($res['erros']) }} linha(s) com erro — NÃO foram importadas:</strong>
        </div>
        <div style="border:1px solid rgba(248,81,73,.3);border-radius:var(--r-sm);overflow:hidden">
            @foreach($res['erros'] as $idx => $err)
            <div style="padding:8px 12px;background:{{ $idx%2==0 ? 'rgba(248,81,73,.04)' : 'transparent' }};border-bottom:1px solid rgba(248,81,73,.1);font-size:12px;font-family:monospace;color:var(--danger)">
                <i class="fas fa-exclamation-circle" style="margin-right:6px;opacity:.7"></i>{{ $err }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($temOk)
    <div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer" onclick="toggleOk()" id="okToggle">
            <i class="fas fa-check-circle" style="color:var(--success)"></i>
            <strong style="color:var(--success)">{{ $res['sucesso'] }} registro(s) com sucesso</strong>
            <i class="fas fa-chevron-down" style="font-size:10px;color:var(--text-3);margin-left:4px" id="okChevron"></i>
        </div>
        <div id="okList" style="display:none;border:1px solid rgba(63,185,80,.3);border-radius:var(--r-sm);overflow:hidden;max-height:300px;overflow-y:auto">
            @foreach($res['ok'] as $idx => $msg)
            <div style="padding:7px 12px;background:{{ $idx%2==0 ? 'rgba(63,185,80,.04)' : 'transparent' }};border-bottom:1px solid rgba(63,185,80,.1);font-size:12px;color:var(--success)">
                <i class="fas fa-check" style="margin-right:6px;opacity:.7"></i>{{ $msg }}
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@else

{{-- Abas de importação --}}
<div style="display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:24px" id="abas">
    <button class="aba aba-ativa" onclick="trocarAba('colaboradores')">
        <i class="fas fa-users"></i> Colaboradores
    </button>
    <button class="aba" onclick="trocarAba('funcoes')">
        <i class="fas fa-briefcase"></i> Funções (CBO / Descritivo)
    </button>
    <button class="aba" onclick="trocarAba('epis')">
        <i class="fas fa-hard-hat"></i> EPIs
    </button>
    <button class="aba" onclick="trocarAba('asos')">
        <i class="fas fa-clipboard-list"></i> Exames (ASO)
    </button>
</div>

{{-- ── ABA COLABORADORES ─────────────────────────────────────────── --}}
<div id="aba-colaboradores" class="aba-conteudo">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-file-import"></i> Importar Colaboradores</div></div>
        <form method="POST" action="{{ route('importacao.colaboradores') }}" enctype="multipart/form-data">@csrf
            <div class="dropzone" id="dropzone-col">
                <input type="file" name="arquivo" id="fileCol" accept=".csv,.txt,.xlsx,.xls" required>
                <div class="drop-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <h3>Arraste o arquivo aqui</h3>
                <p>ou clique para selecionar</p>
                <p style="margin-top:6px;font-size:11px;color:var(--text-3)">CSV (separador ;) ou XLSX · máx. 10MB</p>
                <div class="drop-filename" id="fn-col"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-full mt-16"><i class="fas fa-upload"></i> Importar</button>
        </form>
    </div>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <div class="card-title"><i class="fas fa-info-circle"></i> Instruções</div>
            <a href="{{ route('importacao.modelo','colaboradores') }}" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Baixar modelo</a>
        </div>
        <div class="flex flex-col gap-10 text-13 text-muted mb-16">
            @foreach(['Baixe o modelo e preencha sem remover colunas','O CNPJ da empresa deve existir no sistema','Setor e Função são criados se não existirem','CPFs duplicados são ignorados com aviso','Datas: AAAA-MM-DD ou DD/MM/AAAA'] as $idx=>$txt)
            <div class="flex gap-10 align-center"><span style="width:22px;height:22px;border-radius:50%;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">{{ $idx+1 }}</span><span>{{ $txt }}</span></div>
            @endforeach
        </div>
        <div style="background:var(--bg-secondary);border-radius:var(--r-sm);padding:12px;font-size:11px;font-family:monospace;color:var(--text-2)">
            <div style="font-weight:700;margin-bottom:6px;color:var(--text-1)">Colunas obrigatórias (*):</div>
            <div>nome * | cpf * | cnpj_empresa *</div>
            <div>nome_setor * | nome_funcao *</div>
            <div>data_nascimento * | sexo (M/F) *</div>
            <div>data_admissao *</div>
            <div style="margin-top:8px;font-weight:700;color:var(--text-1)">Opcionais:</div>
            <div>status | matricula | cbo | escolaridade</div>
            <div>pis | telefone | email</div>
        </div>
    </div>
</div>
</div>

{{-- ── ABA FUNÇÕES ───────────────────────────────────────────────── --}}
<div id="aba-funcoes" class="aba-conteudo" style="display:none">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-file-import"></i> Importar CBO e Descritivo</div></div>
        <form method="POST" action="{{ route('importacao.funcoes') }}" enctype="multipart/form-data">@csrf
            <div class="dropzone" id="dropzone-func">
                <input type="file" name="arquivo" id="fileFunc" accept=".csv,.txt,.xlsx,.xls" required>
                <div class="drop-icon"><i class="fas fa-briefcase"></i></div>
                <h3>Arraste o arquivo aqui</h3>
                <p>ou clique para selecionar</p>
                <p style="margin-top:6px;font-size:11px;color:var(--text-3)">CSV (separador ;) ou XLSX · máx. 10MB</p>
                <div class="drop-filename" id="fn-func"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-full mt-16"><i class="fas fa-upload"></i> Atualizar Funções</button>
        </form>
    </div>
    <div class="card">
        <div class="card-title mb-14"><i class="fas fa-info-circle"></i> Como funciona</div>

        {{-- Passo a passo --}}
        <div class="flex flex-col gap-10 text-13 text-muted mb-16">
            @foreach([
                'Escolha a empresa e os setores abaixo e clique em "Baixar modelo preenchido"',
                'O arquivo já vem com todas as funções, CNPJ e setor preenchidos',
                'Você só precisa adicionar o CBO e/ou o descritivo de cada função',
                'Só os campos preenchidos são atualizados — pode preencher apenas CBO, apenas descritivo, ou ambos',
                'Funções não encontradas geram aviso mas não interrompem as demais',
            ] as $idx=>$txt)
            <div class="flex gap-10 align-center">
                <span style="width:22px;height:22px;border-radius:50%;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">{{ $idx+1 }}</span>
                <span>{{ $txt }}</span>
            </div>
            @endforeach
        </div>

        {{-- Gerador de modelo pré-preenchido --}}
        @php $todasEmpresas = \App\Models\Empresa::ativas()->orderBy('razao_social')->get(); @endphp
        <div style="background:rgba(var(--brand-rgb),.06);border:1.5px solid rgba(var(--brand-rgb),.2);border-radius:var(--r);padding:16px">
            <div style="font-size:13px;font-weight:700;color:var(--brand);margin-bottom:12px">
                <i class="fas fa-download"></i> Baixar modelo já preenchido com as funções cadastradas
            </div>
            <form method="GET" action="{{ route('importacao.modelo-funcoes') }}" id="formModeloFuncoes">
                <div class="flex flex-col gap-10">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Empresa *</label>
                        <select name="empresa_id" id="modeloEmpresa" class="form-select" required onchange="carregarSetoresModelo(this.value)">
                            <option value="">Selecione a empresa</option>
                            @foreach($todasEmpresas as $e)
                            <option value="{{ $e->id }}">{{ $e->nome_display }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">
                            Setores
                            <span style="font-weight:400;color:var(--text-3)">(deixe em branco para todos)</span>
                        </label>
                        <div id="setoresModeloWrap" style="border:1.5px solid var(--border);border-radius:var(--r-sm);min-height:44px;padding:8px;display:flex;flex-wrap:wrap;gap:6px;background:var(--bg-card)">
                            <span id="setoresModeloPlaceholder" style="color:var(--text-muted);font-size:12px;align-self:center">Selecione a empresa primeiro</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btnBaixarModelo" disabled>
                        <i class="fas fa-file-csv"></i> Baixar modelo preenchido
                    </button>
                </div>
            </form>
        </div>

        {{-- Colunas --}}
        <div style="margin-top:14px;background:var(--bg-secondary);border-radius:var(--r-sm);padding:12px;font-size:11px;font-family:monospace;color:var(--text-2)">
            <div style="font-weight:700;margin-bottom:6px;color:var(--text-1)">Colunas do arquivo gerado:</div>
            <div>nome_funcao | cnpj_empresa | nome_setor | <span style="color:var(--brand);font-weight:700">cbo</span> | <span style="color:var(--brand);font-weight:700">descricao</span></div>
            <div style="margin-top:6px;color:var(--text-3)">↑ as 3 primeiras já vêm preenchidas — você só edita cbo e descricao</div>
        </div>
    </div>
</div>
</div>

{{-- ── ABA EPIs ──────────────────────────────────────────────────── --}}
<div id="aba-epis" class="aba-conteudo" style="display:none">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-file-import"></i> Importar EPIs</div></div>
        <form method="POST" action="{{ route('importacao.epis') }}" enctype="multipart/form-data">@csrf
            <div class="dropzone" id="dropzone-epi">
                <input type="file" name="arquivo" id="fileEpi" accept=".csv,.txt" required>
                <div class="drop-icon"><i class="fas fa-hard-hat"></i></div>
                <h3>Arraste o arquivo aqui</h3>
                <p>ou clique para selecionar (CSV)</p>
                <div class="drop-filename" id="fn-epi"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-full mt-16"><i class="fas fa-upload"></i> Importar EPIs</button>
        </form>
    </div>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <div class="card-title"><i class="fas fa-info-circle"></i> Instruções</div>
            <a href="{{ route('importacao.modelo','epis') }}" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Baixar modelo</a>
        </div>
        <div style="background:var(--bg-secondary);border-radius:var(--r-sm);padding:12px;font-size:11px;font-family:monospace;color:var(--text-2)">
            <div style="font-weight:700;margin-bottom:6px;color:var(--text-1)">Colunas (CSV com ;):</div>
            <div><span style="color:var(--danger)">nome *</span> | <span style="color:var(--danger)">tipo *</span></div>
            <div>numero_ca | validade_ca | fornecedor</div>
            <div>fabricante | vida_util_dias</div>
            <div>estoque_minimo | custo_unitario</div>
        </div>
    </div>
</div>
</div>

{{-- ── ABA EXAMES (ASO) ──────────────────────────────────────────── --}}
<div id="aba-asos" class="aba-conteudo" style="display:none">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-file-import"></i> Importar Exames (ASO)</div></div>
        <form method="POST" action="{{ route('importacao.asos') }}" enctype="multipart/form-data">@csrf
            <div class="dropzone" id="dropzone-aso">
                <input type="file" name="arquivo" id="fileAso" accept=".csv,.txt,.xlsx,.xls" required>
                <div class="drop-icon"><i class="fas fa-clipboard-list"></i></div>
                <h3>Arraste o arquivo aqui</h3>
                <p>ou clique para selecionar</p>
                <p style="margin-top:6px;font-size:11px;color:var(--text-3)">CSV (separador ;) ou XLSX · máx. 10MB</p>
                <div class="drop-filename" id="fn-aso"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-full mt-16"><i class="fas fa-upload"></i> Importar ASOs</button>
        </form>
    </div>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <div class="card-title"><i class="fas fa-info-circle"></i> Instruções</div>
            <a href="{{ route('importacao.modelo','asos') }}" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Baixar modelo</a>
        </div>
        <div class="flex flex-col gap-10 text-13 text-muted mb-16">
            @foreach([
                'O colaborador deve estar cadastrado — identificado pelo CPF',
                'A clínica é buscada por nome (parcial) — deixe em branco se não tiver',
                'Se data_vencimento não for informada, é calculada pela periodicidade da função',
                'CPF inválido ou não encontrado gera erro mas não interrompe os demais',
                'Datas: AAAA-MM-DD ou DD/MM/AAAA',
            ] as $idx => $txt)
            <div class="flex gap-10 align-center">
                <span style="width:22px;height:22px;border-radius:50%;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">{{ $idx+1 }}</span>
                <span>{{ $txt }}</span>
            </div>
            @endforeach
        </div>
        <div style="background:var(--bg-secondary);border-radius:var(--r-sm);padding:12px;font-size:11px;font-family:monospace;color:var(--text-2)">
            <div style="font-weight:700;margin-bottom:6px;color:var(--text-1)">Colunas (CSV com ;):</div>
            <div><span style="color:var(--danger)">cpf *</span> | <span style="color:var(--danger)">data_exame *</span> | <span style="color:var(--danger)">tipo *</span></div>
            <div>clinica | resultado | data_vencimento</div>
            <div style="margin-top:8px;font-weight:700;color:var(--text-1)">Tipos válidos:</div>
            <div>admissional · periodico · demissional</div>
            <div>retorno_trabalho · mudanca_funcao</div>
            <div style="margin-top:8px;font-weight:700;color:var(--text-1)">Resultados válidos:</div>
            <div>apto · inapto · apto_restricoes · pendente</div>
        </div>
    </div>
</div>
</div>

@endif
@endsection

@push('styles')
<style>
.aba {
    padding: 10px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-3);
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: color .15s, border-color .15s;
    display: flex;
    align-items: center;
    gap: 7px;
}
.aba:hover { color: var(--text-1); }
.aba-ativa { color: var(--brand); border-bottom-color: var(--brand); }
.aba-conteudo { animation: fadeIn .15s ease; }
@keyframes fadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }
</style>
@endpush

@push('scripts')
<script>
function trocarAba(nome) {
    document.querySelectorAll('.aba').forEach(b => b.classList.remove('aba-ativa'));
    document.querySelectorAll('.aba-conteudo').forEach(c => c.style.display = 'none');
    event.currentTarget.classList.add('aba-ativa');
    document.getElementById('aba-' + nome).style.display = '';
}

function toggleOk() {
    const list = document.getElementById('okList');
    const chev = document.getElementById('okChevron');
    list.style.display = list.style.display === 'none' ? 'block' : 'none';
    chev.style.transform = list.style.display === 'none' ? '' : 'rotate(180deg)';
}

// Mostra nome do arquivo selecionado
['col','func','epi','aso'].forEach(k => {
    const fi = document.getElementById('file' + k.charAt(0).toUpperCase() + k.slice(1));
    if (fi) fi.addEventListener('change', function() {
        const el = document.getElementById('fn-' + k);
        if (el && this.files[0]) el.textContent = this.files[0].name;
    });
});

// ── Gerador de modelo pré-preenchido: carrega setores da empresa ──
async function carregarSetoresModelo(empresaId) {
    const wrap = document.getElementById('setoresModeloWrap');
    const placeholder = document.getElementById('setoresModeloPlaceholder');
    const btn = document.getElementById('btnBaixarModelo');

    wrap.innerHTML = '';
    btn.disabled = true;

    if (!empresaId) {
        wrap.innerHTML = '<span id="setoresModeloPlaceholder" style="color:var(--text-muted);font-size:12px;align-self:center">Selecione a empresa primeiro</span>';
        return;
    }

    wrap.innerHTML = '<span style="color:var(--text-muted);font-size:12px;align-self:center">Carregando setores...</span>';

    try {
        const r    = await fetch(`/api/setores?empresa_id=${empresaId}`, { headers: {'X-Requested-With':'XMLHttpRequest'} });
        const data = await r.json();

        wrap.innerHTML = '';

        if (!data.length) {
            wrap.innerHTML = '<span style="color:var(--text-muted);font-size:12px">Nenhum setor cadastrado para esta empresa.</span>';
            btn.disabled = false;
            return;
        }

        // Checkbox para cada setor
        data.forEach(s => {
            const label = document.createElement('label');
            label.style.cssText = 'display:flex;align-items:center;gap:6px;padding:5px 10px;background:var(--bg-secondary);border:1.5px solid var(--border);border-radius:20px;cursor:pointer;font-size:12px;font-weight:500;user-select:none;transition:all .15s';
            label.innerHTML = `<input type="checkbox" name="setor_ids[]" value="${s.id}" style="cursor:pointer;accent-color:var(--brand)"> ${s.nome}`;
            label.querySelector('input').addEventListener('change', function() {
                label.style.background   = this.checked ? 'rgba(var(--brand-rgb),.1)' : 'var(--bg-secondary)';
                label.style.borderColor  = this.checked ? 'var(--brand)'  : 'var(--border)';
                label.style.color        = this.checked ? 'var(--brand)'  : '';
                label.style.fontWeight   = this.checked ? '700'           : '500';
            });
            wrap.appendChild(label);
        });

        btn.disabled = false;
    } catch(e) {
        wrap.innerHTML = '<span style="color:var(--danger);font-size:12px">Erro ao carregar setores.</span>';
    }
}

// Abre a aba correta após retorno com resultado
@if(session('importResult') && (session('importResult.tipo') ?? '') === 'funcoes')
// resultado de funções — não precisa abrir aba pois o formulário some
@endif
</script>
@endpush
