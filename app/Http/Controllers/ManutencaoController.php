<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Manutencao, Maquina, Empresa, Setor, Mecanico};

class ManutencaoController extends Controller {

    const TIPOS = [
        'preventiva'           => 'Preventiva',
        'corretiva'            => 'Corretiva',
        'preditiva'            => 'Preditiva',
        'inspecao'             => 'Inspeção NR12',
        'prensa_preventiva'    => 'Prensa — Preventiva',
        'prensa_corretiva'     => 'Prensa — Corretiva',
        'ferramenta_preventiva'=> 'Ferramenta — Preventiva',
        'ferramenta_corretiva' => 'Ferramenta — Corretiva',
    ];

    // ── Painel geral de manutenções ───────────────────────────────
    public function geral(Request $r) {
        $user = auth()->user();
        $q    = Manutencao::with(['maquina.setor','maquina.empresa','responsaveis'])
                    ->leftJoin('maquinas', 'maquinas.id', '=', 'manutencoes.maquina_id')
                    ->select('manutencoes.*');

        if (!$user->isSuperAdmin()) $q->where('manutencoes.empresa_id', $user->empresa_id);
        if ($r->empresa_id)   $q->where('manutencoes.empresa_id', $r->empresa_id);
        if ($r->setor_id)     $q->where('maquinas.setor_id', $r->setor_id);
        if ($r->tipo)         $q->where('manutencoes.tipo', $r->tipo);
        if ($r->maquina_id)   $q->where('manutencoes.maquina_id', $r->maquina_id);
        if ($r->data_inicio)  $q->whereDate('manutencoes.data_manutencao', '>=', $r->data_inicio);
        if ($r->data_fim)     $q->whereDate('manutencoes.data_manutencao', '<=', $r->data_fim);
        if ($r->mecanico_id)  $q->whereHas('responsaveis', fn($s) => $s->where('colaboradores.id', $r->mecanico_id));

        // ── Ordenação multi-coluna ────────────────────────────────
        $allowedSorts = [
            'data_manutencao' => 'manutencoes.data_manutencao',
            'duracao_minutos' => 'manutencoes.duracao_minutos',
            'tipo'            => 'manutencoes.tipo',
            'maquina'         => 'maquinas.nome',
        ];
        $currentSorts = [];
        foreach ((array)$r->input('sorts', []) as $col => $dir) {
            if (isset($allowedSorts[$col])) {
                $currentSorts[$col] = $dir === 'asc' ? 'asc' : 'desc';
            }
        }
        if (empty($currentSorts)) $currentSorts = ['data_manutencao' => 'desc'];
        foreach ($currentSorts as $col => $dir) {
            $q->orderBy($allowedSorts[$col], $dir);
        }

        $manutencoes = $q->paginate(25)->withQueryString();
        $empresas    = $user->isSuperAdmin() ? Empresa::ativas()->get() : collect();
        $setores     = $r->empresa_id
            ? Setor::where('empresa_id', $r->empresa_id)->orderBy('nome')->get()
            : ($user->isSuperAdmin() ? collect() : Setor::where('empresa_id', $user->empresa_id)->orderBy('nome')->get());

        // Dados para os filtros extras
        $baseEmpresaId = $r->empresa_id ?: ($user->isSuperAdmin() ? null : $user->empresa_id);
        $maquinasFiltro = Maquina::withoutTenant()
            ->when($baseEmpresaId, fn($q) => $q->where('empresa_id', $baseEmpresaId))
            ->orderBy('nome')->get();
        $mecanicosFiltro = Mecanico::with('colaborador:id,nome')
            ->when($baseEmpresaId, fn($q) => $q->where('empresa_id', $baseEmpresaId))
            ->get()
            ->sortBy('colaborador.nome');

        $base = fn() => Manutencao::when(!$user->isSuperAdmin(), fn($q) => $q->where('empresa_id', $user->empresa_id));
        $stats = [
            'total'      => $base()->count(),
            'preventiva' => $base()->whereIn('tipo', ['preventiva','prensa_preventiva','ferramenta_preventiva'])->count(),
            'corretiva'  => $base()->whereIn('tipo', ['corretiva','prensa_corretiva','ferramenta_corretiva'])->count(),
            'inspecao'   => $base()->where('tipo','inspecao')->count(),
        ];

        return view('manutencoes.index', compact('manutencoes','empresas','setores','stats','currentSorts','maquinasFiltro','mecanicosFiltro'));
    }

    public function geralStore(Request $r) {
        $r->validate([
            'maquina_id'      => 'required|exists:maquinas,id',
            'tipo'            => 'required|in:'.implode(',', array_keys(self::TIPOS)),
            'data_manutencao' => 'required|date',
            'hora_inicio'     => 'nullable|date_format:H:i',
            'hora_fim'        => 'nullable|date_format:H:i',
        ]);
        $maquina = Maquina::findOrFail($r->maquina_id);
        $duracao = $this->calcularDuracao($r->hora_inicio, $r->hora_fim);
        $man = Manutencao::create([
            'maquina_id'       => $maquina->id,
            'empresa_id'       => $maquina->empresa_id,
            'tipo'             => $r->tipo,
            'data_manutencao'  => $r->data_manutencao,
            'hora_inicio'      => $r->hora_inicio ?: null,
            'hora_fim'         => $r->hora_fim ?: null,
            'duracao_minutos'  => $duracao,
            'descricao'        => $r->descricao,
            'responsavel'      => $r->responsavel_externo ?: null,
        ]);
        if ($r->filled('mecanicos')) $man->responsaveis()->sync($r->mecanicos);
        $maquina->update(['ultima_manutencao' => $r->data_manutencao]);
        return redirect()->route('manutencoes.index')->with('success','Manutenção registrada!');
    }

    // ── Registro em lote ─────────────────────────────────────────
    public function geralStoreLote(Request $r) {
        $r->validate([
            'maquina_id'         => 'required|exists:maquinas,id',
            'tipo'               => 'required|in:'.implode(',', array_keys(self::TIPOS)),
            'data_manutencao'    => 'required|date',
            'itens'              => 'required|array|min:1',
            'itens.*.descricao'  => 'nullable|string',
            'itens.*.hora_inicio'=> 'nullable|date_format:H:i',
            'itens.*.hora_fim'   => 'nullable|date_format:H:i',
        ]);

        $maquina    = Maquina::findOrFail($r->maquina_id);
        $mecanicos  = $r->mecanicos ?? [];
        $registrados = 0;

        foreach ($r->itens as $item) {
            if (empty($item['descricao']) && empty($item['hora_inicio'])) continue; // ignora linhas vazias

            $duracao      = $this->calcularDuracao($item['hora_inicio'] ?? null, $item['hora_fim'] ?? null);
            // Data: usa a do item se preenchida, senão cai no padrão
            $dataItem     = !empty($item['data_manutencao']) ? $item['data_manutencao'] : $r->data_manutencao;
            // Mecânicos: usa os do item se marcados, senão cai nos compartilhados
            $mecanicosItem = !empty($item['mecanicos']) ? $item['mecanicos'] : $mecanicos;

            $man = Manutencao::create([
                'maquina_id'      => $maquina->id,
                'empresa_id'      => $maquina->empresa_id,
                'tipo'            => $r->tipo,
                'data_manutencao' => $dataItem,
                'hora_inicio'     => $item['hora_inicio'] ?: null,
                'hora_fim'        => $item['hora_fim']    ?: null,
                'duracao_minutos' => $duracao,
                'descricao'       => $item['descricao']   ?: null,
                'responsavel'     => $item['responsavel_externo'] ?: null,
            ]);
            if ($mecanicosItem) $man->responsaveis()->sync($mecanicosItem);
            $registrados++;
        }

        if ($registrados) {
            $maquina->update(['ultima_manutencao' => $r->data_manutencao]);
        }

        return redirect()->route('manutencoes.index')
            ->with('success', "{$registrados} manutenção(ões) registrada(s) em lote!");
    }

    // ── Importação CSV ────────────────────────────────────────────
    public function modeloCsv() {
        $tiposValidos = implode(' | ', array_keys(self::TIPOS));
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="modelo_manutencoes.csv"');
        echo "\xEF\xBB\xBF";
        echo "empresa_cnpj;maquina_nome;tipo;data_manutencao;duracao_minutos;descricao;responsavel\n";
        echo "12345678000100;Prensa 01;preventiva;2024-01-15;15;Troca de óleo e filtros;João Silva\n";
        echo ";; Tipos válidos: $tiposValidos\n";
        exit;
    }

    public function importar(Request $r) {
        $r->validate(['arquivo' => 'required|file|mimes:csv,txt|max:5120']);
        $path = $r->file('arquivo')->store('imports');
        $rows = $this->readCsv(storage_path('app/'.$path));
        $user = auth()->user();
        $s = 0; $e = [];

        foreach ($rows as $i => $row) {
            $linha = $i + 2;
            // Ignora linhas de comentário
            if (str_starts_with(trim($row[0] ?? $row['empresa_cnpj'] ?? ''), ';')) continue;

            $cnpj        = preg_replace('/\D/', '', $row['empresa_cnpj'] ?? $row[0] ?? '');
            $nomeMaq     = trim($row['maquina_nome'] ?? $row[1] ?? '');
            $tipo        = trim(strtolower($row['tipo'] ?? $row[2] ?? ''));
            $data        = trim($row['data_manutencao'] ?? $row[3] ?? '');
            $duracaoMin  = is_numeric($row['duracao_minutos'] ?? $row[4] ?? '') ? (int)($row['duracao_minutos'] ?? $row[4]) : null;
            $descricao   = trim($row['descricao'] ?? $row[5] ?? '');
            $responsavel = trim($row['responsavel'] ?? $row[6] ?? '');

            if (!$nomeMaq || !$tipo || !$data) {
                $e[] = "L$linha: máquina, tipo e data são obrigatórios."; continue;
            }
            if (!array_key_exists($tipo, self::TIPOS)) {
                $e[] = "L$linha: tipo '$tipo' inválido. Válidos: " . implode(', ', array_keys(self::TIPOS)); continue;
            }
            $dataFmt = $this->parseData($data);
            if (!$dataFmt) { $e[] = "L$linha: data '$data' inválida (use AAAA-MM-DD ou DD/MM/AAAA)."; continue; }

            // Resolver empresa
            $empresa = null;
            if ($cnpj) {
                $empresa = Empresa::where('cnpj', $cnpj)->first();
                if (!$empresa) { $e[] = "L$linha: empresa CNPJ $cnpj não encontrada."; continue; }
            } elseif (!$user->isSuperAdmin()) {
                $empresa = $user->empresa;
            } else {
                $e[] = "L$linha: empresa_cnpj obrigatório para super-admin."; continue;
            }

            // Resolver máquina
            $maquina = Maquina::withoutTenant()
                ->where('empresa_id', $empresa->id)
                ->where(fn($q) => $q->where('nome', 'ilike', $nomeMaq)->orWhere('numero_serie', $nomeMaq))
                ->first();
            if (!$maquina) { $e[] = "L$linha: máquina '$nomeMaq' não encontrada na empresa."; continue; }

            try {
                Manutencao::create([
                    'maquina_id'      => $maquina->id,
                    'empresa_id'      => $empresa->id,
                    'tipo'            => $tipo,
                    'data_manutencao' => $dataFmt,
                    'duracao_minutos' => $duracaoMin,
                    'descricao'       => $descricao,
                    'responsavel'     => $responsavel,
                ]);
                // Atualiza ultima_manutencao se for a mais recente
                if (!$maquina->ultima_manutencao || $dataFmt > $maquina->ultima_manutencao->format('Y-m-d')) {
                    $maquina->update(['ultima_manutencao' => $dataFmt]);
                }
                $s++;
            } catch (\Exception $ex) {
                $e[] = "L$linha: " . $ex->getMessage();
            }
        }

        $msg = "$s manutenção(ões) importada(s).";
        if ($e) $msg .= ' ' . count($e) . ' erro(s): ' . implode(' | ', array_slice($e, 0, 5));
        return redirect()->route('manutencoes.index')->with($s > 0 ? 'success' : 'error', $msg);
    }

    // ── Manutenções por máquina (tela específica via wrench) ──────
    public function index(Maquina $maquina) {
        $manutencoes = $maquina->manutencoes()->with('responsaveis')->orderByDesc('data_manutencao')->paginate(20);
        return view('maquinas.manutencoes', compact('maquina','manutencoes'));
    }

    public function store(Request $r, Maquina $maquina) {
        $r->validate([
            'tipo'            => 'required|in:'.implode(',', array_keys(self::TIPOS)),
            'data_manutencao' => 'required|date',
            'hora_inicio'     => 'nullable|date_format:H:i',
            'hora_fim'        => 'nullable|date_format:H:i',
        ]);
        $duracao = $this->calcularDuracao($r->hora_inicio, $r->hora_fim);
        $man = Manutencao::create([
            'maquina_id'      => $maquina->id,
            'empresa_id'      => $maquina->empresa_id,
            'tipo'            => $r->tipo,
            'data_manutencao' => $r->data_manutencao,
            'hora_inicio'     => $r->hora_inicio ?: null,
            'hora_fim'        => $r->hora_fim ?: null,
            'duracao_minutos' => $duracao,
            'descricao'       => $r->descricao,
            'responsavel'     => $r->responsavel_externo ?: null,
        ]);
        if ($r->filled('mecanicos')) $man->responsaveis()->sync($r->mecanicos);
        $maquina->update(['ultima_manutencao' => $r->data_manutencao]);
        return back()->with('success','Manutenção registrada!');
    }

    public function destroy(Manutencao $manutencao) { $manutencao->delete(); return back()->with('success','Excluída!'); }
    public function create(Maquina $maquina) { return redirect()->route('maquinas.manutencoes.index', $maquina); }
    public function show(Manutencao $m) { return back(); }

    public function edit(Manutencao $manutencao) {
        return response()->json([
            'id'              => $manutencao->id,
            'maquina_id'      => $manutencao->maquina_id,
            'maquina_nome'    => $manutencao->maquina?->nome,
            'empresa_id'      => $manutencao->empresa_id,
            'tipo'            => $manutencao->tipo,
            'data_manutencao' => $manutencao->data_manutencao->format('Y-m-d'),
            'hora_inicio'     => $manutencao->hora_inicio ? substr($manutencao->hora_inicio,0,5) : null,
            'hora_fim'        => $manutencao->hora_fim    ? substr($manutencao->hora_fim,0,5)    : null,
            'duracao_minutos' => $manutencao->duracao_minutos,
            'descricao'       => $manutencao->descricao,
            'responsavel'     => $manutencao->responsavel,
            'mecanicos'       => $manutencao->responsaveis->pluck('id'),
        ]);
    }

    public function update(Request $r, Manutencao $manutencao) {
        $r->validate([
            'tipo'            => 'required|in:'.implode(',', array_keys(self::TIPOS)),
            'data_manutencao' => 'required|date',
            'hora_inicio'     => 'nullable|date_format:H:i',
            'hora_fim'        => 'nullable|date_format:H:i',
        ]);
        $duracao = $this->calcularDuracao($r->hora_inicio, $r->hora_fim);
        $manutencao->update([
            'tipo'            => $r->tipo,
            'data_manutencao' => $r->data_manutencao,
            'hora_inicio'     => $r->hora_inicio ?: null,
            'hora_fim'        => $r->hora_fim     ?: null,
            'duracao_minutos' => $duracao,
            'descricao'       => $r->descricao,
            'responsavel'     => $r->responsavel_externo ?: null,
        ]);
        if ($r->has('mecanicos')) {
            $manutencao->responsaveis()->sync($r->mecanicos ?? []);
        }
        $maquina = $manutencao->maquina;
        if ($maquina && (!$maquina->ultima_manutencao || $r->data_manutencao >= $maquina->ultima_manutencao->format('Y-m-d'))) {
            $maquina->update(['ultima_manutencao' => $r->data_manutencao]);
        }
        return redirect()->back()->with('success','Manutenção atualizada!');
    }

    private function calcularDuracao(?string $inicio, ?string $fim): ?int {
        if (!$inicio || !$fim) return null;
        try {
            $i = new \DateTime($inicio);
            $f = new \DateTime($fim);
            if ($f < $i) $f->modify('+1 day'); // manutenção passou da meia-noite
            return (int) round(($f->getTimestamp() - $i->getTimestamp()) / 60);
        } catch (\Exception $ex) { return null; }
    }

    private function readCsv(string $path): array {
        $rows = []; $h = null;
        if (($f = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($f, 0, ';')) !== false) {
                $row = array_map(fn($v) => trim($v ?? ''), $row);
                if (!$h) { $h = array_map('trim', $row); }
                elseif (count($row) > 1) { $rows[] = count($h) === count($row) ? array_combine($h, $row) : $row; }
            }
            fclose($f);
        }
        return $rows;
    }

    private function parseData(string $d): ?string {
        if (!$d) return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $d, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
        try { return (new \DateTime($d))->format('Y-m-d'); } catch (\Exception $ex) { return null; }
    }
}
