<?php
namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RealizarBackup extends Command
{
    protected $signature   = 'backup:realizar {--tipo=automatico}';
    protected $description = 'Realiza backup do banco de dados PostgreSQL';

    public function handle(BackupService $service): int
    {
        $tipo = $this->option('tipo');
        $this->info("Iniciando backup ({$tipo})...");

        $resultado = $service->executar($tipo);

        if (!$resultado['sucesso']) {
            $this->error('Falha no backup: ' . $resultado['mensagem']);
            return Command::FAILURE;
        }

        $kb = round(($resultado['tamanho'] ?? 0) / 1024, 1);
        $this->info("✔ Backup concluído: {$resultado['arquivo']} ({$kb} KB)");

        if ($resultado['drive_ok']) {
            $this->info('✔ Upload Google Drive realizado com sucesso.');
        }

        return Command::SUCCESS;
    }
}
