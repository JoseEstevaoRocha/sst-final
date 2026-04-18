<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupConfig extends Model
{
    protected $table = 'backup_configs';

    protected $fillable = [
        'ativo', 'horario', 'retencao', 'pg_dump_path',
        'google_drive_ativo', 'google_drive_pasta_id', 'google_credenciais_json',
        'google_client_id', 'google_client_secret', 'google_refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'ativo'              => 'boolean',
            'google_drive_ativo' => 'boolean',
        ];
    }

    public static function get(): self
    {
        return static::firstOrCreate([], [
            'ativo'        => false,
            'horario'      => '02:00',
            'retencao'     => 7,
            'pg_dump_path' => 'pg_dump',
        ]);
    }
}
