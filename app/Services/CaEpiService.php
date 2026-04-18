<?php
namespace App\Services;

use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

/**
 * Serviço de consulta ao CAEPI — 100% independente.
 * Consulta somente o banco local (ca_cache), populado pelo command caepi:sincronizar.
 * Fonte oficial: ftp.mtps.gov.br (Ministério do Trabalho e Emprego)
 */
class CaEpiService
{
    /**
     * Retorna dados do CA pelo número.
     * Busca no cache local — retorna null se não encontrado.
     */
    public function buscar(string $ca): ?array
    {
        $ca = trim($ca);
        if (!$ca) return null;

        $registro = DB::table('ca_cache')->where('ca', $ca)->first();
        return $registro ? (array) $registro : null;
    }

    /**
     * Retorna true se o CA está com situação VÁLIDO.
     */
    public function valido(string $ca): bool
    {
        $dados = $this->buscar($ca);
        return isset($dados['situacao']) &&
               mb_strtoupper($dados['situacao']) === 'VÁLIDO';
    }

    /**
     * Retorna todos os registros históricos de um CA (pode haver duplicatas com datas diferentes).
     */
    public function historico(string $ca): array
    {
        return DB::table('ca_cache')
            ->where('ca', $ca)
            ->orderBy('data_validade')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    /**
     * Atualiza ca_situacao e validade_ca nos EPIs com base no cache atual.
     * Chamado pelo command após sincronização.
     */
    public function atualizarEpis(): int
    {
        return DB::statement("
            UPDATE epis
            SET
                ca_situacao = c.situacao,
                validade_ca = COALESCE(epis.validade_ca, c.data_validade)
            FROM ca_cache c
            WHERE epis.numero_ca = c.ca
              AND epis.numero_ca IS NOT NULL
        ") ? DB::table('epis')->whereNotNull('numero_ca')->count() : 0;
    }
}
