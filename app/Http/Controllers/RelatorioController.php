<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{ASO, EntregaEPI, EntregaUniforme, Extintor, Maquina, Colaborador};

class RelatorioController extends Controller {
    public function index() {
        $stats = [
            'asos_vencidos'    => ASO::where('data_vencimento','<',today())->count(),
            'epis_vencidos'    => EntregaEPI::where('data_prevista_troca','<',today())->where('status','Ativo')->count(),
            'ext_vencidos'     => Extintor::where('proxima_recarga','<',today())->count(),
            'maquinas_manu'    => Maquina::where('status','manutencao')->count(),
            'colaboradores'    => Colaborador::where('status','Contratado')->count(),
            'entregas_epi_mes' => EntregaEPI::where('created_at','>=',now()->startOfMonth())->count(),
        ];
        return view('relatorios.index',compact('stats'));
    }
    public function asos(Request $r) { $asos=ASO::with(['colaborador.funcao','empresa'])->when($r->status,fn($q)=>$q->where('data_vencimento',$r->status==='vencidos'?'<':'>=',today()))->orderBy('data_vencimento')->paginate(30)->withQueryString(); return view('relatorios.asos',compact('asos')); }
    public function epis()      { $entregas=EntregaEPI::with(['colaborador','epi'])->orderByDesc('data_entrega')->paginate(30); return view('relatorios.epis',compact('entregas')); }
    public function uniformes() { $entregas=EntregaUniforme::with(['colaborador','uniforme','tamanho'])->orderByDesc('data_entrega')->paginate(30); return view('relatorios.uniformes',compact('entregas')); }
    public function extintores(){ $ext=Extintor::with(['setor'])->orderBy('status')->orderBy('proxima_recarga')->paginate(30); return view('relatorios.extintores',compact('ext')); }
    public function maquinas()  { $maq=Maquina::with(['setor'])->orderBy('status')->paginate(30); return view('relatorios.maquinas',compact('maq')); }
    public function export(Request $r, string $tipo) { return back()->with('success',"Exportação de $tipo iniciada (configure barryvdh/laravel-dompdf)."); }
}
