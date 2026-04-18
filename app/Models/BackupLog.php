<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $table    = 'backup_logs';
    public    $timestamps = true;

    protected $fillable = [
        'nome_arquivo', 'tamanho_bytes', 'status', 'mensagem',
        'google_drive_id', 'google_drive_ok', 'tipo',
    ];

    protected function casts(): array
    {
        return ['google_drive_ok' => 'boolean'];
    }

    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho_bytes ?? 0;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1)    . ' KB';
        return $bytes . ' B';
    }
}
