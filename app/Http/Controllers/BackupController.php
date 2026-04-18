<?php
namespace App\Http\Controllers;

use App\Models\{BackupConfig, BackupLog};
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index(BackupService $service)
    {
        $config  = BackupConfig::get();
        $logs    = BackupLog::orderByDesc('created_at')->paginate(20);
        $autorizado = $service->driveAutorizado();
        return view('config.backup', compact('config', 'logs', 'autorizado'));
    }

    public function salvarConfig(Request $r)
    {
        $r->validate([
            'horario'               => 'required|regex:/^\d{2}:\d{2}$/',
            'retencao'              => 'required|integer|min:1|max:365',
            'pg_dump_path'          => 'nullable|string|max:500',
            'google_drive_pasta_id' => 'nullable|string|max:255',
            'google_client_id'      => 'nullable|string|max:255',
            'google_client_secret'  => 'nullable|string|max:255',
        ]);

        $config = BackupConfig::get();
        $config->update([
            'ativo'                  => $r->boolean('ativo'),
            'horario'                => $r->horario,
            'retencao'               => $r->retencao,
            'pg_dump_path'           => $r->pg_dump_path ?: 'pg_dump',
            'google_drive_ativo'     => $r->boolean('google_drive_ativo'),
            'google_drive_pasta_id'  => $r->google_drive_pasta_id,
            'google_client_id'       => $r->google_client_id,
            'google_client_secret'   => $r->google_client_secret,
        ]);

        // Se mudou client_id ou secret, invalida o refresh token anterior
        if ($r->filled('google_client_id') || $r->filled('google_client_secret')) {
            $config->update(['google_refresh_token' => null]);
        }

        return back()->with('success', 'Configurações salvas! Agora clique em "Autorizar Google Drive".');
    }

    public function executarManual(BackupService $service)
    {
        $resultado = $service->executar('manual');

        if (!$resultado['sucesso']) {
            return back()->with('error', 'Falha no backup: ' . $resultado['mensagem']);
        }

        $kb  = round(($resultado['tamanho'] ?? 0) / 1024, 1);
        $msg = "Backup realizado: {$resultado['arquivo']} ({$kb} KB)";

        if ($resultado['drive_ok']) {
            $msg .= ' · enviado ao Google Drive ✔';
        } elseif ($resultado['drive_erro']) {
            $msg .= ' · Drive falhou: ' . $resultado['drive_erro'];
        }

        return back()->with($resultado['drive_ok'] || !$this->cfg($service)->google_drive_ativo ? 'success' : 'warning', $msg);
    }

    // ── OAuth Google Drive ────────────────────────────────────────────────────

    public function oauthRedirecionar(BackupService $service)
    {
        $config = BackupConfig::get();

        if (!$config->google_client_id || !$config->google_client_secret) {
            return back()->with('error', 'Informe o Client ID e Client Secret antes de autorizar.');
        }

        return redirect($service->urlAutorizacao());
    }

    public function oauthCallback(Request $r, BackupService $service)
    {
        if ($r->has('error')) {
            return redirect()->route('backup.index')
                ->with('error', 'Autorização negada: ' . $r->get('error'));
        }

        [$ok, $erro] = $service->trocarCodigoPorToken($r->get('code'));

        if (!$ok) {
            return redirect()->route('backup.index')
                ->with('error', 'Falha ao obter token: ' . $erro);
        }

        return redirect()->route('backup.index')
            ->with('success', 'Google Drive autorizado com sucesso! ✔ Os backups serão enviados automaticamente.');
    }

    public function revogarOauth()
    {
        BackupConfig::get()->update(['google_refresh_token' => null]);
        return back()->with('success', 'Autorização do Google Drive removida.');
    }

    public function download(BackupLog $log)
    {
        $caminho = storage_path('app/backups/' . $log->nome_arquivo);
        if (!file_exists($caminho)) {
            return back()->with('error', 'Arquivo não encontrado no servidor.');
        }
        return response()->download($caminho);
    }

    public function destroy(BackupLog $log)
    {
        $caminho = storage_path('app/backups/' . $log->nome_arquivo);
        if (file_exists($caminho)) @unlink($caminho);
        $log->delete();
        return back()->with('success', 'Backup removido.');
    }

    private function cfg(BackupService $service): BackupConfig
    {
        return BackupConfig::get();
    }
}
