<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Extintor, InspecaoExtintor, Setor, Empresa};

class ExtintorController extends Controller {
    public function index(Request $r) {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            if ($r->has('empresa_id')) {
                $r->empresa_id ? session(['_extintor_empresa_id' => $r->empresa_id]) : session()->forget('_extintor_empresa_id');
            }
            $empresaId = $r->empresa_id ?? session('_extintor_empresa_id') ?? null;
        } else {
            $empresaId = $user->empresa_id;
        }
        $cW = $empresaId ? ['empresa_id' => $empresaId] : [];

        $q = Extintor::with(['setor'])->where($cW);
        if ($r->status) $q->where('status', $r->status);
        if ($r->tipo)   $q->where('tipo',   $r->tipo);
        $extintores = $q->orderBy('proxima_recarga')->paginate(20)->withQueryString();

        $stats = [
            'total'      => Extintor::where($cW)->count(),
            'vencidos'   => Extintor::where($cW)->where('proxima_recarga', '<', today())->count(),
            'regulares'  => Extintor::where($cW)->where('proxima_recarga', '>=', today())->count(),
            'manutencao' => Extintor::where($cW)->where('status', 'manutencao')->count(),
        ];

        $setores  = Setor::when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))->orderBy('nome')->get();
        $empresas = $user->isSuperAdmin() ? Empresa::ativas()->orderBy('razao_social')->get() : collect();

        return view('emergencia.extintores', compact('extintores', 'stats', 'setores', 'empresas', 'empresaId'));
    }

    private function redirectToIndex(Request $r): \Illuminate\Http\RedirectResponse {
        $params = array_filter(['empresa_id' => $r->empresa_id ?? session('_extintor_empresa_id') ?? null]);
        return redirect()->route('extintores.index', $params);
    }

    public function store(Request $r) {
        $user = auth()->user();
        $r->validate([
            'tipo'       => 'required',
            'empresa_id' => $user->isSuperAdmin() ? 'required|exists:empresas,id' : 'nullable',
        ]);
        $empresaId = $user->isSuperAdmin() ? ($r->empresa_id ?: $user->empresa_id) : $user->empresa_id;

        $data = $r->only(['numero_serie','tipo','capacidade','localizacao','setor_id','ultima_recarga','proxima_recarga','ultimo_teste_hidrostatico','proximo_teste_hidrostatico','status']);
        $data['empresa_id'] = $empresaId;
        $data['status'] = (!empty($data['proxima_recarga']) && $data['proxima_recarga'] < today()->format('Y-m-d')) ? 'vencido' : ($data['status'] ?? 'regular');

        if ($empresaId) session(['_extintor_empresa_id' => $empresaId]);
        Extintor::create($data);
        return $this->redirectToIndex($r)->with('success', 'Extintor cadastrado!');
    }

    public function update(Request $r, Extintor $extintor) {
        $r->validate(['tipo' => 'required']);
        $extintor->update($r->only(['numero_serie','tipo','capacidade','localizacao','setor_id','ultima_recarga','proxima_recarga','ultimo_teste_hidrostatico','proximo_teste_hidrostatico','status']));
        if ($extintor->empresa_id) session(['_extintor_empresa_id' => $extintor->empresa_id]);
        return $this->redirectToIndex($r)->with('success', 'Extintor atualizado!');
    }

    public function destroy(Extintor $extintor) {
        $empresaId = $extintor->empresa_id;
        $extintor->delete();
        session(['_extintor_empresa_id' => $empresaId]);
        $params = $empresaId ? ['empresa_id' => $empresaId] : [];
        return redirect()->route('extintores.index', $params)->with('success', 'Extintor excluído!');
    }

    public function show(Extintor $e)   { return redirect()->route('extintores.index'); }
    public function create()            { return redirect()->route('extintores.index'); }
    public function edit(Extintor $e)   { return redirect()->route('extintores.index'); }

    public function inspecao(Request $r, Extintor $extintor) {
        $r->validate(['data_inspecao' => 'required|date', 'resultado' => 'required|in:conforme,nao_conforme']);
        InspecaoExtintor::create([
            'extintor_id'   => $extintor->id,
            'empresa_id'    => $extintor->empresa_id,
            'data_inspecao' => $r->data_inspecao,
            'responsavel'   => $r->responsavel ?? auth()->user()->name,
            'resultado'     => $r->resultado,
            'observacoes'   => $r->observacoes,
        ]);
        if ($r->resultado === 'nao_conforme') $extintor->update(['status' => 'manutencao']);
        return back()->with('success', 'Inspeção registrada!');
    }
}
