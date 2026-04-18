<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniformeEstoque extends Model {
    protected $table = 'uniforme_estoques';
    protected $fillable = ['uniforme_id','tamanho_id','quantidade','minimo'];
    public function uniforme(): BelongsTo { return $this->belongsTo(Uniforme::class); }
    public function tamanho(): BelongsTo { return $this->belongsTo(Tamanho::class); }
    public function getBaixoEstoqueAttribute(): bool { return $this->quantidade <= $this->minimo && $this->minimo > 0; }
}
