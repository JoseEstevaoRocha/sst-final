<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Funcao, Setor, Empresa};

class FuncaoController extends Controller {
    public function index(Request $r) {
        $q = Funcao::with(['setor','empresa']);
        if ($r->empresa_id) $q->where('funcoes.empresa_id', $r->empresa_id);
        if ($r->setor_id)   $q->where('setor_id', $r->setor_id);
        if ($r->search)     $q->where('funcoes.nome', 'ilike', "%{$r->search}%");
        $funcoes  = $q->orderBy('funcoes.nome')->paginate(25)->withQueryString();
        $empresas = Empresa::ativas()->orderBy('razao_social')->get();
        $setores  = $r->empresa_id ? Setor::where('empresa_id', $r->empresa_id)->orderBy('nome')->get() : collect();
        return view('funcoes.index', compact('funcoes','empresas','setores'));
    }
    public function store(Request $r) {
        $r->validate(['empresa_id'=>'required','setor_id'=>'required|exists:setores,id','nome'=>'required']);
        Funcao::create($r->only(['empresa_id','setor_id','nome','descricao','cbo','periodicidade_aso_dias']));
        return back()->with('success','Função criada!');
    }
    public function update(Request $r, Funcao $funcao) {
        $r->validate(['nome'=>'required']);
        $funcao->update($r->only(['nome','descricao','cbo','periodicidade_aso_dias']));
        return back()->with('success','Função atualizada!');
    }
    public function destroy(Funcao $funcao) {
        try {
            $funcao->delete();
            return back()->with('success', 'Função excluída!');
        } catch (\Exception $e) {
            return back()->with('error', 'Não é possível excluir esta função pois existem colaboradores vinculados a ela.');
        }
    }
    public function create() { return redirect()->route('funcoes.index'); }
    public function show(Funcao $funcao) { return redirect()->route('funcoes.index'); }
    public function edit(Funcao $funcao) { return redirect()->route('funcoes.index'); }
}
