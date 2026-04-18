<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsToMany};

class EPI extends Model {
    protected $table = 'epis';
    protected $fillable = ['nome','descricao','tipo','numero_ca','validade_ca','ca_situacao','fornecedor','fabricante','marca','vida_util_dias','estoque_minimo','unidade','custo_unitario','status','tem_tamanho'];
    protected function casts(): array { return ['validade_ca'=>'date','custo_unitario'=>'decimal:2','tem_tamanho'=>'boolean']; }
    public function estoques(): HasMany { return $this->hasMany(EPIEstoque::class, 'epi_id'); }
    public function entregas(): HasMany { return $this->hasMany(EntregaEPI::class, 'epi_id'); }
    public function tamanhos(): BelongsToMany { return $this->belongsToMany(Tamanho::class, 'epi_tamanhos'); }
    public function scopeAtivos($q) { return $q->where('status','Ativo'); }
    public function getEstoqueEmpresaAttribute(): int {
        if (!app()->bound('tenant_id')) return 0;
        return $this->estoques()->where('empresa_id', app('tenant_id'))->value('quantidade') ?? 0;
    }
}
