<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
Artisan::command('inspire', function () { $this->comment(Inspiring::quote()); })->purpose('Display an inspiring quote');
// Processa agendamentos do dia: demissional, mudança de função, PPP
\Illuminate\Support\Facades\Schedule::command('sst:processar-agendamentos')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/agendamentos.log'));

// Baixa base CAEPI do MTE (ftp.mtps.gov.br) e importa para ca_cache
\Illuminate\Support\Facades\Schedule::command('caepi:sincronizar')
    ->dailyAt('21:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/caepi.log'));

// Backup automático — horário definido nas configurações do sistema
try {
    $backupHorario = \Illuminate\Support\Facades\DB::table('backup_configs')->value('horario') ?? '02:00';
} catch (\Throwable) {
    $backupHorario = '02:00';
}
\Illuminate\Support\Facades\Schedule::call(function () {
    $cfg = \App\Models\BackupConfig::get();
    if (!$cfg->ativo) return;
    app(\App\Services\BackupService::class)->executar('automatico');
})->name('backup:automatico')
 ->dailyAt($backupHorario)
 ->withoutOverlapping()
 ->appendOutputTo(storage_path('logs/backup.log'));

Artisan::command('sst:alertas', function () {
    $vencidos = \App\Models\ASO::where('data_vencimento','<',now())->count();
    $this->info("ASOs vencidos: {$vencidos}");
})->purpose('Verificar alertas SST')->daily();
