<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model {
    protected $table = 'audit_logs';
    public $timestamps = false;
    protected $fillable = ['user_id','user_name','empresa_id','acao','tabela','registro_id','dados_antes','dados_depois','ip','user_agent','created_at'];
    protected function casts(): array { return ['dados_antes'=>'array','dados_depois'=>'array','created_at'=>'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public static function registrar(string $acao, string $tabela, int $id, array $antes=[], array $depois=[]): void {
        $user = auth()->user();
        static::create([
            'user_id'      => $user?->id,
            'user_name'    => $user?->name ?? 'Sistema',
            'empresa_id'   => $user?->empresa_id,
            'acao'         => $acao,
            'tabela'       => $tabela,
            'registro_id'  => $id,
            'dados_antes'  => $antes,
            'dados_depois' => $depois,
            'ip'           => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'created_at'   => now(),
        ]);
    }
}
