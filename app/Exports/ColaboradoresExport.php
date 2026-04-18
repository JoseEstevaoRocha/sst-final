<?php
namespace App\Exports;

use App\Models\Colaborador;
use Maatwebsite\Excel\Concerns\{
    FromCollection, WithHeadings, WithStyles,
    ShouldAutoSize, WithTitle
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ColaboradoresExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public const CAMPOS = [
        'nome'              => 'Nome',
        'cpf'               => 'CPF',
        'rg'                => 'RG',
        'pis'               => 'PIS/PASEP',
        'matricula'         => 'Matrícula',
        'matricula_esocial' => 'Matrícula eSocial',
        'cbo'               => 'CBO',
        'sexo'              => 'Sexo',
        'data_nascimento'   => 'Data de Nascimento',
        'idade'             => 'Idade',
        'escolaridade'      => 'Escolaridade',
        'data_admissao'     => 'Data de Admissão',
        'tempo_empresa'     => 'Tempo de Empresa',
        'data_demissao'     => 'Data de Demissão',
        'status'            => 'Status',
        'setor'             => 'Setor',
        'funcao'            => 'Função',
        'telefone'          => 'Telefone',
        'email'             => 'E-mail',
        'jovem_aprendiz'    => 'Jovem Aprendiz',
        'observacoes'       => 'Observações',
    ];

    private array   $campos;
    private array   $empresaIds;
    private bool    $multiEmpresa;
    private string  $statusFiltro;   // 'ativos' | 'todos'
    private ?string $admissaoDe;
    private ?string $admissaoAte;

    public function __construct(
        array   $campos,
        array   $empresaIds,
        bool    $multiEmpresa  = false,
        string  $statusFiltro  = 'ativos',
        ?string $admissaoDe    = null,
        ?string $admissaoAte   = null
    ) {
        $this->campos        = $campos;
        $this->empresaIds    = $empresaIds;
        $this->multiEmpresa  = $multiEmpresa;
        $this->statusFiltro  = $statusFiltro;
        $this->admissaoDe    = $admissaoDe;
        $this->admissaoAte   = $admissaoAte;
    }

    public function collection()
    {
        $query = Colaborador::with(['empresa', 'setor', 'funcao'])
            ->whereIn('empresa_id', $this->empresaIds);

        if ($this->statusFiltro === 'ativos') {
            $query->where('status', 'Contratado');
        }

        if ($this->admissaoDe) {
            $query->whereDate('data_admissao', '>=', $this->admissaoDe);
        }
        if ($this->admissaoAte) {
            $query->whereDate('data_admissao', '<=', $this->admissaoAte);
        }

        $query->orderBy('nome');

        return $query->get()->map(function (Colaborador $c) {
            $linha = [];

            if ($this->multiEmpresa) {
                $linha['Empresa'] = $c->empresa?->nome_display;
                $linha['CNPJ']    = $c->empresa?->cnpj;
            }

            foreach ($this->campos as $campo) {
                $label = self::CAMPOS[$campo] ?? $campo;
                $linha[$label] = match ($campo) {
                    'cpf'            => $this->formatarCpf($c->cpf),
                    'setor'          => $c->setor?->nome,
                    'funcao'         => $c->funcao?->nome,
                    'idade'          => $c->data_nascimento ? $c->data_nascimento->age : null,
                    'tempo_empresa'  => $this->calcularTempoEmpresa($c->data_admissao),
                    'data_nascimento'=> $c->data_nascimento?->format('d/m/Y'),
                    'data_admissao'  => $c->data_admissao?->format('d/m/Y'),
                    'data_demissao'  => $c->data_demissao?->format('d/m/Y'),
                    'jovem_aprendiz' => $c->jovem_aprendiz ? 'Sim' : 'Não',
                    default          => $c->{$campo} ?? null,
                };
            }

            return $linha;
        });
    }

    public function headings(): array
    {
        $headers = [];

        if ($this->multiEmpresa) {
            $headers[] = 'Empresa';
            $headers[] = 'CNPJ';
        }

        foreach ($this->campos as $campo) {
            $headers[] = self::CAMPOS[$campo] ?? $campo;
        }

        return $headers;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '2C3E50']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Colaboradores';
    }

    private function formatarCpf(?string $cpf): ?string
    {
        if (!$cpf) return null;
        $digits = preg_replace('/\D/', '', $cpf);
        if (strlen($digits) !== 11) return $cpf;
        return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
    }

    private function calcularTempoEmpresa(?Carbon $admissao): ?string
    {
        if (!$admissao) return null;
        $diff  = $admissao->diff(now());
        $anos  = $diff->y;
        $meses = $diff->m;
        if ($anos > 0) return "{$anos} ano(s) e {$meses} mês(es)";
        return "{$meses} mês(es)";
    }
}
