<?php
namespace App\Console\Commands;

use App\Services\CaEpiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Log};

/**
 * Baixa a base oficial do CAEPI (MTE) via FTP e importa para ca_cache.
 *
 * Fonte: ftp.mtps.gov.br — dados públicos do Ministério do Trabalho e Emprego
 * Arquivo: tgg_export_caepi.zip → tgg_export_caepi.txt (CSV pipe-delimitado, 19 colunas)
 * Atualização oficial: diariamente às 20h (horário de Brasília)
 */
class SincronizarCaepi extends Command
{
    protected $signature   = 'caepi:sincronizar {--force : Força redownload mesmo se arquivo recente}';
    protected $description = 'Baixa a base CAEPI do MTE (ftp.mtps.gov.br) e importa para o banco local';

    const FTP_HOST   = 'ftp.mtps.gov.br';
    const FTP_PATH   = 'portal/fiscalizacao/seguranca-e-saude-no-trabalho/caepi/';
    const FTP_FILE   = 'tgg_export_caepi.zip';
    const N_COLUNAS  = 19;
    const CHUNK_SIZE = 2000; // registros por batch insert

    // Mapeamento das colunas do CSV → campos da tabela ca_cache
    const COLUNAS = [
        0  => 'ca',
        1  => 'data_validade',
        2  => 'situacao',
        3  => null,              // NRProcesso — não armazenado
        4  => 'cnpj_fabricante',
        5  => 'razao_social',
        6  => 'natureza',
        7  => 'nome_equipamento',
        8  => 'descricao_equipamento',
        9  => 'marca',
        10 => 'referencia',
        11 => null,              // Cor
        12 => null,              // AprovadoParaLaudo
        13 => null,              // RestricaoLaudo
        14 => null,              // ObservacaoAnaliseLaudo
        15 => null,              // CNPJLaboratorio
        16 => null,              // RazaoSocialLaboratorio
        17 => null,              // NRLaudo
        18 => 'norma',
    ];

    public function handle(CaEpiService $caService): int
    {
        $tmpDir  = storage_path('app/caepi');
        $zipPath = "{$tmpDir}/" . self::FTP_FILE;
        $txtPath = "{$tmpDir}/tgg_export_caepi.txt";

        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

        // ── 1. Download via FTP ───────────────────────────────────────────────
        $this->info('Conectando ao FTP do MTE...');
        if (!$this->baixarFtp($zipPath)) {
            $this->error('Falha no download. Verifique a conexão com a internet.');
            return Command::FAILURE;
        }
        $this->info('Download concluído: ' . round(filesize($zipPath) / 1024 / 1024, 1) . ' MB');

        // ── 2. Extrai ZIP ─────────────────────────────────────────────────────
        $this->info('Extraindo arquivo...');
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error('Falha ao abrir o ZIP.');
            return Command::FAILURE;
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        if (!file_exists($txtPath)) {
            $this->error('Arquivo TXT não encontrado após extração.');
            return Command::FAILURE;
        }

        // ── 3. Parse e importa ────────────────────────────────────────────────
        $this->info('Importando dados para o banco...');
        [$importados, $erros] = $this->importarCsv($txtPath);

        // ── 4. Atualiza EPIs ──────────────────────────────────────────────────
        $this->info('Atualizando situação dos EPIs...');
        $caService->atualizarEpis();

        // ── 5. Limpeza ────────────────────────────────────────────────────────
        @unlink($zipPath);
        @unlink($txtPath);

        $this->info("✔ Sincronização concluída!");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Registros importados', number_format($importados, 0, ',', '.')],
                ['Linhas com erro',      $erros],
                ['Atualizado em',        now()->format('d/m/Y H:i')],
            ]
        );

        return Command::SUCCESS;
    }

    // ── Download via cURL (FTP URL) ───────────────────────────────────────────

    private function baixarFtp(string $destino): bool
    {
        $url = 'ftp://anonymous:caepi%40sst@' . self::FTP_HOST . '/' . self::FTP_PATH . self::FTP_FILE;

        $fp = @fopen($destino, 'wb');
        if (!$fp) {
            Log::error('CAEPI: não foi possível criar arquivo de destino: ' . $destino);
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_FTP_USE_EPSV   => false,  // compatibilidade com NAT/firewall
            CURLOPT_TRANSFERTEXT   => false,   // modo binário
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'SST-Manager/1.0',
        ]);

        $ok  = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $err) {
            Log::error('CAEPI: falha no download cURL — ' . $err);
            @unlink($destino);
            return false;
        }

        if (!file_exists($destino) || filesize($destino) < 1024) {
            Log::error('CAEPI: arquivo baixado vazio ou inválido');
            @unlink($destino);
            return false;
        }

        return true;
    }

    // ── CSV ───────────────────────────────────────────────────────────────────

    private function importarCsv(string $arquivo): array
    {
        $handle     = fopen($arquivo, 'r');
        $importados = 0;
        $erros      = 0;
        $batch      = [];
        $agora      = now()->toDateTimeString();
        $primeiraLinha = true;

        while (($linha = fgets($handle)) !== false) {
            $linha = rtrim($linha, "\r\n");

            // Sempre pula a primeira linha (cabeçalho do CSV)
            if ($primeiraLinha) {
                $primeiraLinha = false;
                continue; // pula incondicionalmente
            }

            $cols = str_getcsv($linha, '|', '"');

            // Trata linhas com colunas extras (pipe dentro de campo)
            if (count($cols) > self::N_COLUNAS) {
                $cols = $this->tratarLinhaComErro($linha);
                if (!$cols) { $erros++; continue; }
            }

            if (count($cols) < self::N_COLUNAS) {
                $erros++;
                continue;
            }

            $ca = trim($cols[0] ?? '');
            if (!$ca) { $erros++; continue; }

            $registro = [
                'ca'                    => $ca,
                'data_validade'         => $this->parseData(trim($cols[1] ?? '')),
                'situacao'              => $this->limpar($cols[2] ?? ''),
                'cnpj_fabricante'       => $this->limpar($cols[4] ?? ''),
                'razao_social'          => $this->limpar($cols[5] ?? ''),
                'natureza'              => $this->limpar($cols[6] ?? ''),
                'nome_equipamento'      => $this->limpar($cols[7] ?? ''),
                'descricao_equipamento' => $this->limpar($cols[8] ?? ''),
                'marca'                 => $this->limpar($cols[9] ?? ''),
                'referencia'            => $this->limpar($cols[10] ?? ''),
                'norma'                 => $this->limpar($cols[18] ?? ''),
                'dados_completos'       => null, // não armazenamos o JSON completo no bulk
                'atualizado_em'         => $agora,
                'api_disponivel'        => true,
            ];

            $batch[$ca] = $registro; // chave = CA evita duplicatas no mesmo batch

            // Insere em lotes
            if (count($batch) >= self::CHUNK_SIZE) {
                $this->upsertBatch(array_values($batch));
                $importados += count($batch);
                $batch = [];
                $this->output->write('.');
            }
        }

        // Último lote
        if ($batch) {
            $this->upsertBatch(array_values($batch));
            $importados += count($batch);
        }

        fclose($handle);
        $this->newLine();

        return [$importados, $erros];
    }

    private function upsertBatch(array $batch): void
    {
        DB::table('ca_cache')->upsert(
            $batch,
            ['ca'],
            ['situacao','data_validade','nome_equipamento','descricao_equipamento',
             'marca','referencia','natureza','razao_social','cnpj_fabricante',
             'norma','atualizado_em','api_disponivel']
        );
    }

    private function tratarLinhaComErro(string $linha): ?array
    {
        // Tenta split mais restritivo: pipe não precedido por espaço
        $cols = preg_split('/(?<! )\|/', $linha);
        if (is_array($cols) && count($cols) === self::N_COLUNAS) {
            return $cols;
        }
        return null;
    }

    private function limpar(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        return $v === '' ? null : $v;
    }

    private function parseData(?string $data): ?string
    {
        if (!$data) return null;
        $data = trim($data);
        if (!$data) return null;
        try {
            // Formato do MTE: dd/mm/yyyy
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $data)->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($data)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}
