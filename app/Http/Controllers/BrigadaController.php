<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Brigadista, Setor, Empresa, Colaborador};
use App\Helpers\CnaeRiscoHelper;

class BrigadaController extends Controller {

    private function empresaId() {
        $user = auth()->user();
        return $user->isSuperAdmin() ? null : $user->empresa_id;
    }

    public function index() {
        $user = auth()->user();
        $empresaId = $this->empresaId();

        $brigadistas = Brigadista::with(['colaborador.setor', 'colaborador.funcao'])
            ->where('ativo', true)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->get()
            ->sortBy([
                [fn($b) => $b->colaborador?->setor?->nome ?? 'zzz', 'asc'],
                [fn($b) => $b->colaborador?->nome ?? '', 'asc'],
            ]);

        $setores  = Setor::when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))->orderBy('nome')->get();
        $empresas = $user->isSuperAdmin() ? Empresa::ativas()->orderBy('razao_social')->get() : collect();

        return view('emergencia.brigada', compact('brigadistas', 'setores', 'empresas'));
    }

    public function dashboard(Request $r) {
        $user      = auth()->user();
        $empresaId = $user->isSuperAdmin()
            ? ($r->empresa_id ?: null)
            : $user->empresa_id;

        $empresas = $user->isSuperAdmin() ? Empresa::ativas()->orderBy('razao_social')->get() : collect();

        // Super admin deve obrigatoriamente selecionar uma empresa
        if ($user->isSuperAdmin() && !$empresaId) {
            return view('emergencia.brigada-dashboard', [
                'requireEmpresa'    => true,
                'empresas'          => $empresas,
                'empresaId'         => null,
                'totalBrigadistas'  => 0, 'totalColaboradores' => 0, 'pctGeral' => 0,
                'setores'           => collect(), 'brigadistas' => collect(),
                'grupoRisco'        => 'B', 'pctMinimo' => 10.0,
                'labelGrupo'        => '', 'corGrupo' => '#64748b',
                'empresa'           => null,
            ]);
        }

        $totalBrigadistas = Brigadista::where('ativo', true)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->count();

        $totalColaboradores = \App\Models\Colaborador::where('status', 'Contratado')
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->count();

        // Por setor: brigadistas e total de colaboradores
        $setores = Setor::with(['colaboradores' => fn($q) => $q->where('status', 'Contratado')])
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->get()
            ->map(function($s) {
                $totalCol = $s->colaboradores->count();
                $totalBrig = Brigadista::where('ativo', true)
                    ->whereHas('colaborador', fn($q) => $q->where('setor_id', $s->id))
                    ->count();
                return [
                    'id'        => $s->id,
                    'nome'      => $s->nome,
                    'total_col' => $totalCol,
                    'total_brig'=> $totalBrig,
                    'pct'       => $totalCol > 0 ? round($totalBrig / $totalCol * 100, 1) : 0,
                ];
            })
            ->filter(fn($s) => $s['total_col'] > 0)
            ->values();

        // Lista completa de brigadistas por setor para a planta
        $brigadistas = Brigadista::with(['colaborador.setor', 'colaborador.funcao'])
            ->where('ativo', true)
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->get()
            ->sortBy([
                [fn($b) => $b->colaborador?->setor?->nome ?? 'zzz', 'asc'],
                [fn($b) => $b->colaborador?->nome ?? '', 'asc'],
            ]);

        $pctGeral = $totalColaboradores > 0
            ? round($totalBrigadistas / $totalColaboradores * 100, 1)
            : 0;

        // Grau de risco NBR 14276 da empresa
        $empresa   = $empresaId ? Empresa::find($empresaId) : null;
        $grupoRisco = $empresa ? $empresa->grupo_risco_efetivo : CnaeRiscoHelper::grupoEfetivo(null, null);
        $pctMinimo  = CnaeRiscoHelper::pctMinimo($grupoRisco);
        $labelGrupo = CnaeRiscoHelper::labelGrupo($grupoRisco);
        $corGrupo   = CnaeRiscoHelper::corGrupo($grupoRisco);

        return view('emergencia.brigada-dashboard', compact(
            'totalBrigadistas', 'totalColaboradores', 'pctGeral',
            'setores', 'brigadistas',
            'grupoRisco', 'pctMinimo', 'labelGrupo', 'corGrupo', 'empresa',
            'empresaId', 'empresas'
        ));
    }

    public function store(Request $r) {
        $r->validate(['colaborador_id' => 'required', 'funcao_brigada' => 'required']);
        $user      = auth()->user();
        $empresaId = $user->isSuperAdmin() ? ($r->empresa_id ?: $user->empresa_id) : $user->empresa_id;
        Brigadista::updateOrCreate(
            ['colaborador_id' => $r->colaborador_id, 'empresa_id' => $empresaId],
            ['funcao_brigada' => $r->funcao_brigada, 'data_inicio' => $r->data_inicio, 'data_validade_cert' => $r->data_validade_cert, 'ativo' => true]
        );
        return back()->with('success', 'Brigadista cadastrado!');
    }

    public function storeLote(Request $r) {
        $r->validate(['itens' => 'required|array|min:1', 'itens.*.colaborador_id' => 'required', 'itens.*.funcao_brigada' => 'required']);
        $user      = auth()->user();
        $empresaId = $user->isSuperAdmin() ? ($r->empresa_id ?: $user->empresa_id) : $user->empresa_id;
        $count = 0;
        foreach ($r->itens as $item) {
            Brigadista::updateOrCreate(
                ['colaborador_id' => $item['colaborador_id'], 'empresa_id' => $empresaId],
                ['funcao_brigada' => $item['funcao_brigada'], 'data_inicio' => $item['data_inicio'] ?? null, 'data_validade_cert' => $item['data_validade_cert'] ?? null, 'ativo' => true]
            );
            $count++;
        }
        return back()->with('success', "{$count} brigadista(s) cadastrado(s)!");
    }

    public function edit(int $id) {
        $b = Brigadista::with(['colaborador.setor'])->findOrFail($id);
        return response()->json([
            'id'                => $b->id,
            'colaborador_id'    => $b->colaborador_id,
            'nome'              => $b->colaborador?->nome,
            'setor'             => $b->colaborador?->setor?->nome,
            'funcao_brigada'    => $b->funcao_brigada,
            'data_inicio'       => $b->data_inicio?->format('Y-m-d'),
            'data_validade_cert'=> $b->data_validade_cert?->format('Y-m-d'),
        ]);
    }

    public function update(Request $r, int $id) {
        $r->validate(['funcao_brigada' => 'required']);
        $b = Brigadista::findOrFail($id);
        $b->update([
            'funcao_brigada'    => $r->funcao_brigada,
            'data_inicio'       => $r->data_inicio ?: null,
            'data_validade_cert'=> $r->data_validade_cert ?: null,
        ]);
        return back()->with('success', 'Brigadista atualizado!');
    }

    public function destroy(int $id) {
        Brigadista::find($id)?->update(['ativo' => false]);
        return back()->with('success', 'Brigadista removido!');
    }
}
