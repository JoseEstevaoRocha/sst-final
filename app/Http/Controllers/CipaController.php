<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{CipaMembro, Colaborador};

class CipaController extends Controller {
    public function index() {
        $membros = CipaMembro::with('colaborador')->where('ativo',true)->paginate(20);
        $colaboradores = Colaborador::ativos()->orderBy('nome')->get();
        return view('emergencia.cipa',compact('membros','colaboradores'));
    }
    public function store(Request $r) {
        $r->validate(['colaborador_id'=>'required','cargo'=>'required']);
        CipaMembro::create(['colaborador_id'=>$r->colaborador_id,'empresa_id'=>auth()->user()->empresa_id,'cargo'=>$r->cargo,'mandato_inicio'=>$r->mandato_inicio,'mandato_fim'=>$r->mandato_fim,'tipo'=>$r->tipo,'ativo'=>true]);
        return back()->with('success','Membro CIPA cadastrado!');
    }
    public function destroy(int $id) { CipaMembro::find($id)?->update(['ativo'=>false]); return back()->with('success','Membro removido!'); }
}
