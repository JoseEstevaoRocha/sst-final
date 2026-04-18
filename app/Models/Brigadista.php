<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brigadista extends BaseModel {
    protected $fillable = ['empresa_id','colaborador_id','funcao_brigada','data_inicio','data_validade_cert','ativo'];
    protected function casts(): array { return ['data_inicio'=>'date','data_validade_cert'=>'date','ativo'=>'boolean']; }
    public function colaborador(): BelongsTo { return $this->belongsTo(Colaborador::class); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
}
