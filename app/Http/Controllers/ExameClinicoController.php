<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{ExameClinico, Setor, Funcao};

class ExameClinicoController extends Controller {

    public function index(Request $r) {
        $q = ExameClinico::query();
        if ($r->tipo)   $q->where('tipo', $r->tipo);
        if ($r->search) $q->where('nome','ilike',"%{$r->search}%");
        $exames = $q->orderBy('nome')->paginate(30)->withQueryString();
        return view('exames-clinicos.index', compact('exames'));
    }

    public function store(Request $r) {
        $r->validate(['nome' => 'required', 'tipo' => 'required']);
        ExameClinico::create($r->only(['nome','tipo','descricao','obrigatorio_nr']));
        return back()->with('success','Exame cadastrado!');
    }

    public function update(Request $r, ExameClinico $exame) {
        $r->validate(['nome' => 'required', 'tipo' => 'required']);
        $exame->update($r->only(['nome','tipo','descricao','obrigatorio_nr']));
        return back()->with('success','Exame atualizado!');
    }

    public function destroy(ExameClinico $exame) {
        $exame->delete();
        return back()->with('success','Exame excluído!');
    }

    // ── Exames do Setor ───────────────────────────────────────────
    public function setorExames(Setor $setor) {
        $setor->load('exames','empresa');
        $examesDisponiveis = ExameClinico::orderBy('tipo')->orderBy('nome')->get();
        $idsAssociados = $setor->exames->pluck('id');
        return view('exames-clinicos.setor', compact('setor','examesDisponiveis','idsAssociados'));
    }

    public function setorAddExame(Request $r, Setor $setor) {
        $r->validate(['exame_id' => 'required|exists:exames_clinicos,id']);
        $setor->exames()->syncWithoutDetaching([
            $r->exame_id => [
                'periodicidade_meses' => $r->periodicidade_meses ?: null,
                'obrigatorio'         => $r->boolean('obrigatorio', true),
            ]
        ]);
        return back()->with('success','Exame associado ao setor!');
    }

    public function setorRemoveExame(Setor $setor, ExameClinico $exame) {
        $setor->exames()->detach($exame->id);
        return back()->with('success','Exame removido do setor!');
    }

    // ── Exames da Função ──────────────────────────────────────────
    public function funcaoExames(Funcao $funcao) {
        $funcao->load('exames','setor.exames','empresa');
        $examesDisponiveis = ExameClinico::orderBy('tipo')->orderBy('nome')->get();
        $idsAssociados = $funcao->exames->pluck('id');
        return view('exames-clinicos.funcao', compact('funcao','examesDisponiveis','idsAssociados'));
    }

    public function funcaoImportarSetor(Funcao $funcao) {
        $examesSetor = $funcao->setor?->exames ?? collect();
        foreach ($examesSetor as $exame) {
            $funcao->exames()->syncWithoutDetaching([
                $exame->id => [
                    'periodicidade_meses' => $exame->pivot->periodicidade_meses,
                    'obrigatorio'         => $exame->pivot->obrigatorio,
                    'origem'              => 'setor',
                ]
            ]);
        }
        return back()->with('success', $examesSetor->count().' exame(s) importado(s) do setor!');
    }

    public function funcaoAddExame(Request $r, Funcao $funcao) {
        $r->validate(['exame_id' => 'required|exists:exames_clinicos,id']);
        $funcao->exames()->syncWithoutDetaching([
            $r->exame_id => [
                'periodicidade_meses' => $r->periodicidade_meses ?: null,
                'obrigatorio'         => $r->boolean('obrigatorio', true),
                'origem'              => 'funcao',
            ]
        ]);
        return back()->with('success','Exame associado à função!');
    }

    public function funcaoRemoveExame(Funcao $funcao, ExameClinico $exame) {
        $funcao->exames()->detach($exame->id);
        return back()->with('success','Exame removido da função!');
    }

    // ── Atribuição em lote: múltiplas funções ─────────────────────
    public function atribuirEmLote(Request $r) {
        $r->validate([
            'exames_ids'   => 'required|array|min:1',
            'exames_ids.*' => 'exists:exames_clinicos,id',
            'funcoes_ids'  => 'required|array|min:1',
            'funcoes_ids.*'=> 'exists:funcoes,id',
        ]);

        $total = 0;
        foreach ($r->funcoes_ids as $funcaoId) {
            $funcao = Funcao::findOrFail($funcaoId);
            foreach ($r->exames_ids as $exameId) {
                $funcao->exames()->syncWithoutDetaching([
                    $exameId => [
                        'periodicidade_meses' => $r->periodicidade_meses ?: null,
                        'obrigatorio'         => $r->boolean('obrigatorio', true),
                        'origem'              => 'funcao',
                    ]
                ]);
                $total++;
            }
        }

        return back()->with('success', "{$total} associação(ões) criada(s) com sucesso!");
    }
}
