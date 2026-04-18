@extends('layouts.app')
@section('title','Backup do Banco de Dados')
@section('content')

<div class="page-header">
    <div><h1 class="page-title"><i class="fas fa-database"></i> Backup do Banco de Dados</h1></div>
    <div>
        <form method="POST" action="{{ route('backup.executar') }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-primary" onclick="return confirm('Executar backup agora?')">
                <i class="fas fa-play"></i> Fazer Backup Agora
            </button>
        </form>
    </div>
</div>

@foreach(['success','error','warning'] as $tipo)
    @if(session($tipo))
        <div class="alert alert-{{ $tipo === 'warning' ? 'warning' : ($tipo === 'error' ? 'danger' : 'success') }} mb-16">
            <i class="fas fa-{{ $tipo === 'success' ? 'check-circle' : ($tipo === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') }}"></i>
            {{ session($tipo) }}
        </div>
    @endif
@endforeach

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    {{-- ── Configurações Gerais ── --}}
    <div class="flex flex-col gap-16">
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="fas fa-cog"></i> Configurações Gerais</div></div>
            <form method="POST" action="{{ route('backup.config') }}">
                @csrf @method('PUT')
                <div class="flex flex-col gap-14">

                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="ativo" value="1" {{ $config->ativo ? 'checked' : '' }}>
                            Backup automático ativo
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Horário do backup diário</label>
                        <input type="time" name="horario" value="{{ $config->horario }}" class="form-control" style="max-width:120px">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Retenção (backups a manter)</label>
                        <select name="retencao" class="form-control" style="max-width:160px">
                            @foreach([7,15,30,60,90] as $v)
                                <option value="{{ $v }}" {{ $config->retencao == $v ? 'selected' : '' }}>{{ $v }} backups</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Caminho do pg_dump</label>
                        <input type="text" name="pg_dump_path" value="{{ $config->pg_dump_path }}" class="form-control" placeholder="pg_dump">
                        <div class="text-11 text-muted mt-4">Ex: C:/Program Files/PostgreSQL/16/bin/pg_dump.exe</div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>

        {{-- ── Google Drive ── --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fab fa-google-drive"></i> Google Drive</div>
                @if($autorizado)
                    <span class="badge badge-success"><i class="fas fa-check"></i> Autorizado</span>
                @else
                    <span class="badge badge-secondary">Não autorizado</span>
                @endif
            </div>
            <form method="POST" action="{{ route('backup.config') }}">
                @csrf @method('PUT')
                {{-- Passa os campos gerais como hidden para não perder --}}
                <input type="hidden" name="horario" value="{{ $config->horario }}">
                <input type="hidden" name="retencao" value="{{ $config->retencao }}">
                <input type="hidden" name="pg_dump_path" value="{{ $config->pg_dump_path }}">
                @if($config->ativo)<input type="hidden" name="ativo" value="1">@endif

                <div class="flex flex-col gap-14">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="google_drive_ativo" value="1" {{ $config->google_drive_ativo ? 'checked' : '' }}>
                            Enviar backup para Google Drive
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ID da pasta no Google Drive</label>
                        <input type="text" name="google_drive_pasta_id" value="{{ $config->google_drive_pasta_id }}" class="form-control" placeholder="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgV...">
                        <div class="text-11 text-muted mt-4">URL da pasta: drive.google.com/drive/folders/<strong>ESTE_ID</strong></div>
                    </div>

                    <hr style="border-color:var(--border)">
                    <div class="text-13 font-500 text-muted">Credenciais OAuth 2.0 (conta pessoal Google)</div>

                    <div style="padding:10px;background:#fff3cd;border-radius:6px;font-size:12px;line-height:1.5">
                        <strong>Como configurar:</strong><br>
                        1. <a href="https://console.cloud.google.com" target="_blank">console.cloud.google.com</a> → Criar projeto<br>
                        2. APIs → Ativar <strong>Google Drive API</strong><br>
                        3. Credenciais → <strong>Criar ID do cliente OAuth</strong> → Tipo: <strong>Aplicativo da Web</strong><br>
                        4. Em "URIs de redirecionamento" adicionar: <br>
                        <code style="font-size:11px;word-break:break-all">{{ route('backup.oauth-callback') }}</code><br>
                        5. Copiar <strong>Client ID</strong> e <strong>Client Secret</strong> abaixo
                    </div>

                    <div class="form-group">
                        <label class="form-label">Client ID</label>
                        <input type="text" name="google_client_id" value="{{ $config->google_client_id }}" class="form-control" placeholder="XXXXXXXXX.apps.googleusercontent.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Client Secret</label>
                        <input type="password" name="google_client_secret" value="{{ $config->google_client_secret }}" class="form-control" placeholder="GOCSPX-...">
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar credenciais</button>

                    @if($config->google_client_id && $config->google_client_secret)
                        <hr style="border-color:var(--border)">
                        @if($autorizado)
                            <div class="flex gap-8 items-center">
                                <div class="badge badge-success" style="font-size:13px;padding:8px 12px">
                                    <i class="fas fa-check-circle"></i> Google Drive autorizado com sucesso
                                </div>
                                <form method="POST" action="{{ route('backup.oauth-revogar') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-secondary" onclick="return confirm('Remover autorização?')">
                                        <i class="fas fa-unlink"></i> Revogar
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('backup.oauth-autorizar') }}" class="btn btn-success">
                                <i class="fab fa-google"></i> Autorizar Google Drive
                            </a>
                            <div class="text-11 text-muted">Você será redirecionado para o Google para autorizar o acesso.</div>
                        @endif
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ── Info & Histórico ── --}}
    <div class="flex flex-col gap-16">
        <div class="card">
            <div class="card-header"><div class="card-title"><i class="fas fa-info-circle"></i> Status</div></div>
            <div class="flex flex-col gap-10 text-13">
                <div><span class="text-muted">Banco:</span> <strong>{{ config('database.connections.pgsql.database') }}</strong></div>
                <div><span class="text-muted">Backup automático:</span>
                    @if($config->ativo)
                        <span class="badge badge-success">Ativo — {{ $config->horario }} diário</span>
                    @else
                        <span class="badge badge-secondary">Desativado</span>
                    @endif
                </div>
                <div><span class="text-muted">Google Drive:</span>
                    @if($config->google_drive_ativo && $autorizado)
                        <span class="badge badge-success">Ativo e autorizado</span>
                    @elseif($config->google_drive_ativo && !$autorizado)
                        <span class="badge badge-warning">Ativo — pendente autorização</span>
                    @else
                        <span class="badge badge-secondary">Desativado</span>
                    @endif
                </div>
                <div><span class="text-muted">Retenção:</span> <strong>{{ $config->retencao }} backups</strong></div>
                @php $ultimo = \App\Models\BackupLog::where('status','sucesso')->latest()->first(); @endphp
                <div><span class="text-muted">Último backup:</span>
                    <strong>{{ $ultimo ? $ultimo->created_at->format('d/m/Y H:i') : 'Nunca' }}</strong>
                </div>
                @if($ultimo)
                    <div><span class="text-muted">Tamanho:</span> <strong>{{ $ultimo->tamanho_formatado }}</strong></div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> Histórico de Backups</div></div>
            @if($logs->isEmpty())
                <div class="empty-state text-center py-24 text-muted">Nenhum backup realizado ainda.</div>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Tipo</th>
                            <th>Tamanho</th>
                            <th>Status</th>
                            <th>Drive</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr>
                            <td class="text-13">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="badge {{ $log->tipo === 'automatico' ? 'badge-info' : 'badge-secondary' }}">{{ ucfirst($log->tipo) }}</span></td>
                            <td class="text-13">{{ $log->tamanho_formatado }}</td>
                            <td>
                                @if($log->status === 'sucesso')
                                    <span class="badge badge-success"><i class="fas fa-check"></i> OK</span>
                                @else
                                    <span class="badge badge-danger" title="{{ $log->mensagem }}"><i class="fas fa-times"></i> Erro</span>
                                @endif
                            </td>
                            <td>
                                @if($log->google_drive_ok)
                                    <span class="badge badge-success" title="ID: {{ $log->google_drive_id }}"><i class="fab fa-google-drive"></i></span>
                                @elseif($log->mensagem && str_contains($log->mensagem, 'Drive'))
                                    <span class="badge badge-warning" title="{{ $log->mensagem }}"><i class="fab fa-google-drive"></i> !</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="flex gap-6">
                                @if($log->status === 'sucesso')
                                    <a href="{{ route('backup.download', $log) }}" class="btn btn-xs btn-secondary" title="Download"><i class="fas fa-download"></i></a>
                                @endif
                                <form method="POST" action="{{ route('backup.destroy', $log) }}" onsubmit="return confirm('Remover este backup?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-12">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
