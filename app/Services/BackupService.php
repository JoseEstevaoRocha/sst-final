<?php
namespace App\Services;

use App\Models\{BackupConfig, BackupLog};
use Illuminate\Support\Facades\{Log, Http};

class BackupService
{
    private BackupConfig $cfg;
    private string $backupDir;

    public function __construct()
    {
        $this->cfg       = BackupConfig::get();
        $this->backupDir = storage_path('app/backups');
    }

    public function executar(string $tipo = 'manual'): array
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $nomeArquivo  = 'sst_db_backup_' . now()->format('Y-m-d_H-i') . '.sql';
        $caminhoLocal = $this->backupDir . DIRECTORY_SEPARATOR . $nomeArquivo;

        [$ok, $erro] = $this->pgDump($caminhoLocal);

        if (!$ok) {
            $this->registrar($nomeArquivo, null, 'erro', $erro, $tipo);
            return ['sucesso' => false, 'mensagem' => $erro];
        }

        $tamanho = file_exists($caminhoLocal) ? filesize($caminhoLocal) : null;
        $driveId = null;
        $driveOk = false;
        $driveErroMsg = null;

        if ($this->cfg->google_drive_ativo) {
            [$driveOk, $driveId, $driveErroMsg] = $this->uploadDrive($caminhoLocal, $nomeArquivo);
            if (!$driveOk) {
                Log::warning("Backup: falha no upload Drive — {$driveErroMsg}");
            }
        }

        $this->registrar($nomeArquivo, $tamanho, 'sucesso', $driveErroMsg, $tipo, $driveId, $driveOk);
        $this->aplicarRetencao();

        return [
            'sucesso'        => true,
            'arquivo'        => $nomeArquivo,
            'tamanho'        => $tamanho,
            'drive_ok'       => $driveOk,
            'drive_erro'     => $driveErroMsg,
        ];
    }

    // ── pg_dump ───────────────────────────────────────────────────────────────

    private function pgDump(string $destino): array
    {
        $db  = config('database.connections.pgsql');
        $bin = $this->cfg->pg_dump_path ?: 'pg_dump';

        if ($bin === 'pg_dump' && PHP_OS_FAMILY === 'Windows') {
            foreach (glob('C:/Program Files/PostgreSQL/*/bin/pg_dump.exe') as $p) {
                $bin = $p; break;
            }
        }

        $cmd = sprintf(
            '"%s" -h %s -p %s -U %s -d %s --no-password --exclude-table-data=ca_cache -f "%s" 2>&1',
            $bin, $db['host'], $db['port'], $db['username'], $db['database'], $destino
        );

        $envBase = array_filter(array_merge(getenv() ?: [], $_ENV ?? []), 'is_string');
        $env     = array_merge($envBase, ['PGPASSWORD' => (string) $db['password']]);

        $proc = proc_open($cmd, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, null, $env);

        if (!is_resource($proc)) {
            return [false, 'Não foi possível iniciar pg_dump. Verifique o caminho configurado.'];
        }

        fclose($pipes[0]);
        $saida = stream_get_contents($pipes[1]);
        $erros = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        if ($code !== 0) {
            return [false, trim($erros ?: $saida ?: "pg_dump retornou código {$code}")];
        }

        return [true, null];
    }

    // ── Google Drive (OAuth 2.0 — conta pessoal Google) ───────────────────────

    private function uploadDrive(string $arquivo, string $nome): array
    {
        try {
            $token = $this->obterAccessToken();
            if (!$token) {
                return [false, null, 'Sem token de acesso. Configure o OAuth do Google Drive nas configurações.'];
            }

            $pastaId  = $this->cfg->google_drive_pasta_id;
            $metadata = ['name' => $nome];
            if ($pastaId) $metadata['parents'] = [$pastaId];

            $conteudo = file_get_contents($arquivo);
            $boundary = 'sst_backup_' . uniqid();

            $body = "--{$boundary}\r\n"
                  . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
                  . json_encode($metadata) . "\r\n"
                  . "--{$boundary}\r\n"
                  . "Content-Type: application/octet-stream\r\n\r\n"
                  . $conteudo . "\r\n"
                  . "--{$boundary}--";

            $resposta = Http::withoutVerifying()
                ->withToken($token)
                ->withBody($body, "multipart/related; boundary={$boundary}")
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

            if ($resposta->successful()) {
                return [true, $resposta->json('id'), null];
            }

            $msg = $resposta->json('error.message') ?? $resposta->body();
            return [false, null, 'Drive API: ' . $msg];

        } catch (\Throwable $e) {
            return [false, null, $e->getMessage()];
        }
    }

    /**
     * Obtém access token via OAuth 2.0 (refresh token — conta pessoal Google).
     */
    private function obterAccessToken(): ?string
    {
        $clientId     = $this->cfg->google_client_id;
        $clientSecret = $this->cfg->google_client_secret;
        $refreshToken = $this->cfg->google_refresh_token;

        if (!$clientId || !$clientSecret || !$refreshToken) {
            return null;
        }

        $resposta = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        return $resposta->json('access_token');
    }

    /**
     * Gera a URL de autorização OAuth para o usuário clicar e autorizar.
     */
    public function urlAutorizacao(): string
    {
        $params = http_build_query([
            'client_id'     => $this->cfg->google_client_id,
            'redirect_uri'  => route('backup.oauth-callback'),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/drive.file',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);

        return 'https://accounts.google.com/o/oauth2/auth?' . $params;
    }

    /**
     * Troca o código de autorização pelo refresh token e salva.
     */
    public function trocarCodigoPorToken(string $code): array
    {
        $resposta = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => $this->cfg->google_client_id,
            'client_secret' => $this->cfg->google_client_secret,
            'redirect_uri'  => route('backup.oauth-callback'),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ]);

        if (!$resposta->successful() || !$resposta->json('refresh_token')) {
            $msg = $resposta->json('error_description') ?? $resposta->json('error') ?? 'Resposta inválida do Google';
            return [false, $msg];
        }

        $this->cfg->update(['google_refresh_token' => $resposta->json('refresh_token')]);

        return [true, null];
    }

    public function driveAutorizado(): bool
    {
        return !empty($this->cfg->google_refresh_token);
    }

    // ── Retenção ──────────────────────────────────────────────────────────────

    private function aplicarRetencao(): void
    {
        $retencao = max(1, $this->cfg->retencao);

        BackupLog::where('status', 'sucesso')
            ->orderByDesc('created_at')
            ->skip($retencao)->take(9999)->get()
            ->each(function ($log) {
                $caminho = $this->backupDir . DIRECTORY_SEPARATOR . $log->nome_arquivo;
                if (file_exists($caminho)) @unlink($caminho);
                $log->delete();
            });
    }

    // ── Log ───────────────────────────────────────────────────────────────────

    private function registrar(
        string $nome, ?int $tamanho, string $status, ?string $mensagem,
        string $tipo, ?string $driveId = null, bool $driveOk = false
    ): void {
        BackupLog::create([
            'nome_arquivo'    => $nome,
            'tamanho_bytes'   => $tamanho,
            'status'          => $status,
            'mensagem'        => $mensagem,
            'tipo'            => $tipo,
            'google_drive_id' => $driveId,
            'google_drive_ok' => $driveOk,
        ]);
        Log::info("Backup [{$tipo}] [{$status}]: {$nome}" . ($mensagem ? " — {$mensagem}" : ''));
    }

    public function listarArquivos(): array
    {
        if (!is_dir($this->backupDir)) return [];
        return glob($this->backupDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    }
}
