<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CipaMembro extends BaseModel {
    protected $table = 'cipa_membros';
    protected $fillable = ['empresa_id','colaborador_id','cargo','mandato_inicio','mandato_fim','tipo','ativo'];
    protected function casts(): array { return ['mandato_inicio'=>'date','mandato_fim'=>'date','ativo'=>'boolean']; }
    public function colaborador(): BelongsTo { return $this->belongsTo(Colaborador::class); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
}
