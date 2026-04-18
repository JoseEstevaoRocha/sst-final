<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Tamanho extends Model {
    protected $fillable = ['codigo','descricao','ordem'];
    public function scopeOrdenado($q) { return $q->orderBy('ordem'); }
}
