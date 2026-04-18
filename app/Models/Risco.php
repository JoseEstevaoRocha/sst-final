<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Risco extends Model {
    protected $fillable = ['nome','categoria','descricao','nr_referencia'];
    public function ghes() { return $this->belongsToMany(GHE::class,'ghe_riscos')->withPivot(['probabilidade','severidade','nivel_risco']); }
}
