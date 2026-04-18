<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Empresa;

class EmpresaController extends Controller {
    public function index() { $empresas=Empresa::orderBy('razao_social')->paginate(20); return view('empresas.index',compact('empresas')); }
    public function create() { return view('empresas.form',['empresa'=>null]); }
    public function store(Request $r) {
        $r->validate(['razao_social'=>'required','cnpj'=>'required|size:14|unique:empresas,cnpj']);
        Empresa::create($r->only(['razao_social','nome_fantasia','cnpj','cnae','grau_risco_incendio','endereco','cidade','estado','cep','telefone','email','status']));
        return redirect()->route('empresas.index')->with('success','Empresa cadastrada!');
    }
    public function show(Empresa $empresa) { return view('empresas.show',compact('empresa')); }
    public function edit(Empresa $empresa) { return view('empresas.form',compact('empresa')); }
    public function update(Request $r, Empresa $empresa) {
        $r->validate(['razao_social'=>'required',"cnpj"=>"required|size:14|unique:empresas,cnpj,{$empresa->id}"]);
        $empresa->update($r->only(['razao_social','nome_fantasia','cnpj','cnae','grau_risco_incendio','endereco','cidade','estado','cep','telefone','email','status']));
        return redirect()->route('empresas.index')->with('success','Empresa atualizada!');
    }
    public function destroy(Empresa $empresa) { $empresa->delete(); return redirect()->route('empresas.index')->with('success','Empresa excluída!'); }
}
