<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GHE extends BaseModel {
    protected $table = 'ghes';
    protected $fillable = ['empresa_id','codigo','nome','descricao'];
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function riscos(): BelongsToMany { return $this->belongsToMany(Risco::class,'ghe_riscos')->withPivot(['probabilidade','severidade','nivel_risco','medidas_epc','medidas_epi'])->withTimestamps(); }
    public function setores(): BelongsToMany { return $this->belongsToMany(Setor::class,'ghe_setores'); }
}
