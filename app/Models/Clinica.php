<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinica extends Model {
    protected $fillable = ['nome','cnpj','whatsapp','telefone','email','endereco','cidade','estado','responsavel','ativo'];
    protected function casts(): array { return ['ativo'=>'boolean']; }
    public function asos(): HasMany { return $this->hasMany(ASO::class); }
    public function scopeAtivas($q) { return $q->where('ativo',true); }
}
