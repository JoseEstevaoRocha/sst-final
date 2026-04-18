<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Tamanho;

class TamanhoController extends Controller {
    public function index() {
        $tamanhos = Tamanho::orderBy('ordem')->get();
        return view('tamanhos.index', compact('tamanhos'));
    }

    public function store(Request $r) {
        $r->validate(['codigo' => 'required|unique:tamanhos,codigo']);
        Tamanho::create(['codigo' => strtoupper(trim($r->codigo)), 'descricao' => $r->descricao, 'ordem' => $r->ordem ?? 99]);
        return back()->with('success', 'Tamanho criado!');
    }

    public function update(Request $r, Tamanho $tamanho) {
        $r->validate(['codigo' => 'required|unique:tamanhos,codigo,'.$tamanho->id]);
        $tamanho->update(['codigo' => strtoupper(trim($r->codigo)), 'descricao' => $r->descricao, 'ordem' => $r->ordem ?? 99]);
        return back()->with('success', 'Atualizado!');
    }

    public function destroy(Tamanho $tamanho) {
        $tamanho->delete();
        return back()->with('success', 'Excluído!');
    }

    public function seed(Request $r) {
        $tipo = $r->tipo ?? 'roupas';

        if ($tipo === 'calcados') {
            $seeds = [];
            $ordem = 20;
            for ($n = 33; $n <= 48; $n++) {
                $seeds[] = ['codigo' => (string)$n, 'descricao' => 'Nº '.$n, 'ordem' => $ordem++];
            }
        } else {
            $seeds = [
                ['codigo' => 'PP', 'descricao' => 'PP - Extra Pequeno', 'ordem' => 1],
                ['codigo' => 'P',  'descricao' => 'P - Pequeno',        'ordem' => 2],
                ['codigo' => 'M',  'descricao' => 'M - Médio',          'ordem' => 3],
                ['codigo' => 'G',  'descricao' => 'G - Grande',         'ordem' => 4],
                ['codigo' => 'GG', 'descricao' => 'GG - Extra Grande',  'ordem' => 5],
                ['codigo' => 'XG', 'descricao' => 'XG',                 'ordem' => 6],
                ['codigo' => 'G1', 'descricao' => 'G1',                 'ordem' => 7],
                ['codigo' => 'G2', 'descricao' => 'G2',                 'ordem' => 8],
                ['codigo' => 'G3', 'descricao' => 'G3',                 'ordem' => 9],
            ];
        }

        $c = 0;
        foreach ($seeds as $s) {
            if (!Tamanho::where('codigo', $s['codigo'])->exists()) {
                Tamanho::create($s);
                $c++;
            }
        }
        return back()->with('success', "$c tamanho(s) criado(s)!");
    }

    public function create() { return redirect()->route('tamanhos.index'); }
    public function show(Tamanho $t) { return redirect()->route('tamanhos.index'); }
    public function edit(Tamanho $t) { return redirect()->route('tamanhos.index'); }
}
