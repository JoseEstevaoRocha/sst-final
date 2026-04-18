<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaEPI extends BaseModel {
    protected $table = 'entregas_epi';
    protected $fillable = ['empresa_id','colaborador_id','epi_id','quantidade','tamanho','data_entrega','data_prevista_troca','responsavel','observacoes','status'];
    protected function casts(): array { return ['data_entrega'=>'date','data_prevista_troca'=>'date']; }
    public function colaborador(): BelongsTo { return $this->belongsTo(Colaborador::class); }
    public function epi(): BelongsTo { return $this->belongsTo(EPI::class, 'epi_id'); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
}
