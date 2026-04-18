<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{Empresa, Colaborador, Setor, Funcao, EPI, EPIEstoque, ASO, Clinica};

class ImportacaoController extends Controller {
    public function index() { return view('importacao.index'); }

    public function importarColaboradores(Request $r) {
        $r->validate(['arquivo'=>'required|file|mimes:csv,txt,xlsx,xls|max:10240']);
        $ext = strtolower($r->file('arquivo')->getClientOriginalExtension());
        $path = $r->file('arquivo')->store('imports');
        $fullPath = storage_path('app/'.$path);
        $rows = $ext==='xlsx'||$ext==='xls' ? $this->readXlsx($fullPath) : $this->readCsv($fullPath);
        $res = $this->processarColaboradores($rows);
        return redirect()->route('importacao.index')->with('importResult', $res);
    }

    public function importarEpis(Request $r) {
        $r->validate(['arquivo'=>'required|file|mimes:csv,txt|max:5120']);
        $path = $r->file('arquivo')->store('imports');
        $rows = $this->readCsv(storage_path('app/'.$path));
        $s=0; $e=[];
        foreach ($rows as $i => $row) {
            $nome  = trim($row['nome']??$row[0]??'');
            $tipo  = trim($row['tipo']??$row[1]??'');
            $ca    = trim($row['numero_ca']??$row[2]??'');
            if (!$nome||!$tipo){$e[]="Linha ".($i+2).": nome e tipo obrigatórios.";continue;}
            try{EPI::firstOrCreate(['nome'=>$nome,'tipo'=>$tipo],['numero_ca'=>$ca,'fornecedor'=>$row['fornecedor']??'','status'=>'Ativo']);$s++;}catch(\Exception $ex){$e[]="Linha ".($i+2).": ".$ex->getMessage();}
        }
        return redirect()->route('importacao.index')->with('success',"$s EPI(s) importado(s). ".count($e)." erro(s).");
    }

    public function importarAsos(Request $r) {
        $r->validate(['arquivo' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);
        $ext      = strtolower($r->file('arquivo')->getClientOriginalExtension());
        $path     = $r->file('arquivo')->store('imports');
        $fullPath = storage_path('app/' . $path);
        $rows     = in_array($ext, ['xlsx','xls']) ? $this->readXlsx($fullPath) : $this->readCsv($fullPath);

        $s = 0; $erros = []; $ok = [];

        foreach ($rows as $i => $row) {
            $linha     = $i + 2;
            $cpf       = preg_replace('/\D/', '', $row['cpf'] ?? $row[1] ?? '');
            $dataExame = trim($row['data_exame'] ?? $row[2] ?? '');
            $tipo      = strtolower(trim(str_replace(' ', '_', $row['tipo'] ?? $row[3] ?? 'periodico')));
            $clinicaNm = trim($row['clinica'] ?? $row[4] ?? '');
            $resultado = strtolower(str_replace(' ', '_', trim($row['resultado'] ?? $row[5] ?? 'pendente')));
            $dataVenc  = trim($row['data_vencimento'] ?? $row[6] ?? '');

            if (!$cpf) { $erros[] = "Linha $linha: CPF obrigatório."; continue; }

            $colaborador = Colaborador::withoutTenant()->where('cpf', $cpf)->first();
            if (!$colaborador) { $erros[] = "Linha $linha: colaborador com CPF $cpf não encontrado."; continue; }

            $dataExameFmt = $this->parseData($dataExame);
            if (!$dataExameFmt) { $erros[] = "Linha $linha (CPF $cpf): data_exame '$dataExame' inválida."; continue; }

            $tiposValidos = ['admissional','periodico','retorno_trabalho','mudanca_funcao','demissional','monitoracao_pontual'];
            if (!in_array($tipo, $tiposValidos)) $tipo = 'periodico';

            $resultadosValidos = ['apto','inapto','apto_restricoes','pendente'];
            if (!in_array($resultado, $resultadosValidos)) $resultado = 'pendente';

            $clinicaId = null;
            if ($clinicaNm) {
                $clinica = Clinica::where('nome', 'ilike', "%$clinicaNm%")->first();
                if ($clinica) $clinicaId = $clinica->id;
            }

            $dataVencFmt = $dataVenc ? $this->parseData($dataVenc) : null;

            // Se não informou vencimento, calcula com base na função (periodicidade_aso_dias)
            if (!$dataVencFmt && $colaborador->funcao && $colaborador->funcao->periodicidade_aso_dias) {
                $dataVencFmt = \Carbon\Carbon::parse($dataExameFmt)
                    ->addDays($colaborador->funcao->periodicidade_aso_dias)
                    ->format('Y-m-d');
            }

            try {
                ASO::withoutTenant()->create([
                    'colaborador_id'  => $colaborador->id,
                    'empresa_id'      => $colaborador->empresa_id,
                    'tipo'            => $tipo,
                    'data_exame'      => $dataExameFmt,
                    'data_vencimento' => $dataVencFmt,
                    'resultado'       => $resultado,
                    'clinica_id'      => $clinicaId,
                    'clinica_nome'    => $clinicaId ? null : ($clinicaNm ?: null),
                    'status_logistico'=> $resultado === 'pendente' ? 'pendente' : 'realizado',
                ]);
                $ok[] = "Linha $linha: ASO de {$colaborador->nome} ($tipo / $dataExameFmt) importado.";
                $s++;
            } catch (\Exception $ex) {
                $erros[] = "Linha $linha (CPF $cpf): " . $ex->getMessage();
            }
        }

        return redirect()->route('importacao.index')
            ->with('importResult', ['sucesso' => $s, 'erros' => $erros, 'ok' => $ok, 'total' => count($rows), 'tipo' => 'asos']);
    }

    public function importarFuncoes(Request $r) {
        $r->validate(['arquivo' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);
        $ext      = strtolower($r->file('arquivo')->getClientOriginalExtension());
        $path     = $r->file('arquivo')->store('imports');
        $fullPath = storage_path('app/'.$path);
        $rows     = in_array($ext, ['xlsx','xls']) ? $this->readXlsx($fullPath) : $this->readCsv($fullPath);

        $s = 0; $erros = []; $ok = [];

        foreach ($rows as $i => $row) {
            $linha      = $i + 2;
            $nomeFuncao = trim($row['nome_funcao'] ?? $row[0] ?? '');
            $cnpj       = preg_replace('/\D/', '', $row['cnpj_empresa'] ?? $row[1] ?? '');
            $nomeSetor  = trim($row['nome_setor']  ?? $row[2] ?? '');
            $cbo        = trim($row['cbo']          ?? $row[3] ?? '');
            $descricao  = trim($row['descricao']    ?? $row[4] ?? '');

            if (!$nomeFuncao || !$cnpj) {
                $erros[] = "Linha $linha: nome_funcao e cnpj_empresa são obrigatórios.";
                continue;
            }

            $empresa = Empresa::where('cnpj', $cnpj)->first();
            if (!$empresa) {
                $erros[] = "Linha $linha ($nomeFuncao): empresa CNPJ $cnpj não encontrada.";
                continue;
            }

            // Busca a função — filtra por setor se informado
            $q = Funcao::withoutTenant()->where('empresa_id', $empresa->id)
                ->where('nome', $nomeFuncao);
            if ($nomeSetor) {
                $setor = Setor::withoutTenant()
                    ->where('empresa_id', $empresa->id)
                    ->where('nome', $nomeSetor)
                    ->first();
                if ($setor) {
                    $q->where('setor_id', $setor->id);
                }
            }
            $funcao = $q->first();

            if (!$funcao) {
                $erros[] = "Linha $linha: função \"$nomeFuncao\"" . ($nomeSetor ? " no setor \"$nomeSetor\"" : '') . " não encontrada em {$empresa->nome_display}.";
                continue;
            }

            // Atualiza apenas os campos fornecidos
            $update = [];
            if ($cbo !== '')       $update['cbo']       = $cbo;
            if ($descricao !== '') $update['descricao']  = $descricao;

            if (empty($update)) {
                $erros[] = "Linha $linha ($nomeFuncao): nenhum dado para atualizar (cbo e descricao estão vazios).";
                continue;
            }

            try {
                $funcao->update($update);
                $partes = [];
                if (isset($update['cbo']))       $partes[] = "CBO={$update['cbo']}";
                if (isset($update['descricao'])) $partes[] = 'descricao atualizada';
                $ok[]  = "Linha $linha: \"{$funcao->nome}\" atualizado (" . implode(', ', $partes) . ")";
                $s++;
            } catch (\Exception $ex) {
                $erros[] = "Linha $linha ($nomeFuncao): " . $ex->getMessage();
            }
        }

        return redirect()->route('importacao.index')
            ->with('importResult', ['sucesso'=>$s,'erros'=>$erros,'ok'=>$ok,'total'=>count($rows),'tipo'=>'funcoes']);
    }

    /** Gera CSV já preenchido com as funções da empresa/setores selecionados */
    public function modeloFuncoes(Request $r) {
        $r->validate(['empresa_id' => 'required|exists:empresas,id']);

        $empresa = Empresa::findOrFail($r->empresa_id);
        $cnpj    = $empresa->cnpj;

        $q = Funcao::withoutTenant()
            ->with('setor')
            ->where('empresa_id', $empresa->id)
            ->orderBy('setor_id')
            ->orderBy('nome');

        if ($r->filled('setor_ids')) {
            $q->whereIn('setor_id', (array) $r->setor_ids);
        }

        $funcoes = $q->get();

        $nomeArquivo = 'funcoes_' . preg_replace('/\W+/', '_', strtolower($empresa->nome_display)) . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: no-cache');
        echo "\xEF\xBB\xBF"; // BOM UTF-8 para Excel abrir corretamente

        $out = fopen('php://output', 'w');
        fputcsv($out, ['nome_funcao', 'cnpj_empresa', 'nome_setor', 'cbo', 'descricao'], ';');

        foreach ($funcoes as $f) {
            fputcsv($out, [
                $f->nome,
                $cnpj,
                $f->setor?->nome ?? '',
                $f->cbo ?? '',
                $f->descricao ?? '',
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function modelo(string $tipo) {
        $modelos = [
            'colaboradores' => ['nome *','cpf *','cnpj_empresa *','nome_setor *','nome_funcao *','data_nascimento (AAAA-MM-DD) *','sexo (M/F) *','data_admissao *','status','matricula','cbo','escolaridade','pis','telefone','email'],
            'epis'          => ['nome *','tipo *','numero_ca','validade_ca','fornecedor','fabricante','vida_util_dias','estoque_minimo','custo_unitario'],
            'funcoes'       => ['nome_funcao *','cnpj_empresa *','nome_setor','cbo','descricao'],
            'asos'          => ['cpf *','data_exame (AAAA-MM-DD) *','tipo (admissional/periodico/demissional/retorno_trabalho/mudanca_funcao) *','clinica','resultado (apto/inapto/apto_restricoes/pendente)','data_vencimento (AAAA-MM-DD)'],
        ];
        if (!isset($modelos[$tipo])) abort(404);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="modelo_'.$tipo.'.csv"');
        echo "\xEF\xBB\xBF";
        echo implode(';',$modelos[$tipo])."\n";
        if ($tipo==='colaboradores') echo "João da Silva;12345678901;12345678000100;Produção;Operador;1990-05-15;M;2023-01-10;Contratado;E001;7171-10;Ensino Médio Completo;12345678901;(11)99999;joao@email.com\n";
        if ($tipo==='asos') {
            echo "12345678901;2024-03-15;periodico;Clínica Saúde;apto;2025-03-15\n";
            echo "98765432100;2024-01-10;admissional;Clínica Central;apto;\n";
        }
        if ($tipo==='funcoes') {
            echo "Operador de Prensa;12345678000100;Produção;7171-10;Responsável pela operação de prensas hidráulicas conforme NR12\n";
            echo "Soldador;12345678000100;Manutenção;7243-35;Executa soldas MIG/TIG em estruturas metálicas\n";
        }
        exit;
    }

    private function processarColaboradores(array $rows): array {
        $s=0; $erros=[]; $avisos=[]; $ok=[];
        foreach ($rows as $i => $row) {
            $linha = $i + 2;
            $nome  = trim($row['nome']??$row[0]??'');
            $cpf   = preg_replace('/\D/','',$row['cpf']??$row[1]??'');
            $cnpj  = preg_replace('/\D/','',$row['cnpj_empresa']??$row[2]??'');
            $setor = trim($row['nome_setor']??$row[3]??'');
            $funcao= trim($row['nome_funcao']??$row[4]??'');
            $nasc  = trim($row['data_nascimento']??$row[5]??'');
            $sexo  = strtoupper(substr(trim($row['sexo']??$row[6]??'M'),0,1));
            $adm   = trim($row['data_admissao']??$row[7]??'');

            // Validações com mensagem específica
            $campos = [];
            if (!$nome)   $campos[] = 'nome';
            if (!$cpf)    $campos[] = 'cpf';
            if (!$cnpj)   $campos[] = 'cnpj_empresa';
            if (!$setor)  $campos[] = 'nome_setor';
            if (!$funcao) $campos[] = 'nome_funcao';
            if (!$nasc)   $campos[] = 'data_nascimento';
            if (!$adm)    $campos[] = 'data_admissao';
            if ($campos) { $erros[] = "Linha $linha: campo(s) obrigatório(s) vazio(s) → ".implode(', ',$campos); continue; }

            if (strlen($cpf)!==11) { $erros[] = "Linha $linha ($nome): CPF '$cpf' inválido (deve ter 11 dígitos, recebeu ".strlen($cpf).")"; continue; }

            $empresa = Empresa::where('cnpj',$cnpj)->first();
            if (!$empresa) { $erros[] = "Linha $linha ($nome): empresa com CNPJ '$cnpj' não encontrada no sistema"; continue; }

            if (Colaborador::withoutTenant()->where('cpf',$cpf)->exists()) {
                $erros[] = "Linha $linha ($nome): CPF $cpf já cadastrado — pulado";
                continue;
            }

            $nascFmt = $this->parseData($nasc);
            $admFmt  = $this->parseData($adm);
            if (!$nascFmt) { $erros[] = "Linha $linha ($nome): data_nascimento '$nasc' inválida (use AAAA-MM-DD ou DD/MM/AAAA)"; continue; }
            if (!$admFmt)  { $erros[] = "Linha $linha ($nome): data_admissao '$adm' inválida (use AAAA-MM-DD ou DD/MM/AAAA)"; continue; }

            try {
                $setorObj  = Setor::withoutTenant()->firstOrCreate(['empresa_id'=>$empresa->id,'nome'=>$setor]);
                $funcaoObj = Funcao::withoutTenant()->firstOrCreate(['empresa_id'=>$empresa->id,'setor_id'=>$setorObj->id,'nome'=>$funcao]);
                Colaborador::withoutTenant()->create([
                    'empresa_id'=>$empresa->id,'setor_id'=>$setorObj->id,'funcao_id'=>$funcaoObj->id,
                    'nome'=>$nome,'cpf'=>$cpf,
                    'pis'=>preg_replace('/\D/','',$row['pis']??''),
                    'matricula'=>trim($row['matricula']??''),
                    'cbo'=>trim($row['cbo']??''),
                    'data_nascimento'=>$nascFmt,
                    'sexo'=>in_array($sexo,['M','F'])?$sexo:'M',
                    'data_admissao'=>$admFmt,
                    'status'=>trim($row['status']??'Contratado')?:'Contratado',
                    'escolaridade'=>trim($row['escolaridade']??''),
                    'telefone'=>$row['telefone']??'',
                    'email'=>$row['email']??'',
                ]);
                $ok[] = "Linha $linha: $nome importado (Setor: $setor / Função: $funcao)";
                $s++;
            } catch(\Exception $ex) {
                $erros[] = "Linha $linha ($nome): ".$ex->getMessage();
            }
        }
        return ['sucesso'=>$s,'erros'=>$erros,'ok'=>$ok,'total'=>count($rows)];
    }

    private function readCsv(string $path): array {
        $rows=[]; $h=null;
        $content = file_get_contents($path);
        // Remove BOM UTF-8
        $content = ltrim($content, "\xEF\xBB\xBF");
        // Tenta converter de Latin-1/Windows-1252 para UTF-8 se não for UTF-8 válido
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        // Normaliza quebras de linha
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        $tmpFile = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tmpFile, $content);
        if (($f = fopen($tmpFile, 'r')) !== false) {
            while (($row = fgetcsv($f, 0, ';')) !== false) {
                $row = array_map(fn($v) => trim($v ?? ''), $row);
                if (!$h) {
                    // Remove BOM do primeiro header caso ainda exista
                    $row[0] = ltrim($row[0], "\xEF\xBB\xBF\x00");
                    $h = array_map('trim', $row);
                } elseif (count($row) > 1) {
                    $rows[] = count($h) === count($row) ? array_combine($h, $row) : $row;
                }
            }
            fclose($f);
        }
        unlink($tmpFile);
        return $rows;
    }

    private function readXlsx(string $path): array {
        $rows=[];$zip=new \ZipArchive;
        if($zip->open($path)!==true) return $rows;
        $xml=$zip->getFromName('xl/worksheets/sheet1.xml');
        $shared=$zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        if(!$xml) return $rows;
        $strings=[];
        if($shared){preg_match_all('/<t(?:\s[^>]*)?>([^<]*)<\/t>/u',$shared,$m);$strings=$m[1];}
        $headers=null;
        preg_match_all('/<row[^>]*>(.*?)<\/row>/s',$xml,$rowMatches);
        foreach($rowMatches[1] as $rowXml){
            $cells=[];
            preg_match_all('/<c\s([^>]*)>(.*?)<\/c>/s',$rowXml,$cellMatches,PREG_SET_ORDER);
            foreach($cellMatches as $cell){
                $attrs=$cell[1];$inner=$cell[2];
                $t='';if(preg_match('/t="([^"]*)"/',$attrs,$tm))$t=$tm[1];
                $v='';if(preg_match('/<v>([^<]*)<\/v>/',$inner,$vm))$v=$vm[1];
                if($t==='s')$v=$strings[(int)$v]??'';
                elseif($t==='inlineStr'){preg_match('/<t>([^<]*)<\/t>/',$inner,$tm2);$v=$tm2[1]??'';}
                $cells[]=html_entity_decode(trim($v));
            }
            if(!$headers)$headers=$cells;
            else $rows[]=count($headers)===count($cells)?array_combine($headers,$cells):$cells;
        }
        return $rows;
    }

    private function parseData(string $d): ?string {
        if(!$d) return null;
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)) return $d;
        if(preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/',$d,$m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
        try{return (new \DateTime($d))->format('Y-m-d');}catch(\Exception $ex){return null;}
    }
}
