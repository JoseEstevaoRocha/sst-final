<?php
namespace App\Http\Controllers;

use App\Models\{NfEntrada, NfFornecedor, EPI, Tamanho, Empresa};
use App\Services\NfEntradaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NfEntradaController extends Controller
{
    public function index(Request $r)
    {
        $user = auth()->user();

        $q = NfEntrada::with(['fornecedor', 'usuario'])
            ->withCount('itens');

        if ($r->search) {
            $q->where(fn($sq) =>
                $sq->where('numero', 'ilike', "%{$r->search}%")
                   ->orWhereHas('fornecedor', fn($fq) =>
                        $fq->where('razao_social', 'ilike', "%{$r->search}%")
                           ->orWhere('nome_fantasia', 'ilike', "%{$r->search}%")
                   )
            );
        }

        if ($r->status) $q->where('status', $r->status);
        if ($r->de)     $q->whereDate('data_entrada', '>=', $r->de);
        if ($r->ate)    $q->whereDate('data_entrada', '<=', $r->ate);

        $notas = $q->orderByDesc('data_entrada')->orderByDesc('id')->paginate(20)->withQueryString();

        $totalMes = NfEntrada::where('status', 'ativa')
            ->whereMonth('data_entrada', now()->month)
            ->sum('valor_total');

        return view('epi.nf.index', compact('notas', 'totalMes'));
    }

    public function create()
    {
        $user      = auth()->user();
        $fornecedores = NfFornecedor::orderBy('razao_social')->get();
        $tamanhos  = Tamanho::orderBy('ordem')->get();
        $epis      = EPI::ativos()->orderBy('nome')->get(['id','nome','tipo','numero_ca','unidade']);

        return view('epi.nf.form', compact('fornecedores', 'tamanhos', 'epis'));
    }

    public function store(Request $r, NfEntradaService $service)
    {
        $r->validate([
            'numero'                   => 'required|string|max:20',
            'serie'                    => 'required|string|max:5',
            'chave_acesso'             => 'nullable|digits:44',
            'data_emissao'             => 'required|date',
            'data_entrada'             => 'required|date',
            'valor_total'              => 'required|numeric|min:0',
            'fornecedor.razao_social'  => 'required|string|max:255',
            'itens'                    => 'required|array|min:1',
            'itens.*.nome'             => 'required|string|max:255',
            'itens.*.tipo'             => 'required|string',
            'itens.*.quantidade'       => 'required|numeric|min:0.001',
            'itens.*.valor_unitario'   => 'required|numeric|min:0',
        ]);

        try {
            $nf = $service->salvar(
                $r->only(['numero','serie','chave_acesso','data_emissao','data_entrada',
                          'natureza_operacao','valor_produtos','valor_frete','valor_desconto',
                          'valor_total','observacoes']) + ['fornecedor' => $r->fornecedor],
                $r->itens,
                app('tenant_id'),
                auth()->id()
            );

            return redirect()->route('nf-entradas.show', $nf)
                ->with('success', "NF {$nf->numero}/{$nf->serie} cadastrada! {$nf->itens()->count()} itens, estoque atualizado.");

        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return back()->withInput()
                ->with('error', "Já existe uma nota {$r->numero}/{$r->serie} cadastrada para esta empresa.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Erro ao salvar: ' . $e->getMessage());
        }
    }

    public function show(NfEntrada $nfEntrada)
    {
        $nfEntrada->load(['fornecedor', 'usuario', 'itens.epi', 'itens.tamanho', 'movimentacoes.epi']);
        return view('epi.nf.show', compact('nfEntrada'));
    }

    public function cancelar(NfEntrada $nfEntrada, NfEntradaService $service)
    {
        if ($nfEntrada->status === 'cancelada') {
            return back()->with('error', 'Esta nota já está cancelada.');
        }

        try {
            $service->cancelar($nfEntrada);
            return redirect()->route('nf-entradas.index')
                ->with('success', "NF {$nfEntrada->numero}/{$nfEntrada->serie} cancelada. Estoque revertido.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao cancelar: ' . $e->getMessage());
        }
    }

    // ── Upload e parsing de XML NF-e ─────────────────────────────────────────

    public function parseXml(Request $r, NfEntradaService $service)
    {
        $r->validate(['xml' => 'required|file|mimes:xml,txt|max:2048']);

        $conteudo = file_get_contents($r->file('xml')->getRealPath());
        $dados    = $service->parseXml($conteudo);

        if (isset($dados['erro'])) {
            return response()->json(['erro' => $dados['erro']], 422);
        }

        return response()->json($dados);
    }

    // ── API: busca fornecedores por CNPJ/nome ─────────────────────────────────

    public function buscarFornecedor(Request $r)
    {
        $term = $r->q ?? '';
        $fornecedores = NfFornecedor::where(fn($q) =>
            $q->where('razao_social', 'ilike', "%{$term}%")
              ->orWhere('nome_fantasia', 'ilike', "%{$term}%")
              ->orWhere('cnpj', 'like', "%{$term}%")
        )->limit(10)->get(['id','razao_social','nome_fantasia','cnpj','logradouro','numero','municipio','uf','cep','telefone','email','inscricao_estadual']);

        return response()->json($fornecedores);
    }
}
