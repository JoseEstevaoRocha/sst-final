<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{ASO, Colaborador, Alerta};
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessarAgendamentos extends Command
{
    protected $signature   = 'sst:processar-agendamentos {--data= : Data específica Y-m-d, padrão hoje}';
    protected $description = 'Processa agendamentos do dia: atualiza colaboradores, cria alertas PPP e marca como realizado.';

    public function handle(): int
    {
        $data = $this->option('data')
            ? Carbon::parse($this->option('data'))->toDateString()
            : today()->toDateString();

        $this->info("Processando agendamentos para {$data}...");

        $agendamentos = ASO::with(['colaborador', 'empresa'])
            ->whereDate('data_agendada', $data)
            ->where('status_logistico', 'agendado')
            ->get();

        if ($agendamentos->isEmpty()) {
            $this->info('Nenhum agendamento para processar.');
            return self::SUCCESS;
        }

        $processados = 0;

        foreach ($agendamentos as $aso) {
            try {
                $this->processarUm($aso);
                $processados++;
            } catch (\Throwable $e) {
                $this->error("Erro no ASO #{$aso->id}: {$e->getMessage()}");
                Log::error("ProcessarAgendamentos: ASO #{$aso->id}", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Concluído: {$processados} agendamento(s) processado(s).");
        return self::SUCCESS;
    }

    private function processarUm(ASO $aso): void
    {
        $colab = $aso->colaborador;
        if (!$colab) return;

        // ── Demissional ──────────────────────────────────────────────────────
        if ($aso->tipo === 'demissional') {
            $colab->update(['status' => 'Demitido', 'data_demissao' => $aso->data_agendada]);

            $jaExiste = Alerta::where('colaborador_id', $colab->id)
                ->where('tipo', 'ppp')->where('status', 'pendente')->exists();

            if (!$jaExiste) {
                Alerta::create([
                    'empresa_id'    => $aso->empresa_id,
                    'colaborador_id'=> $colab->id,
                    'tipo'          => 'ppp',
                    'titulo'        => 'Gerar PPP',
                    'descricao'     => "Colaborador: {$colab->nome} | CPF: {$colab->cpf}",
                    'status'        => 'pendente',
                    'data_prevista' => $aso->data_agendada,
                ]);
            }

            $this->line("  [Demissional] {$colab->nome} → Demitido + Alerta PPP.");
        }

        // ── Mudança de Função ────────────────────────────────────────────────
        if ($aso->tipo === 'mudanca_funcao') {
            $updates = array_filter([
                'setor_id'  => $aso->novo_setor_id,
                'funcao_id' => $aso->nova_funcao_id,
            ]);
            if ($updates) {
                $colab->update($updates);
                $this->line("  [Mud.Função] {$colab->nome} → Setor/Função atualizados.");
            }
        }

        // ── Marca como realizado ─────────────────────────────────────────────
        $aso->update(['status_logistico' => 'realizado']);
    }
}
