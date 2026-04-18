<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};

class Uniforme extends Model {
    protected $fillable = ['nome','tipo','descricao','fornecedor','custo_unitario','status','empresa_id','estoque_minimo_padrao'];
    protected function casts(): array { return ['custo_unitario'=>'decimal:2']; }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function estoques(): HasMany { return $this->hasMany(UniformeEstoque::class); }
    public function entregas(): HasMany { return $this->hasMany(EntregaUniforme::class); }
    public function scopeAtivos($q) { return $q->where('status','Ativo'); }
}
