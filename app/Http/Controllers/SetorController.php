<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Setor, Empresa};

class SetorController extends Controller {
    public function index(Request $r) {
        $q = Setor::with('empresa');
        if ($r->empresa_id) $q->where('empresa_id', $r->empresa_id);
        if ($r->search)     $q->where('nome', 'ilike', "%{$r->search}%");
        $setores  = $q->orderBy('nome')->paginate(25)->withQueryString();
        $empresas = Empresa::ativas()->orderBy('razao_social')->get();
        return view('setores.index', compact('setores','empresas'));
    }
    public function store(Request $r) {
        $r->validate(['empresa_id'=>'required|exists:empresas,id','nome'=>'required']);
        Setor::create($r->only(['empresa_id','nome','descricao']));
        return back()->with('success','Setor criado!');
    }
    public function update(Request $r, Setor $setor) {
        $r->validate(['nome'=>'required']);
        $setor->update($r->only(['nome','descricao']));
        return back()->with('success','Setor atualizado!');
    }
    public function destroy(Setor $setor) {
        try {
            $setor->delete();
            return back()->with('success', 'Setor excluído!');
        } catch (\Exception $e) {
            return back()->with('error', 'Não é possível excluir este setor pois existem colaboradores vinculados a ele.');
        }
    }
    public function create() { return redirect()->route('setores.index'); }
    public function show(Setor $setor) { return redirect()->route('setores.index'); }
    public function edit(Setor $setor) { return redirect()->route('setores.index'); }
}
