<?php
namespace App\Console\Commands;

use App\Services\CaEpiService;
use Illuminate\Console\Command;

class AtualizarCaEpi extends Command
{
    protected $signature   = 'caepi:atualizar';
    protected $description = 'Atualiza os dados dos CAs de todos os EPIs via API CAEPI (roda diariamente às 21h)';

    public function handle(CaEpiService $service): int
    {
        $this->info('Iniciando atualização dos CAs...');

        $atualizados = $service->atualizarEpis();

        $this->info("EPIs atualizados: {$atualizados}");

        return Command::SUCCESS;
    }
}
