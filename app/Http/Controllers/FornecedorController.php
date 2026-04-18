<?php
namespace App\Http\Controllers;

use App\Models\NfFornecedor;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function index(Request $r)
    {
        $q = NfFornecedor::query();

        if ($r->search) {
            $q->where(fn($sq) =>
                $sq->where('razao_social', 'ilike', "%{$r->search}%")
                   ->orWhere('nome_fantasia', 'ilike', "%{$r->search}%")
                   ->orWhere('cnpj', 'like', "%{$r->search}%")
            );
        }

        if ($r->uf) $q->where('uf', $r->uf);

        $fornecedores = $q->withCount('notas')->orderBy('razao_social')->paginate(20)->withQueryString();

        return view('epi.fornecedores.index', compact('fornecedores'));
    }

    public function create()
    {
        return view('epi.fornecedores.form', ['fornecedor' => null]);
    }

    public function store(Request $r)
    {
        $dados = $this->validar($r);
        $dados['empresa_id'] = app()->bound('tenant_id') ? app('tenant_id') : auth()->user()->empresa_id;
        $fornecedor = NfFornecedor::create($dados);

        if ($r->expectsJson()) {
            return response()->json(['id' => $fornecedor->id, 'razao_social' => $fornecedor->razao_social, 'nome_fantasia' => $fornecedor->nome_fantasia, 'cnpj' => $fornecedor->cnpj]);
        }

        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor cadastrado com sucesso!');
    }

    public function edit(NfFornecedor $fornecedor)
    {
        return view('epi.fornecedores.form', compact('fornecedor'));
    }

    public function update(Request $r, NfFornecedor $fornecedor)
    {
        $dados = $this->validar($r, $fornecedor->id);
        $fornecedor->update($dados);
        return redirect()->route('fornecedores.index')->with('success', 'Fornecedor atualizado!');
    }

    public function destroy(NfFornecedor $fornecedor)
    {
        if ($fornecedor->notas()->exists()) {
            return back()->with('error', 'Não é possível excluir: fornecedor possui notas fiscais vinculadas.');
        }
        $fornecedor->delete();
        return back()->with('success', 'Fornecedor excluído.');
    }

    private function validar(Request $r, ?int $ignoreId = null): array
    {
        $r->validate([
            'razao_social' => 'required|string|max:255',
            'nome_fantasia'=> 'nullable|string|max:255',
            'cnpj'         => 'nullable|string|max:18',
            'email'        => 'nullable|email|max:255',
        ]);

        return $r->only([
            'razao_social','nome_fantasia','cnpj','inscricao_estadual',
            'logradouro','numero','complemento','bairro','municipio','uf','cep',
            'telefone','email',
        ]);
    }
}
