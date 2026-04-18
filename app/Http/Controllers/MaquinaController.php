<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Maquina, Setor, Empresa, Mecanico, Colaborador};

class MaquinaController extends Controller {
    public function index(Request $r) {
        $user = auth()->user();
        $q = Maquina::with(['setor','empresa']);
        if (!$user->isSuperAdmin()) $q->where('empresa_id', $user->empresa_id);
        if ($r->empresa_id) $q->where('empresa_id', $r->empresa_id);
        if ($r->setor_id)   $q->where('setor_id', $r->setor_id);
        if ($r->search)     $q->where(fn($sq)=>$sq->where('nome','ilike',"%{$r->search}%")->orWhere('numero_serie','ilike',"%{$r->search}%"));
        if ($r->status)     $q->where('status',$r->status);
        $maquinas = $q->orderBy('nome')->paginate(20)->withQueryString();
        $empresas = $user->isSuperAdmin() ? Empresa::ativas()->get() : collect();
        $setores  = $r->empresa_id
            ? Setor::where('empresa_id', $r->empresa_id)->orderBy('nome')->get()
            : ($user->isSuperAdmin() ? collect() : Setor::where('empresa_id', $user->empresa_id)->orderBy('nome')->get());
        $base  = fn() => Maquina::when(!$user->isSuperAdmin(), fn($q) => $q->where('empresa_id', $user->empresa_id));
        $stats = ['total'=>$base()->count(),'operacionais'=>$base()->where('status','operacional')->count(),'inativas'=>$base()->where('status','inativo')->count()];
        return view('maquinas.index',compact('maquinas','setores','empresas','stats'));
    }
    public function create() {
        $setores  = Setor::orderBy('nome')->get();
        $empresas = auth()->user()->hasRole('super-admin') ? Empresa::ativas()->get() : collect();
        return view('maquinas.form',['maquina'=>null,'setores'=>$setores,'empresas'=>$empresas]);
    }
    public function store(Request $r) {
        $r->validate(['nome'=>'required','empresa_id'=>auth()->user()->hasRole('super-admin')?'required':'nullable']);
        $data = $r->only(['nome','marca','modelo','numero_serie','ano_fabricacao','setor_id','status','observacoes']);
        $data['empresa_id'] = auth()->user()->hasRole('super-admin') ? $r->empresa_id : auth()->user()->empresa_id;
        Maquina::create($data);
        return redirect()->route('maquinas.index')->with('success','Máquina cadastrada!');
    }
    public function edit(Maquina $maquina) {
        $setores  = Setor::orderBy('nome')->get();
        $empresas = auth()->user()->hasRole('super-admin') ? Empresa::ativas()->get() : collect();
        return view('maquinas.form',compact('maquina','setores','empresas'));
    }
    public function update(Request $r, Maquina $maquina) {
        $r->validate(['nome'=>'required']);
        $data = $r->only(['nome','marca','modelo','numero_serie','ano_fabricacao','setor_id','status','observacoes']);
        if (auth()->user()->hasRole('super-admin') && $r->empresa_id) {
            $data['empresa_id'] = $r->empresa_id;
        }
        $maquina->update($data);
        return redirect()->route('maquinas.index')->with('success','Máquina atualizada!');
    }
    public function destroy(Maquina $maquina) { $maquina->delete(); return redirect()->route('maquinas.index')->with('success','Máquina excluída!'); }
    public function mecanicos(Request $r) {
        $user      = auth()->user();
        $empresas  = $user->isSuperAdmin() ? Empresa::ativas()->get() : collect();
        $empresaId = $r->empresa_id ?? ($user->isSuperAdmin() ? null : $user->empresa_id);
        $setores   = $empresaId ? Setor::where('empresa_id', $empresaId)->orderBy('nome')->get() : collect();

        // Colaboradores disponíveis (filtrados por setor se selecionado)
        $colaboradores = collect();
        if ($empresaId) {
            $q = Colaborador::with(['setor','funcao'])
                ->where('empresa_id', $empresaId)
                ->where('status', 'Contratado')
                ->orderBy('nome');
            if ($r->setor_id) $q->where('setor_id', $r->setor_id);
            $colaboradores = $q->get();
        }

        // IDs dos mecânicos já cadastrados desta empresa
        $mecanicoIds = $empresaId
            ? Mecanico::where('empresa_id', $empresaId)->pluck('colaborador_id')->toArray()
            : [];

        // Lista de mecânicos cadastrados com dados do colaborador
        $mecanicosCadastrados = $empresaId
            ? Mecanico::with(['colaborador.setor','colaborador.funcao'])
                ->where('empresa_id', $empresaId)
                ->get()
            : collect();

        return view('mecanicos.index', compact(
            'empresas','setores','colaboradores','empresaId',
            'mecanicoIds','mecanicosCadastrados'
        ));
    }

    public function mecanicoAdd(Request $r, Colaborador $colaborador) {
        $empresaId = $colaborador->empresa_id;
        Mecanico::firstOrCreate(['empresa_id'=>$empresaId,'colaborador_id'=>$colaborador->id]);
        return back()->with('success', $colaborador->nome.' adicionado como mecânico!');
    }

    public function mecanicoRemove(Request $r, Colaborador $colaborador) {
        Mecanico::where('empresa_id', $colaborador->empresa_id)
            ->where('colaborador_id', $colaborador->id)
            ->delete();
        return back()->with('success', $colaborador->nome.' removido dos mecânicos.');
    }

    public function show(Maquina $m) { return redirect()->route('maquinas.index'); }
    public function checklist(Maquina $maquina) { return view('maquinas.checklist',compact('maquina')); }
}
