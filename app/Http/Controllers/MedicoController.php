<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Medico, Clinica};

class MedicoController extends Controller {
    public function index(Request $r) {
        $q = Medico::with('clinica');
        if ($r->search)    $q->where('nome','ilike',"%{$r->search}%");
        if ($r->clinica_id)$q->where('clinica_id',$r->clinica_id);
        $medicos  = $q->orderBy('nome')->paginate(25)->withQueryString();
        $clinicas = Clinica::ativas()->orderBy('nome')->get();
        return view('configuracoes.medicos', compact('medicos','clinicas'));
    }

    public function store(Request $r) {
        $r->validate(['nome'=>'required|min:2','crm'=>'nullable|string','especialidade'=>'nullable|string','clinica_id'=>'nullable|exists:clinicas,id']);
        Medico::create($r->only(['nome','crm','especialidade','clinica_id','ativo']));
        return back()->with('success','Médico cadastrado!');
    }

    public function update(Request $r, Medico $medico) {
        $r->validate(['nome'=>'required|min:2']);
        $medico->update($r->only(['nome','crm','especialidade','clinica_id','ativo']));
        return back()->with('success','Médico atualizado!');
    }

    public function destroy(Medico $medico) {
        $medico->delete();
        return back()->with('success','Médico excluído!');
    }
}
