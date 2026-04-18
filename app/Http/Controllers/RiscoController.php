<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Risco;

class RiscoController extends Controller {
    public function index() { $riscos=Risco::orderBy('categoria')->orderBy('nome')->paginate(25); return view('ghe.riscos',compact('riscos')); }
    public function store(Request $r) { $r->validate(['nome'=>'required','categoria'=>'required']); Risco::create($r->only(['nome','categoria','descricao','nr_referencia'])); return back()->with('success','Risco criado!'); }
    public function update(Request $r, Risco $risco) { $r->validate(['nome'=>'required','categoria'=>'required']); $risco->update($r->only(['nome','categoria','descricao','nr_referencia'])); return back()->with('success','Risco atualizado!'); }
    public function destroy(Risco $risco) { $risco->delete(); return back()->with('success','Risco excluído!'); }
    public function create() { return redirect()->route('riscos.index'); }
    public function show(Risco $r) { return redirect()->route('riscos.index'); }
    public function edit(Risco $r) { return redirect()->route('riscos.index'); }
}
