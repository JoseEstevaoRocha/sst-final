<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends BaseModel {
    protected $table = 'alertas';
    protected $fillable = ['empresa_id','colaborador_id','tipo','titulo','descricao','status','data_prevista','criado_por'];
    protected function casts(): array { return ['data_prevista' => 'date']; }

    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function colaborador(): BelongsTo { return $this->belongsTo(Colaborador::class); }

    public function scopePendentes($q) { return $q->where('status', 'pendente'); }
}
