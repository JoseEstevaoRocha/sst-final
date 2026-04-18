<?php
namespace App\Services;

use App\Models\{NfEntrada, NfFornecedor, NfItem, EPI, EPIEstoque, EpiMovimentacao};
use Illuminate\Support\Facades\{DB, Log};

class NfEntradaService
{
    /**
     * Persiste a NF completa dentro de uma transação.
     * Se qualquer passo falhar, faz rollback de tudo.
     */
    public function salvar(array $dados, array $itens, int $empresaId, int $usuarioId): NfEntrada
    {
        return DB::transaction(function () use ($dados, $itens, $empresaId, $usuarioId) {

            // ── 1. Fornecedor ──────────────────────────────────────────────────
            $fornecedorId = $this->resolverFornecedor($dados['fornecedor'], $empresaId);

            // ── 2. Nota Fiscal ─────────────────────────────────────────────────
            $nf = NfEntrada::create([
                'empresa_id'       => $empresaId,
                'fornecedor_id'    => $fornecedorId,
                'usuario_id'       => $usuarioId,
                'numero'           => $dados['numero'],
                'serie'            => $dados['serie']            ?? '1',
                'chave_acesso'     => $dados['chave_acesso']     ?? null,
                'data_emissao'     => $dados['data_emissao'],
                'data_entrada'     => $dados['data_entrada'],
                'natureza_operacao'=> $dados['natureza_operacao'] ?? null,
                'valor_produtos'   => $dados['valor_produtos']   ?? 0,
                'valor_frete'      => $dados['valor_frete']      ?? 0,
                'valor_desconto'   => $dados['valor_desconto']   ?? 0,
                'valor_total'      => $dados['valor_total']      ?? 0,
                'observacoes'      => $dados['observacoes']      ?? null,
                'status'           => 'ativa',
            ]);

            // ── 3. Itens ───────────────────────────────────────────────────────
            foreach ($itens as $item) {
                $epi = $this->resolverEpi($item);

                NfItem::create([
                    'nf_entrada_id'    => $nf->id,
                    'epi_id'           => $epi->id,
                    'tamanho_id'       => $item['tamanho_id'] ?? null,
                    'codigo_fornecedor'=> $item['codigo_fornecedor'] ?? null,
                    'nome'             => $item['nome'],
                    'unidade'          => $item['unidade']    ?? 'un',
                    'quantidade'       => $item['quantidade'],
                    'valor_unitario'   => $item['valor_unitario'],
                    'valor_total'      => $item['valor_total'],
                    'lote'             => $item['lote']           ?? null,
                    'data_validade'    => $item['data_validade']  ?? null,
                ]);

                // ── 4. Atualiza estoque ────────────────────────────────────────
                $this->entradaEstoque(
                    $epi->id,
                    $empresaId,
                    $item['tamanho_id'] ?? null,
                    (int) $item['quantidade'],
                    $nf->id,
                    auth()->user()->name ?? 'sistema'
                );
            }

            Log::info("NF Entrada #{$nf->id} criada — {$nf->numero}/{$nf->serie} ({$nf->itens()->count()} itens)");

            return $nf;
        });
    }

    /**
     * Cancela a NF e reverte o estoque.
     */
    public function cancelar(NfEntrada $nf): void
    {
        DB::transaction(function () use ($nf) {
            foreach ($nf->itens()->with('epi')->get() as $item) {
                $est = EPIEstoque::where([
                    'epi_id'     => $item->epi_id,
                    'empresa_id' => $nf->empresa_id,
                    'tamanho_id' => $item->tamanho_id,
                ])->first();

                if ($est) {
                    $novaQtd = max(0, $est->quantidade - (int) $item->quantidade);
                    $est->update(['quantidade' => $novaQtd]);
                }

                EpiMovimentacao::create([
                    'epi_id'        => $item->epi_id,
                    'empresa_id'    => $nf->empresa_id,
                    'tipo'          => 'saida',
                    'quantidade'    => (int) $item->quantidade,
                    'motivo'        => "Cancelamento NF {$nf->numero}/{$nf->serie}",
                    'usuario'       => auth()->user()->name ?? 'sistema',
                    'nf_entrada_id' => $nf->id,
                ]);
            }

            $nf->update(['status' => 'cancelada']);
        });
    }

    // ── XML NF-e ──────────────────────────────────────────────────────────────

    /**
     * Lê um XML NF-e e retorna array estruturado para preencher o formulário.
     */
    public function parseXml(string $xmlContent): array
    {
        try {
            $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOERROR);
            if (!$xml) return ['erro' => 'XML inválido'];

            // Namespace NF-e
            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
            $inf = $xml->NFe->infNFe ?? $xml->xpath('//nfe:infNFe')[0] ?? null;

            if (!$inf) {
                // Tenta sem namespace
                $inf = $xml->NFe->infNFe ?? null;
                if (!$inf && isset($xml->NFe)) $inf = $xml->NFe->infNFe;
                if (!$inf) return ['erro' => 'Estrutura XML não reconhecida como NF-e'];
            }

            $ide    = $inf->ide;
            $emit   = $inf->emit;
            $total  = $inf->total->ICMSTot;

            $nota = [
                'numero'            => (string)($ide->nNF ?? ''),
                'serie'             => (string)($ide->serie ?? '1'),
                'chave_acesso'      => ltrim((string)($inf->attributes()['Id'] ?? ''), 'NFe'),
                'data_emissao'      => $this->parseDataXml((string)($ide->dhEmi ?? $ide->dEmi ?? '')),
                'natureza_operacao' => (string)($ide->natOp ?? ''),
                'valor_produtos'    => (float)($total->vProd  ?? 0),
                'valor_frete'       => (float)($total->vFrete ?? 0),
                'valor_desconto'    => (float)($total->vDesc  ?? 0),
                'valor_total'       => (float)($total->vNF    ?? 0),
            ];

            $endEmit = $emit->enderEmit ?? null;
            $fornecedor = [
                'razao_social'      => (string)($emit->xNome  ?? ''),
                'nome_fantasia'     => (string)($emit->xFant  ?? ''),
                'cnpj'              => $this->formatarCnpj((string)($emit->CNPJ ?? '')),
                'inscricao_estadual'=> (string)($emit->IE     ?? ''),
                'logradouro'        => (string)($endEmit?->xLgr   ?? ''),
                'numero'            => (string)($endEmit?->nro    ?? ''),
                'complemento'       => (string)($endEmit?->xCpl   ?? ''),
                'bairro'            => (string)($endEmit?->xBairro ?? ''),
                'municipio'         => (string)($endEmit?->xMun   ?? ''),
                'uf'                => (string)($endEmit?->UF     ?? ''),
                'cep'               => $this->formatarCep((string)($endEmit?->CEP ?? '')),
                'telefone'          => (string)($endEmit?->fone   ?? ''),
            ];

            $itens = [];
            foreach ($inf->det ?? [] as $det) {
                $prod = $det->prod;
                $itens[] = [
                    'codigo_fornecedor' => (string)($prod->cProd  ?? ''),
                    'nome'              => (string)($prod->xProd  ?? ''),
                    'unidade'           => (string)($prod->uCom   ?? 'un'),
                    'quantidade'        => (float)($prod->qCom    ?? 0),
                    'valor_unitario'    => (float)($prod->vUnCom  ?? 0),
                    'valor_total'       => (float)($prod->vProd   ?? 0),
                ];
            }

            return compact('nota', 'fornecedor', 'itens');
        } catch (\Throwable $e) {
            return ['erro' => 'Erro ao processar XML: ' . $e->getMessage()];
        }
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function resolverFornecedor(array $dados, int $empresaId): int
    {
        $cnpj = preg_replace('/\D/', '', $dados['cnpj'] ?? '');

        $fornecedor = NfFornecedor::where('empresa_id', $empresaId)
            ->when($cnpj, fn($q) => $q->where('cnpj', 'like', "%{$cnpj}%"))
            ->when(!$cnpj, fn($q) => $q->where('razao_social', $dados['razao_social']))
            ->first();

        if ($fornecedor) {
            $fornecedor->update(array_filter($dados));
            return $fornecedor->id;
        }

        return NfFornecedor::create(array_merge($dados, ['empresa_id' => $empresaId]))->id;
    }

    private function resolverEpi(array $item): EPI
    {
        $ca = trim($item['numero_ca'] ?? '');

        // 1. Busca pelo CA se informado
        if ($ca) {
            $epi = EPI::where('numero_ca', $ca)->first();
            if ($epi) return $epi;
        }

        // 2. Busca pelo nome + tipo
        $epi = EPI::where('nome', $item['nome'])
            ->where('tipo', $item['tipo'] ?? 'Outros')
            ->first();

        if ($epi) return $epi;

        // 3. Cria novo EPI automaticamente
        return EPI::create([
            'nome'          => $item['nome'],
            'tipo'          => $item['tipo']        ?? 'Outros',
            'numero_ca'     => $ca                  ?: null,
            'unidade'       => $item['unidade']     ?? 'un',
            'custo_unitario'=> $item['valor_unitario'] ?? null,
            'fornecedor'    => $item['fornecedor']  ?? null,
            'marca'         => $item['marca']       ?? null,
            'status'        => 'Ativo',
        ]);
    }

    private function entradaEstoque(
        int $epiId, int $empresaId, ?int $tamanhoId,
        int $quantidade, int $nfId, string $usuario
    ): void {
        $est = EPIEstoque::firstOrCreate(
            ['epi_id' => $epiId, 'empresa_id' => $empresaId, 'tamanho_id' => $tamanhoId],
            ['quantidade' => 0]
        );
        $est->increment('quantidade', $quantidade);

        EpiMovimentacao::create([
            'epi_id'        => $epiId,
            'empresa_id'    => $empresaId,
            'tipo'          => 'entrada',
            'quantidade'    => $quantidade,
            'motivo'        => "Entrada via Nota Fiscal #{$nfId}",
            'usuario'       => $usuario,
            'nf_entrada_id' => $nfId,
        ]);
    }

    private function parseDataXml(string $str): ?string
    {
        if (!$str) return null;
        // Remove timezone: "2024-01-15T10:30:00-03:00" → "2024-01-15"
        return substr($str, 0, 10);
    }

    private function formatarCnpj(string $cnpj): string
    {
        $d = preg_replace('/\D/', '', $cnpj);
        if (strlen($d) !== 14) return $cnpj;
        return substr($d,0,2).'.'.substr($d,2,3).'.'.substr($d,5,3).'/'.substr($d,8,4).'-'.substr($d,12,2);
    }

    private function formatarCep(string $cep): string
    {
        $d = preg_replace('/\D/', '', $cep);
        if (strlen($d) !== 8) return $cep;
        return substr($d,0,5).'-'.substr($d,5,3);
    }
}
