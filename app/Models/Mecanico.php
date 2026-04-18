<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mecanico extends BaseModel {
    protected $table = 'mecanicos';
    protected $fillable = ['empresa_id', 'colaborador_id'];

    public function colaborador(): BelongsTo { return $this->belongsTo(Colaborador::class); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
}
