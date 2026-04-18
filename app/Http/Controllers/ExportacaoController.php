<?php
namespace App\Http\Controllers;

use App\Exports\ColaboradoresExport;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportacaoController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $empresas = $user->isSuperAdmin()
            ? Empresa::ativas()->orderBy('razao_social')->get()
            : Empresa::where('id', $user->empresa_id)->get();

        $campos = ColaboradoresExport::CAMPOS;

        return view('exportacao.index', compact('empresas', 'campos'));
    }

    public function exportar(Request $r)
    {
        $user = auth()->user();

        $r->validate([
            'empresa_ids'   => 'required|array|min:1',
            'empresa_ids.*' => 'exists:empresas,id',
            'campos'        => 'required|array|min:1',
            'formato'       => 'required|in:xlsx,csv',
            'status_filtro' => 'required|in:ativos,todos',
            'admissao_de'   => 'nullable|date',
            'admissao_ate'  => 'nullable|date|after_or_equal:admissao_de',
        ]);

        $empresasPermitidas = $user->isSuperAdmin()
            ? $r->empresa_ids
            : array_intersect($r->empresa_ids, [$user->empresa_id]);

        if (empty($empresasPermitidas)) {
            return back()->with('error', 'Sem permissão para exportar as empresas selecionadas.');
        }

        $campos       = array_intersect($r->campos, array_keys(ColaboradoresExport::CAMPOS));
        $multiEmpresa = count($empresasPermitidas) > 1;

        $export = new ColaboradoresExport(
            $campos,
            $empresasPermitidas,
            $multiEmpresa,
            $r->status_filtro,
            $r->admissao_de  ?: null,
            $r->admissao_ate ?: null,
        );

        $nomeArq = 'colaboradores_' . now()->format('Y-m-d_H-i') . '.' . $r->formato;

        if ($r->formato === 'csv') {
            return Excel::download($export, $nomeArq, \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
        }

        return Excel::download($export, $nomeArq);
    }
}
