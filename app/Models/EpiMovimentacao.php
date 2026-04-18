<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EpiMovimentacao extends Model {
    protected $table = 'epi_movimentacoes';
    protected $fillable = ['epi_id','empresa_id','tipo','quantidade','motivo','usuario','nf_entrada_id'];

    public function epi(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EPI::class, 'epi_id');
    }

    public function nfEntrada(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NfEntrada::class, 'nf_entrada_id');
    }
}
