<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspecaoExtintor extends BaseModel {
    protected $table = 'inspecoes_extintor';
    protected $fillable = ['empresa_id','extintor_id','data_inspecao','responsavel','resultado','observacoes'];
    protected function casts(): array { return ['data_inspecao'=>'date']; }
    public function extintor(): BelongsTo { return $this->belongsTo(Extintor::class); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
}
