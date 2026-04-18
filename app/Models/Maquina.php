<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maquina extends BaseModel {
    protected $fillable = ['empresa_id','setor_id','nome','marca','modelo','numero_serie','ano_fabricacao','status','ultima_manutencao','proxima_manutencao','observacoes'];
    protected function casts(): array { return ['ultima_manutencao'=>'date','proxima_manutencao'=>'date']; }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function setor(): BelongsTo { return $this->belongsTo(Setor::class); }
    public function manutencoes(): HasMany { return $this->hasMany(Manutencao::class); }
}
