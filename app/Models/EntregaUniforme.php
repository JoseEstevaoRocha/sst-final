<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaUniforme extends BaseModel {
    protected $table = 'entregas_uniforme';
    protected $fillable = ['empresa_id','colaborador_id','uniforme_id','tamanho_id','quantidade','data_entrega','data_prevista_troca','motivo','responsavel','observacoes'];
    protected function casts(): array { return ['data_entrega'=>'date','data_prevista_troca'=>'date']; }
    public function colaborador(): BelongsTo { return $this->belongsTo(Colaborador::class); }
    public function uniforme(): BelongsTo { return $this->belongsTo(Uniforme::class); }
    public function tamanho(): BelongsTo { return $this->belongsTo(Tamanho::class); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
}
