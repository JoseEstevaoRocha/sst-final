<?php
namespace App\Helpers;

/**
 * Calcula o Grupo de Risco de incêndio (NBR 14276:2006) a partir do CNAE.
 *
 * Grupos:
 *   A – Risco Baixo    → mínimo  5% de brigadistas por turno
 *   B – Risco Médio    → mínimo 10%
 *   C – Risco Alto     → mínimo 15%
 *   D – Risco Elevado  → mínimo 20%
 */
class CnaeRiscoHelper
{
    /**
     * Percentual mínimo recomendado por grupo (NBR 14276).
     */
    public static function pctMinimo(string $grupo): float
    {
        return match(strtoupper($grupo)) {
            'A' => 5.0,
            'B' => 10.0,
            'C' => 15.0,
            'D' => 20.0,
            default => 10.0,
        };
    }

    public static function labelGrupo(string $grupo): string
    {
        return match(strtoupper($grupo)) {
            'A' => 'Grupo A — Risco Baixo',
            'B' => 'Grupo B — Risco Médio',
            'C' => 'Grupo C — Risco Alto',
            'D' => 'Grupo D — Risco Elevado',
            default => 'Indefinido',
        };
    }

    public static function corGrupo(string $grupo): string
    {
        return match(strtoupper($grupo)) {
            'A' => '#16a34a',
            'B' => '#2563eb',
            'C' => '#d97706',
            'D' => '#dc2626',
            default => '#64748b',
        };
    }

    /**
     * Determina o Grupo de Risco pelo código CNAE.
     * Usa os dois primeiros dígitos (divisão IBGE) para classificar.
     * Retorna 'B' como padrão caso não encontre.
     */
    public static function grupoPorCnae(string $cnae): string
    {
        // Remove tudo que não for dígito
        $digits = preg_replace('/\D/', '', $cnae);
        if (strlen($digits) < 2) return 'B';

        $divisao = (int) substr($digits, 0, 2);

        return match(true) {

            // ── GRUPO D: Risco Elevado ────────────────────────────────────
            // Indústrias Extrativas (carvão, petróleo, minérios metálicos)
            in_array($divisao, [5, 6, 7, 8, 9]) => 'D',
            // Refino de petróleo e biocombustíveis
            $divisao === 19 => 'D',
            // Produtos químicos
            $divisao === 20 => 'D',
            // Eletricidade e gás
            $divisao === 35 => 'D',
            // Fabricação de explosivos, munições
            // (subclasse dentro do 25 — tratado no Grupo C; sem divisão própria)

            // ── GRUPO C: Risco Alto ───────────────────────────────────────
            // Alimentos e bebidas
            in_array($divisao, [10, 11, 12]) => 'C',
            // Madeira, celulose, papel, impressão
            in_array($divisao, [16, 17, 18]) => 'C',
            // Produtos farmacêuticos
            $divisao === 21 => 'C',
            // Borracha, plástico, minerais não-metálicos, metalurgia, produtos de metal
            in_array($divisao, [22, 23, 24, 25]) => 'C',
            // Veículos automotores, reboques, outros equipamentos de transporte
            in_array($divisao, [29, 30]) => 'C',
            // Construção civil
            in_array($divisao, [41, 42, 43]) => 'C',
            // Saúde humana e social
            in_array($divisao, [86, 87, 88]) => 'C',

            // ── GRUPO B: Risco Médio ──────────────────────────────────────
            // Agricultura, pecuária, silvicultura, pesca, aquicultura
            in_array($divisao, [1, 2, 3]) => 'B',
            // Têxtil, vestuário, couro e calçados
            in_array($divisao, [13, 14, 15]) => 'B',
            // Máquinas e equipamentos, eletrônica, equipamentos de TI
            in_array($divisao, [26, 27, 28]) => 'B',
            // Móveis e outras indústrias de transformação
            in_array($divisao, [31, 32, 33]) => 'B',
            // Saneamento, gestão de resíduos
            in_array($divisao, [36, 37, 38, 39]) => 'B',
            // Comércio (atacado e varejo)
            in_array($divisao, [45, 46, 47]) => 'B',
            // Transporte, armazenagem e correios
            in_array($divisao, [49, 50, 51, 52, 53]) => 'B',
            // Alojamento e alimentação (hotéis, restaurantes)
            in_array($divisao, [55, 56]) => 'B',
            // Educação
            $divisao === 85 => 'B',
            // Cultura, esporte e lazer
            in_array($divisao, [90, 91, 92, 93]) => 'B',

            // ── GRUPO A: Risco Baixo ──────────────────────────────────────
            // Informação e comunicação
            in_array($divisao, [58, 59, 60, 61, 62, 63]) => 'A',
            // Atividades financeiras e de seguros
            in_array($divisao, [64, 65, 66]) => 'A',
            // Atividades imobiliárias
            $divisao === 68 => 'A',
            // Atividades profissionais, científicas e técnicas
            in_array($divisao, [69, 70, 71, 72, 73, 74, 75]) => 'A',
            // Atividades administrativas e serviços complementares
            in_array($divisao, [77, 78, 79, 80, 81, 82]) => 'A',
            // Administração pública, defesa e seguridade social
            $divisao === 84 => 'A',
            // Organismos internacionais
            $divisao === 99 => 'A',
            // Atividades domésticas, organizações associativas
            in_array($divisao, [94, 95, 96, 97, 98]) => 'A',

            // Padrão
            default => 'B',
        };
    }

    /**
     * Retorna o grupo efetivo: usa o campo manual se preenchido, senão calcula pelo CNAE.
     */
    public static function grupoEfetivo(?string $grauManual, ?string $cnae): string
    {
        if ($grauManual && in_array(strtoupper($grauManual), ['A', 'B', 'C', 'D'])) {
            return strtoupper($grauManual);
        }
        if ($cnae) {
            return self::grupoPorCnae($cnae);
        }
        return 'B'; // padrão seguro
    }
}
