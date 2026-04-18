@extends('layouts.app')
@section('title', $clinica ? 'Editar Clínica' : 'Nova Clínica')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $clinica ? 'Editar Clínica' : 'Nova Clínica' }}</h1>
        <p class="page-sub">{{ $clinica ? $clinica->nome : 'Preencha os dados da clínica parceira' }}</p>
    </div>
    <a href="{{ route('clinicas.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

{{-- FORMULÁRIO --}}
<div class="card">
    <form method="POST" action="{{ $clinica ? route('clinicas.update', $clinica->id) : route('clinicas.store') }}">
        @csrf
        @if($clinica) @method('PUT') @endif

        <div class="form-grid">
            <div class="form-group form-full">
                <label class="form-label">Nome da Clínica *</label>
                <input type="text" name="nome" class="form-control" value="{{ old('nome', $clinica->nome ?? '') }}" required placeholder="Ex: Clínica Saúde Ocupacional SP">
            </div>

            {{-- WhatsApp destacado --}}
            <div class="form-group form-full">
                <label class="form-label" style="color:var(--success)"><i class="fab fa-whatsapp"></i> WhatsApp para Agendamento *</label>
                <div style="position:relative">
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--success);font-weight:700;font-size:14px">+55</span>
                    <input type="text" name="whatsapp" id="wppInput" class="form-control" style="padding-left:44px;border-color:var(--success)"
                        value="{{ old('whatsapp', $clinica->whatsapp ?? '') }}"
                        placeholder="(11) 99999-0000" required oninput="atualizarPreviewWpp(this.value)">
                </div>
                <p class="text-11 text-muted" style="margin-top:4px"><i class="fas fa-info-circle"></i> Este número será usado para enviar as solicitações de agendamento pelo WhatsApp.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Telefone Fixo</label>
                <input type="text" name="telefone" class="form-control" value="{{ old('telefone', $clinica->telefone ?? '') }}" placeholder="(11) 3333-0000">
            </div>

            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $clinica->email ?? '') }}" placeholder="contato@clinica.com.br">
            </div>

            <div class="form-group">
                <label class="form-label">Responsável / Médico</label>
                <input type="text" name="responsavel" class="form-control" value="{{ old('responsavel', $clinica->responsavel ?? '') }}" placeholder="Dr. Nome Sobrenome">
            </div>

            <div class="form-group">
                <label class="form-label">CNPJ</label>
                <input type="text" name="cnpj" class="form-control font-mono" value="{{ old('cnpj', $clinica->cnpj ?? '') }}" placeholder="00.000.000/0000-00" maxlength="18">
            </div>

            <div class="form-group">
                <label class="form-label">Cidade</label>
                <input type="text" name="cidade" class="form-control" value="{{ old('cidade', $clinica->cidade ?? '') }}" placeholder="São Paulo">
            </div>

            <div class="form-group">
                <label class="form-label">Estado</label>
                <input type="text" name="estado" class="form-control" value="{{ old('estado', $clinica->estado ?? '') }}" maxlength="2" placeholder="SP" style="text-transform:uppercase">
            </div>

            <div class="form-group form-full">
                <label class="form-label">Endereço</label>
                <input type="text" name="endereco" class="form-control" value="{{ old('endereco', $clinica->endereco ?? '') }}" placeholder="Rua, número, bairro">
            </div>
        </div>

        <div class="form-footer">
            <a href="{{ route('clinicas.index') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
        </div>
    </form>
</div>

{{-- PAINEL LATERAL --}}
<div style="display:flex;flex-direction:column;gap:16px">

    {{-- Preview WhatsApp --}}
    <div class="card" style="border-left:3px solid #25d366">
        <div class="card-header">
            <div class="card-title" style="color:#25d366"><i class="fab fa-whatsapp"></i> Preview do Contato</div>
        </div>
        <div style="padding:0 16px 16px">
            <div id="wppPreview" style="background:var(--bg-alt);border-radius:8px;padding:14px;text-align:center">
                <div style="font-size:32px;margin-bottom:8px">💬</div>
                <div style="font-size:12px;color:var(--text-3);margin-bottom:12px">Digite o número para ver o link</div>
                <a id="wppLink" href="#" target="_blank" class="btn btn-success btn-sm" style="display:none;background:#25d366;border-color:#25d366">
                    <i class="fab fa-whatsapp"></i> Testar WhatsApp
                </a>
            </div>
            <p class="text-11 text-muted" style="margin-top:8px;text-align:center">Clique para verificar se o número está correto antes de salvar.</p>
        </div>
    </div>

    {{-- Dicas --}}
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-lightbulb"></i> Dicas</div></div>
        <div style="padding:0 16px 16px;display:flex;flex-direction:column;gap:8px">
            @foreach([
                ['fas fa-mobile-alt','#25d366','Use o número do WhatsApp Business da clínica, não o fixo'],
                ['fas fa-flag-checkered','var(--brand)','O código do país (+55) é adicionado automaticamente'],
                ['fas fa-check-circle','var(--success)','Números com DDD de 2 dígitos + 9 dígitos (celular) funcionam melhor'],
                ['fas fa-bell','var(--warning)','Salve o número sem espaços ou traços — o sistema formata automaticamente'],
            ] as [$icon, $color, $txt])
            <div style="display:flex;gap:10px;align-items:flex-start">
                <i class="{{ $icon }}" style="color:{{ $color }};margin-top:2px;flex-shrink:0"></i>
                <span style="font-size:12px;color:var(--text-2)">{{ $txt }}</span>
            </div>
            @endforeach
        </div>
    </div>

    @if($clinica && $clinica->whatsapp)
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-link"></i> Ações Rápidas</div></div>
        <div style="padding:0 16px 16px;display:flex;flex-direction:column;gap:8px">
            @php $num = '55'.preg_replace('/\D/','',$clinica->whatsapp); @endphp
            <a href="https://wa.me/{{ $num }}" target="_blank" class="btn btn-success btn-sm btn-full">
                <i class="fab fa-whatsapp"></i> Abrir conversa no WhatsApp
            </a>
            <a href="{{ route('whatsapp.index') }}" class="btn btn-secondary btn-sm btn-full">
                <i class="fas fa-paper-plane"></i> Criar solicitação de agendamento
            </a>
        </div>
    </div>
    @endif
</div>

</div>

@endsection
@push('scripts')
<script>
function atualizarPreviewWpp(val) {
    const digits = val.replace(/\D/g, '');
    const link   = document.getElementById('wppLink');
    if (digits.length >= 10) {
        link.href = `https://wa.me/55${digits}`;
        link.style.display = 'inline-flex';
    } else {
        link.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    atualizarPreviewWpp(document.getElementById('wppInput')?.value || '');
});
</script>
@endpush
