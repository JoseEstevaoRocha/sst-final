<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{GHE, Risco, Empresa};

class GHEController extends Controller {
    public function index(Request $r) {
        $ghes    = GHE::with(['empresa'])->when($r->empresa_id,fn($q)=>$q->where('empresa_id',$r->empresa_id))->orderBy('codigo')->get();
        $riscos  = Risco::orderBy('categoria')->orderBy('nome')->get();
        $empresas = auth()->user()->isSuperAdmin() ? Empresa::ativas()->get() : collect([auth()->user()->empresa]);
        return view('ghes.index',compact('ghes','riscos','empresas'));
    }
    public function store(Request $r) {
        $r->validate(['empresa_id'=>'required','codigo'=>'required','nome'=>'required']);
        GHE::create($r->only(['empresa_id','codigo','nome','descricao']));
        return back()->with('success','GHE criado!');
    }
    public function destroy(GHE $ghe) { $ghe->delete(); return back()->with('success','GHE excluído!'); }
    public function addRisco(Request $r, GHE $ghe) {
        $r->validate(['risco_id'=>'required','probabilidade'=>'required|integer|min:1|max:5','severidade'=>'required|integer|min:1|max:5']);
        $ghe->riscos()->syncWithoutDetaching([$r->risco_id=>['probabilidade'=>$r->probabilidade,'severidade'=>$r->severidade,'medidas_epc'=>$r->medidas_epc,'medidas_epi'=>$r->medidas_epi]]);
        return back()->with('success','Risco adicionado!');
    }
    public function removeRisco(GHE $ghe, Risco $risco) { $ghe->riscos()->detach($risco->id); return back()->with('success','Risco removido!'); }
    public function matriz() { $empresas=Empresa::ativas()->get(); $ghes=GHE::with('riscos')->get(); return view('ghes.matriz',compact('ghes','empresas')); }
    public function create() { return redirect()->route('ghes.index'); }
    public function show(GHE $ghe) { return redirect()->route('ghes.index'); }
    public function edit(GHE $ghe) { return redirect()->route('ghes.index'); }
    public function update(Request $r, GHE $ghe) { $r->validate(['nome'=>'required']); $ghe->update($r->only(['nome','descricao'])); return back()->with('success','GHE atualizado!'); }
}
