<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController, DashboardController, EmpresaController,
    ColaboradorController, SetorController, FuncaoController,
    ASOController, EPIController, UniformeController, TamanhoController,
    GHEController, RiscoController, MaquinaController, ManutencaoController,
    ExtintorController, BrigadaController, CipaController,
    WhatsAppController, ClinicaController, FichaController,
    ImportacaoController, RelatorioController, ConfigController,
    ApiController, ExameClinicoController, MedicoController,
    BackupController, ExportacaoController, NfEntradaController,
    FornecedorController
};

// ── AUTENTICAÇÃO ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',  [AuthController::class, 'login'])->name('login.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ── ÁREA PROTEGIDA ────────────────────────────────────────────────────────
Route::middleware(['auth', 'tenant'])->group(function () {

    // Dashboard
    Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/dashboard/alertas', [DashboardController::class, 'alertas'])->name('dashboard.alertas');

    // ── ORGANIZACIONAL ────────────────────────────────────────────
    Route::middleware('permission:empresas.view')->group(function () {
        Route::resource('empresas', EmpresaController::class);
    });

    Route::middleware('permission:colaboradores.view')->group(function () {
        Route::resource('colaboradores', ColaboradorController::class)->parameters(['colaboradores' => 'colaborador']);
        Route::delete('/colaboradores/bulk/destroy', [ColaboradorController::class, 'bulkDestroy'])->name('colaboradores.bulk-destroy')->middleware('permission:colaboradores.delete');
        Route::get('/colaboradores/{colaborador}/historico', [ColaboradorController::class, 'historico'])->name('colaboradores.historico');
        Route::post('/colaboradores/{colaborador}/demitir', [ColaboradorController::class, 'demitir'])->name('colaboradores.demitir');
        Route::post('/colaboradores/{colaborador}/resolver-ppp', [ColaboradorController::class, 'resolverAlerta'])->name('colaboradores.resolver-ppp');
    });

    Route::resource('setores', SetorController::class)->parameters(['setores' => 'setor']);
    Route::resource('funcoes', FuncaoController::class)->parameters(['funcoes' => 'funcao']);

    // ── EXAMES CLÍNICOS ───────────────────────────────────────────
    Route::resource('exames-clinicos', ExameClinicoController::class)->parameters(['exames-clinicos' => 'exame']);
    Route::post('/exames-clinicos/atribuir-em-lote', [ExameClinicoController::class, 'atribuirEmLote'])->name('exames-clinicos.lote');
    // Exames do Setor
    Route::get('/setores/{setor}/exames',                    [ExameClinicoController::class, 'setorExames'])->name('setores.exames');
    Route::post('/setores/{setor}/exames',                   [ExameClinicoController::class, 'setorAddExame'])->name('setores.exames.add');
    Route::delete('/setores/{setor}/exames/{exame}',         [ExameClinicoController::class, 'setorRemoveExame'])->name('setores.exames.remove');
    // Exames da Função
    Route::get('/funcoes/{funcao}/exames',                   [ExameClinicoController::class, 'funcaoExames'])->name('funcoes.exames');
    Route::post('/funcoes/{funcao}/exames',                  [ExameClinicoController::class, 'funcaoAddExame'])->name('funcoes.exames.add');
    Route::post('/funcoes/{funcao}/exames/importar-setor',   [ExameClinicoController::class, 'funcaoImportarSetor'])->name('funcoes.exames.importar');
    Route::delete('/funcoes/{funcao}/exames/{exame}',        [ExameClinicoController::class, 'funcaoRemoveExame'])->name('funcoes.exames.remove');

    // ── SAÚDE OCUPACIONAL ─────────────────────────────────────────
    Route::middleware('permission:asos.view')->group(function () {
        Route::get('/asos/vencidos',             [ASOController::class, 'vencidos'])->name('asos.vencidos');
        Route::get('/asos/a-vencer',             [ASOController::class, 'aVencer'])->name('asos.a-vencer');
        Route::get('/asos/historico',            [ASOController::class, 'historico'])->name('asos.historico');
        Route::get('/asos/agendamento',          [ASOController::class, 'agendamento'])->name('asos.agendamento');
        Route::get('/asos/relatorio-clinica',    [ASOController::class, 'relatorioClinica'])->name('asos.relatorio-clinica');
        Route::post('/asos/agendar-lote',        [ASOController::class, 'agendarLote'])->name('asos.agendar-lote');
        Route::resource('asos', ASOController::class);
        Route::post('/asos/{aso}/logistica',         [ASOController::class, 'updateLogistica'])->name('asos.logistica');
        Route::post('/asos/{aso}/agendar',           [ASOController::class, 'agendar'])->name('asos.agendar');
        Route::post('/asos/{aso}/registrar-resultado',[ASOController::class, 'registrarResultado'])->name('asos.registrar-resultado');
        Route::post('/asos/{aso}/whatsapp',           [ASOController::class, 'whatsappAso'])->name('asos.whatsapp');
    });

    Route::resource('clinicas', ClinicaController::class);

    // WhatsApp
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/',                          [WhatsAppController::class, 'index'])->name('index');
        Route::post('/preparar',                 [WhatsAppController::class, 'preparar'])->name('preparar');
        Route::post('/{msg}/enviar',             [WhatsAppController::class, 'enviar'])->name('enviar');
        Route::get('/{msg}/url',                 [WhatsAppController::class, 'getUrl'])->name('url');
        Route::post('/{msg}/resposta',           [WhatsAppController::class, 'resposta'])->name('resposta');
        Route::post('/{msg}/concluir',           [WhatsAppController::class, 'concluir'])->name('concluir');
        Route::get('/config',                    [WhatsAppController::class, 'config'])->name('config');
        Route::post('/config',                   [WhatsAppController::class, 'saveConfig'])->name('config.save');
    });

    // ── GHE & RISCOS ──────────────────────────────────────────────
    Route::middleware('permission:ghe.view')->group(function () {
        Route::resource('ghes', GHEController::class);
        Route::post('/ghes/{ghe}/riscos',        [GHEController::class, 'addRisco'])->name('ghes.riscos.add');
        Route::delete('/ghes/{ghe}/riscos/{risco}',[GHEController::class, 'removeRisco'])->name('ghes.riscos.remove');
        Route::get('/gro/matriz',                [GHEController::class, 'matriz'])->name('gro.matriz');
    });
    Route::resource('riscos', RiscoController::class);

    // ── EPIs ──────────────────────────────────────────────────────
    Route::middleware('permission:epis.view')->group(function () {
        Route::get('/epis/dashboard',        [EPIController::class, 'dashboard'])->name('epis.dashboard');
        Route::get('/epis/grade',            [EPIController::class, 'grade'])->name('epis.grade');
        Route::get('/epis/entregas',         [EPIController::class, 'entregas'])->name('epis.entregas');
        Route::post('/epis/entregas',        [EPIController::class, 'storeEntrega'])->name('epis.entregas.store');
        Route::get('/epis/validade',         [EPIController::class, 'validade'])->name('epis.validade');
        Route::get('/epis/ficha/{colaborador}',[EPIController::class,'ficha'])->name('epis.ficha');
        Route::resource('epis', EPIController::class);
        Route::post('/epis/{epi}/movimentar',[EPIController::class, 'movimentar'])->name('epis.movimentar');
        Route::get('/epis/buscar',           [EPIController::class, 'buscar'])->name('epis.buscar');

        // ── Notas Fiscais de Entrada ───────────────────────────────────────
        Route::get('/nf-entradas',                       [NfEntradaController::class, 'index'])->name('nf-entradas.index');
        Route::get('/nf-entradas/create',                [NfEntradaController::class, 'create'])->name('nf-entradas.create');
        Route::post('/nf-entradas',                      [NfEntradaController::class, 'store'])->name('nf-entradas.store');
        Route::get('/nf-entradas/buscar-fornecedor',     [NfEntradaController::class, 'buscarFornecedor'])->name('nf-entradas.buscar-fornecedor');
        Route::post('/nf-entradas/parse-xml',            [NfEntradaController::class, 'parseXml'])->name('nf-entradas.parse-xml');
        Route::get('/nf-entradas/{nfEntrada}',           [NfEntradaController::class, 'show'])->name('nf-entradas.show');
        Route::post('/nf-entradas/{nfEntrada}/cancelar', [NfEntradaController::class, 'cancelar'])->name('nf-entradas.cancelar');

        Route::resource('fornecedores', FornecedorController::class)->parameters(['fornecedores' => 'fornecedor']);
    });

    // ── UNIFORMES ─────────────────────────────────────────────────
    Route::middleware('permission:uniformes.view')->group(function () {
        Route::get('/uniformes/entregas',    [UniformeController::class, 'entregas'])->name('uniformes.entregas');
        Route::post('/uniformes/entregas',   [UniformeController::class, 'storeEntrega'])->name('uniformes.entregas.store');
        Route::get('/uniformes/grade',       [UniformeController::class, 'grade'])->name('uniformes.grade');
        Route::get('/uniformes/ficha/{colaborador}',[UniformeController::class,'ficha'])->name('uniformes.ficha');
        Route::resource('uniformes', UniformeController::class);
        Route::post('/uniformes/{uniforme}/estoque', [UniformeController::class, 'updateEstoque'])->name('uniformes.estoque');
    });
    Route::resource('tamanhos', TamanhoController::class);
    Route::post('/tamanhos/seed', [TamanhoController::class, 'seed'])->name('tamanhos.seed');

    // ── MÁQUINAS NR12 ─────────────────────────────────────────────
    Route::middleware('permission:maquinas.view')->group(function () {
        Route::get('/manutencoes',                   [ManutencaoController::class, 'geral'])->name('manutencoes.index');
        Route::post('/manutencoes',                  [ManutencaoController::class, 'geralStore'])->name('manutencoes.geral.store');
        Route::post('/manutencoes/lote',             [ManutencaoController::class, 'geralStoreLote'])->name('manutencoes.geral.lote');
        Route::get('/manutencoes/modelo-csv',        [ManutencaoController::class, 'modeloCsv'])->name('manutencoes.modelo-csv');
        Route::post('/manutencoes/importar',         [ManutencaoController::class, 'importar'])->name('manutencoes.importar');
        Route::resource('maquinas', MaquinaController::class);
        Route::resource('maquinas.manutencoes', ManutencaoController::class)->shallow()->parameters(['manutencoes' => 'manutencao']);
        Route::get('/maquinas/{maquina}/checklist', [MaquinaController::class, 'checklist'])->name('maquinas.checklist');
        Route::get('/mecanicos',                        [MaquinaController::class, 'mecanicos'])->name('mecanicos.index');
        Route::post('/mecanicos/{colaborador}/add',     [MaquinaController::class, 'mecanicoAdd'])->name('mecanicos.add');
        Route::delete('/mecanicos/{colaborador}/remove',[MaquinaController::class, 'mecanicoRemove'])->name('mecanicos.remove');
    });

    // ── EMERGÊNCIA ────────────────────────────────────────────────
    Route::middleware('permission:emergencia.view')->group(function () {
        Route::resource('extintores', ExtintorController::class);
        Route::post('/extintores/{extintor}/inspecao', [ExtintorController::class, 'inspecao'])->name('extintores.inspecao');
        Route::get('/brigada',               [BrigadaController::class, 'index'])->name('brigada.index');
        Route::get('/brigada/dashboard',     [BrigadaController::class, 'dashboard'])->name('brigada.dashboard');
        Route::post('/brigada',              [BrigadaController::class, 'store'])->name('brigada.store');
        Route::post('/brigada/lote',         [BrigadaController::class, 'storeLote'])->name('brigada.lote');
        Route::get('/brigada/{id}/edit',     [BrigadaController::class, 'edit'])->name('brigada.edit');
        Route::put('/brigada/{id}',          [BrigadaController::class, 'update'])->name('brigada.update');
        Route::delete('/brigada/{id}',       [BrigadaController::class, 'destroy'])->name('brigada.destroy');
        Route::get('/cipa',              [CipaController::class, 'index'])->name('cipa.index');
        Route::post('/cipa',             [CipaController::class, 'store'])->name('cipa.store');
        Route::delete('/cipa/{id}',      [CipaController::class, 'destroy'])->name('cipa.destroy');
    });

    // ── FICHA DO FUNCIONÁRIO ──────────────────────────────────────
    Route::get('/ficha',                      [FichaController::class, 'index'])->name('ficha.index');
    Route::get('/ficha/{colaborador}',        [FichaController::class, 'show'])->name('ficha.show');
    Route::get('/ficha/{colaborador}/pdf',    [FichaController::class, 'pdf'])->name('ficha.pdf');
    Route::get('/ficha/{colaborador}/epi-pdf',[FichaController::class, 'epiPdf'])->name('ficha.epi-pdf');
    Route::get('/ficha/{colaborador}/uni-pdf',[FichaController::class, 'uniformePdf'])->name('ficha.uni-pdf');

    // ── IMPORTAÇÃO ────────────────────────────────────────────────
    Route::middleware('permission:colaboradores.import')->group(function () {
        Route::get('/importacao',             [ImportacaoController::class, 'index'])->name('importacao.index');
        Route::post('/importacao/colaboradores',[ImportacaoController::class,'importarColaboradores'])->name('importacao.colaboradores');
        Route::post('/importacao/epis',        [ImportacaoController::class,'importarEpis'])->name('importacao.epis');
        Route::post('/importacao/funcoes',          [ImportacaoController::class,'importarFuncoes'])->name('importacao.funcoes');
        Route::post('/importacao/asos',             [ImportacaoController::class,'importarAsos'])->name('importacao.asos');
        Route::get('/importacao/modelo-funcoes',    [ImportacaoController::class,'modeloFuncoes'])->name('importacao.modelo-funcoes');
        Route::get('/importacao/modelo/{tipo}',     [ImportacaoController::class,'modelo'])->name('importacao.modelo');
    });

    // ── RELATÓRIOS ────────────────────────────────────────────────
    Route::middleware('permission:relatorios.view')->group(function () {
        Route::get('/relatorios',              [RelatorioController::class, 'index'])->name('relatorios.index');
        Route::get('/relatorios/asos',         [RelatorioController::class, 'asos'])->name('relatorios.asos');
        Route::get('/relatorios/epis',         [RelatorioController::class, 'epis'])->name('relatorios.epis');
        Route::get('/relatorios/uniformes',    [RelatorioController::class, 'uniformes'])->name('relatorios.uniformes');
        Route::get('/relatorios/extintores',   [RelatorioController::class, 'extintores'])->name('relatorios.extintores');
        Route::get('/relatorios/maquinas',     [RelatorioController::class, 'maquinas'])->name('relatorios.maquinas');
        Route::get('/relatorios/export/{tipo}',[RelatorioController::class, 'export'])->name('relatorios.export')->middleware('permission:relatorios.export');
    });

    // ── CONFIGURAÇÕES ─────────────────────────────────────────────
    Route::middleware('permission:config.view')->group(function () {
        Route::get('/configuracoes',      [ConfigController::class, 'index'])->name('config.index');
        Route::post('/configuracoes',     [ConfigController::class, 'save'])->name('config.save')->middleware('permission:config.edit');
        Route::post('/configuracoes/logo',[ConfigController::class, 'logo'])->name('config.logo')->middleware('permission:config.edit');
        Route::get('/configuracoes/usuarios', [ConfigController::class, 'usuarios'])->name('config.usuarios')->middleware('permission:users.view');
        Route::post('/configuracoes/usuarios',[ConfigController::class, 'storeUsuario'])->name('config.usuarios.store')->middleware('permission:users.create');
        Route::put('/configuracoes/usuarios/{user}',[ConfigController::class, 'updateUsuario'])->name('config.usuarios.update')->middleware('permission:users.edit');
        Route::delete('/configuracoes/usuarios/{user}',[ConfigController::class, 'destroyUsuario'])->name('config.usuarios.destroy')->middleware('permission:users.delete');

        // ── Médicos ────────────────────────────────────────────────
        Route::get('/configuracoes/medicos',           [MedicoController::class, 'index'])->name('medicos.index');
        Route::post('/configuracoes/medicos',          [MedicoController::class, 'store'])->name('medicos.store');
        Route::put('/configuracoes/medicos/{medico}',  [MedicoController::class, 'update'])->name('medicos.update');
        Route::delete('/configuracoes/medicos/{medico}',[MedicoController::class,'destroy'])->name('medicos.destroy');

        // ── Backup ────────────────────────────────────────────────
        Route::get('/configuracoes/backup',                      [BackupController::class, 'index'])->name('backup.index');
        Route::put('/configuracoes/backup',                      [BackupController::class, 'salvarConfig'])->name('backup.config')->middleware('permission:config.edit');
        Route::post('/configuracoes/backup/executar',            [BackupController::class, 'executarManual'])->name('backup.executar')->middleware('permission:config.edit');
        Route::get('/configuracoes/backup/{log}/download',       [BackupController::class, 'download'])->name('backup.download');
        Route::delete('/configuracoes/backup/{log}',             [BackupController::class, 'destroy'])->name('backup.destroy')->middleware('permission:config.edit');
        Route::get('/configuracoes/backup/oauth/autorizar',      [BackupController::class, 'oauthRedirecionar'])->name('backup.oauth-autorizar')->middleware('permission:config.edit');
        Route::get('/configuracoes/backup/oauth/callback',       [BackupController::class, 'oauthCallback'])->name('backup.oauth-callback');
        Route::post('/configuracoes/backup/oauth/revogar',       [BackupController::class, 'revogarOauth'])->name('backup.oauth-revogar')->middleware('permission:config.edit');
    });

    // ── EXPORTAÇÃO ────────────────────────────────────────────────
    Route::middleware('permission:relatorios.view')->group(function () {
        Route::get('/exportacao',         [ExportacaoController::class, 'index'])->name('exportacao.index');
        Route::post('/exportacao/exportar',[ExportacaoController::class, 'exportar'])->name('exportacao.exportar');
    });

    // ── API AJAX ──────────────────────────────────────────────────
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/setores',           [ApiController::class, 'setores'])->name('setores');
        Route::post('/setores/criar',    [ApiController::class, 'criarSetor'])->name('setores.criar');
        Route::get('/funcoes',                  [ApiController::class, 'funcoes'])->name('funcoes');
        Route::post('/funcoes/criar',           [ApiController::class, 'criarFuncao'])->name('funcoes.criar');
        Route::get('/funcoes/{funcao}/exames',  [ApiController::class, 'funcaoExames'])->name('funcoes.exames');
        Route::get('/colaboradores',     [ApiController::class, 'colaboradores'])->name('colaboradores');
        Route::post('/colaboradores/criar',[ApiController::class, 'criarColaborador'])->name('colaboradores.criar');
        Route::get('/maquinas',          [ApiController::class, 'maquinas'])->name('maquinas');
        Route::get('/mecanicos',         [ApiController::class, 'mecanicos'])->name('mecanicos');
        Route::get('/clinicas',          [ApiController::class, 'clinicas'])->name('clinicas');
        Route::get('/search',            [ApiController::class, 'search'])->name('search');
        Route::get('/epi-tamanhos',      [ApiController::class, 'epiTamanhos'])->name('epi-tamanhos');
        Route::get('/ca/{ca}',           [ApiController::class, 'consultarCa'])->name('ca.consultar');
        Route::get('/marcas',            [ApiController::class, 'marcas'])->name('marcas');
        Route::get('/notificacoes',      [ApiController::class, 'notificacoes'])->name('notificacoes');
        Route::get('/dashboard/stats',   [ApiController::class, 'dashboardStats'])->name('dashboard.stats');
        Route::get('/dashboard/charts',  [ApiController::class, 'dashboardCharts'])->name('dashboard.charts');
    });
});
