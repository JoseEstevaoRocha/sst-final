<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Clinica;

class ClinicaController extends Controller {
    public function index() { $clinicas=Clinica::orderBy('nome')->paginate(20); return view('clinicas.index',compact('clinicas')); }
    public function create() { return view('clinicas.form',['clinica'=>null]); }
    public function store(Request $r) {
        $r->validate(['nome'=>'required','whatsapp'=>'required']);
        Clinica::create(['nome'=>$r->nome,'cnpj'=>preg_replace('/\D/','',$r->cnpj??''),'whatsapp'=>preg_replace('/\D/','',$r->whatsapp),'telefone'=>$r->telefone,'email'=>$r->email,'endereco'=>$r->endereco,'cidade'=>$r->cidade,'estado'=>$r->estado,'responsavel'=>$r->responsavel,'ativo'=>true]);
        return redirect()->route('clinicas.index')->with('success','Clínica cadastrada!');
    }
    public function edit(Clinica $clinica) { return view('clinicas.form',compact('clinica')); }
    public function update(Request $r, Clinica $clinica) {
        $r->validate(['nome'=>'required','whatsapp'=>'required']);
        $clinica->update(['nome'=>$r->nome,'whatsapp'=>preg_replace('/\D/','',$r->whatsapp),'telefone'=>$r->telefone,'email'=>$r->email,'endereco'=>$r->endereco,'cidade'=>$r->cidade,'estado'=>$r->estado,'responsavel'=>$r->responsavel]);
        return redirect()->route('clinicas.index')->with('success','Clínica atualizada!');
    }
    public function destroy(Clinica $clinica) { $clinica->delete(); return redirect()->route('clinicas.index')->with('success','Clínica excluída!'); }
    public function show(Clinica $c) { return redirect()->route('clinicas.index'); }
}
