<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EPIEstoque extends Model {
    protected $table = 'epi_estoques';
    protected $fillable = ['epi_id','empresa_id','tamanho_id','quantidade'];
    public function epi(): BelongsTo { return $this->belongsTo(EPI::class, 'epi_id'); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function tamanho(): BelongsTo { return $this->belongsTo(Tamanho::class); }
}
