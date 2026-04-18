@extends('layouts.app')
@section('title','Config WhatsApp')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">Configuração WhatsApp</h1><p class="page-sub">Modelos de mensagem para solicitação de agendamento</p></div>
    <a href="{{ route('whatsapp.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<form method="POST" action="{{ route('whatsapp.config.save') }}">@csrf

{{-- Grid principal: dois templates + painel de variáveis --}}
<div style="display:grid;grid-template-columns:1fr 1fr 340px;gap:16px;align-items:start">

    {{-- ── Template Padrão ─────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fab fa-whatsapp" style="color:#25d366"></i>
                Modelo Padrão
            </div>
            <span style="font-size:11px;color:var(--text-3)">Admissional · Periódico · Demissional · Retorno</span>
        </div>
        <div style="padding:0 20px 20px;display:flex;flex-direction:column;gap:14px">
            <p class="text-11 text-muted">Use as variáveis da lista ao lado. Clique numa variável para inserir no modelo ativo.</p>
            <div class="form-group" style="margin:0">
                <textarea name="modelo_mensagem" id="tplPadrao" class="form-control"
                    rows="14" required style="font-family:monospace;font-size:12px"
                    onfocus="setActiveTextarea(this)"
                >{{ old('modelo_mensagem', $config->modelo_mensagem ?? "*SOLICITAÇÃO DE AGENDAMENTO*\nEmpresa: {empresa}\nColaborador: {nome}\nCPF: {cpf}\nRG: {rg}\nNasc: {nasc}\nExame: {tipo}\nSetor Atual: {setor}\nFunção Atual: {funcao}\nData: {data} às {horario}") }}</textarea>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Telefone para Retorno</label>
                <input type="text" name="telefone_retorno" value="{{ old('telefone_retorno', $config->telefone_retorno ?? '') }}" class="form-control" placeholder="(11) 99999-0000">
            </div>
        </div>
    </div>

    {{-- ── Template Mudança de Função ──────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-exchange-alt" style="color:var(--brand)"></i>
                Mudança de Função
            </div>
            <span style="font-size:11px;color:var(--text-3)">Usado automaticamente neste tipo de exame</span>
        </div>
        <div style="padding:0 20px 20px;display:flex;flex-direction:column;gap:14px">
            <p class="text-11 text-muted">Inclui as variáveis <code style="font-size:11px">{novo_setor}</code> e <code style="font-size:11px">{nova_funcao}</code> exclusivas deste tipo.</p>
            <div class="form-group" style="margin:0">
                <textarea name="modelo_mudanca_funcao" id="tplMudanca" class="form-control"
                    rows="14" style="font-family:monospace;font-size:12px"
                    onfocus="setActiveTextarea(this)"
                    placeholder="Se vazio, usa o modelo padrão com os campos de mudança adicionados automaticamente."
                >{{ old('modelo_mudanca_funcao', $config->modelo_mudanca_funcao ?? "*SOLICITAÇÃO DE AGENDAMENTO — MUDANÇA DE FUNÇÃO*\nEmpresa: {empresa}\nColaborador: {nome}\nCPF: {cpf}\nNasc: {nasc}\nSetor Atual: {setor}\nFunção Atual: {funcao}\nNovo Setor: {novo_setor}\nNova Função: {nova_funcao}\nData: {data} às {horario}\nLocal: {local}") }}</textarea>
            </div>
            <div style="background:rgba(var(--brand-rgb),.06);border:1px solid rgba(var(--brand-rgb),.2);border-radius:var(--r-sm);padding:10px 12px;font-size:11px;color:var(--text-2)">
                <i class="fas fa-info-circle" style="color:var(--brand)"></i>
                Variáveis exclusivas desta template: <code>{novo_setor}</code> e <code>{nova_funcao}</code>
            </div>
        </div>
    </div>

    {{-- ── Painel lateral: variáveis + preview ─────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="fas fa-code"></i> Variáveis</div></div>
            <div style="padding:0 14px 14px;display:flex;flex-direction:column;gap:5px">
                @php
                $vars = [
                    ['{nome}',        'Nome completo (maiúsculas)'],
                    ['{empresa}',     'Razão social da empresa'],
                    ['{cpf}',         'CPF formatado'],
                    ['{rg}',          'RG do colaborador'],
                    ['{nasc}',        'Data de nascimento'],
                    ['{tipo}',        'Tipo do exame'],
                    ['{setor}',       'Setor atual'],
                    ['{funcao}',      'Função atual + CBO'],
                    ['{novo_setor}',  'Novo setor (mudança)'],
                    ['{nova_funcao}', 'Nova função (mudança)'],
                    ['{data}',        'Data agendada'],
                    ['{horario}',     'Horário agendado'],
                    ['{local}',       'Local do exame'],
                    ['{clinica}',     'Nome da clínica'],
                ];
                @endphp
                @foreach($vars as [$var, $desc])
                <div style="display:flex;align-items:flex-start;gap:8px;padding:5px 8px;background:var(--bg-alt);border-radius:5px;cursor:pointer" onclick="inserirVar('{{ $var }}')">
                    <code style="font-size:11px;font-weight:700;color:var(--brand);background:rgba(37,99,235,.1);padding:1px 6px;border-radius:3px;white-space:nowrap">{{ $var }}</code>
                    <span style="font-size:10px;color:var(--text-3);margin-top:2px">{{ $desc }}</span>
                </div>
                @endforeach
                <p class="text-11 text-muted" style="margin-top:4px"><i class="fas fa-mouse-pointer"></i> Clique para inserir no template ativo</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title"><i class="fas fa-eye"></i> Preview</div></div>
            <div style="padding:0 14px 14px">
                <div id="preview" style="background:var(--bg-alt);border-radius:6px;padding:12px;font-size:11px;white-space:pre-wrap;line-height:1.6;color:var(--text-2);min-height:60px;font-family:monospace"></div>
            </div>
        </div>

        <div class="form-footer" style="padding:0">
            <button type="submit" class="btn btn-primary w-full"><i class="fas fa-save"></i> Salvar Configurações</button>
        </div>
    </div>

</div>
</form>

@endsection
@push('scripts')
<script>
let activeTextarea = document.getElementById('tplPadrao');

function setActiveTextarea(el) {
    activeTextarea = el;
    document.querySelectorAll('.card').forEach(c => c.style.outline = '');
    el.closest('.card').style.outline = '2px solid var(--brand)';
    atualizarPreview();
}

function inserirVar(v) {
    if (!activeTextarea) return;
    const start = activeTextarea.selectionStart;
    const end   = activeTextarea.selectionEnd;
    activeTextarea.value = activeTextarea.value.substring(0, start) + v + activeTextarea.value.substring(end);
    activeTextarea.focus();
    activeTextarea.setSelectionRange(start + v.length, start + v.length);
    atualizarPreview();
}

function atualizarPreview() {
    if (!activeTextarea) return;
    const exemplo = {
        '{nome}':'WILLIANS COSTA MACHADO', '{empresa}':'CAMARO DA SERRA INDUSTRIA LTDA',
        '{cpf}':'077.299.907-41', '{rg}':'111990669', '{nasc}':'15/10/1977',
        '{tipo}':'MUDANÇA DE FUNÇÃO', '{setor}':'PRODUÇÃO',
        '{funcao}':'Operador de Máquina 7170-25',
        '{novo_setor}':'MANUTENÇÃO', '{nova_funcao}':'Técnico de Manutenção',
        '{data}':'20/04/2026', '{horario}':'08:00',
        '{local}':'Na clínica: Clínica SST', '{clinica}':'CLÍNICA SST',
    };
    let txt = activeTextarea.value;
    Object.entries(exemplo).forEach(([k,v]) => { txt = txt.replaceAll(k, v); });
    document.getElementById('preview').textContent = txt;
}

// Inicia com o template padrão ativo
document.getElementById('tplPadrao').addEventListener('input', atualizarPreview);
document.getElementById('tplMudanca').addEventListener('input', atualizarPreview);
setActiveTextarea(document.getElementById('tplPadrao'));
</script>
@endpush
