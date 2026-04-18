<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Colaborador, ASO, EntregaEPI, EntregaUniforme, WhatsappMensagem, Empresa, Setor, Alerta};
use Carbon\Carbon;

class FichaController extends Controller {
    public function index(Request $r) {
        $colaboradores = collect();
        $empresas = auth()->user()->isSuperAdmin() ? Empresa::ativas()->get() : collect([auth()->user()->empresa]);
        $setores = $r->empresa_id ? Setor::where('empresa_id',$r->empresa_id)->get() : collect();
        if ($r->search || $r->empresa_id) {
            $q = Colaborador::with(['empresa','setor','funcao']);
            if ($r->search) $q->where(fn($sq)=>$sq->where('nome','ilike',"%{$r->search}%")->orWhere('cpf','ilike',"%{$r->search}%")->orWhere('matricula','ilike',"%{$r->search}%"));
            if ($r->setor_id) $q->where('setor_id',$r->setor_id);
            $colaboradores = $q->orderBy('nome')->limit(30)->get();
        }
        return view('ficha.index',compact('colaboradores','empresas','setores'));
    }
    public function show(Colaborador $colaborador) {
        $colaborador->load(['empresa','setor','funcao']);
        $asos      = $colaborador->asos()->orderByDesc('data_exame')->get();
        $epiEntregas = EntregaEPI::with('epi')->where('colaborador_id',$colaborador->id)->orderByDesc('data_entrega')->get();
        $uniEntregas = EntregaUniforme::with(['uniforme','tamanho'])->where('colaborador_id',$colaborador->id)->orderByDesc('data_entrega')->get();
        $waMsgs    = WhatsappMensagem::with('clinica')->where('colaborador_id',$colaborador->id)->orderByDesc('created_at')->limit(10)->get();
        $hoje      = today();
        $resumo = [
            'asoVencido'    => $asos->filter(fn($a)=>$a->data_vencimento&&$a->data_vencimento->isPast())->count()>0,
            'epiVencidos'   => $epiEntregas->filter(fn($e)=>$e->data_prevista_troca&&$e->data_prevista_troca->isPast()&&$e->status==='Ativo')->count(),
            'epiAVencer'    => $epiEntregas->filter(fn($e)=>$e->data_prevista_troca&&$e->data_prevista_troca->between($hoje,$hoje->copy()->addDays(30))&&$e->status==='Ativo')->count(),
            'totalUniformes'=> $uniEntregas->sum('quantidade'),
            'idadeAnos'     => $colaborador->data_nascimento?$colaborador->data_nascimento->age:0,
            'tempoMeses'    => $colaborador->data_admissao?$colaborador->data_admissao->diffInMonths($hoje):0,
        ];
        $alertaPpp = Alerta::where('colaborador_id', $colaborador->id)
            ->where('tipo', 'ppp')
            ->where('status', 'pendente')
            ->first();

        return view('ficha.show', compact('colaborador','asos','epiEntregas','uniEntregas','waMsgs','resumo','alertaPpp'));
    }
    public function pdf(Colaborador $colaborador) { return $this->show($colaborador); }
    public function epiPdf(Colaborador $colaborador) { return $this->show($colaborador); }
    public function uniformePdf(Colaborador $colaborador) { return $this->show($colaborador); }
}
