<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfItem extends Model
{
    protected $table = 'nf_itens';

    protected $fillable = [
        'nf_entrada_id', 'epi_id', 'tamanho_id',
        'codigo_fornecedor', 'nome', 'unidade',
        'quantidade', 'valor_unitario', 'valor_total',
        'lote', 'data_validade',
    ];

    protected function casts(): array
    {
        return [
            'quantidade'    => 'decimal:3',
            'valor_unitario'=> 'decimal:4',
            'valor_total'   => 'decimal:2',
            'data_validade' => 'date',
        ];
    }

    public function nfEntrada(): BelongsTo { return $this->belongsTo(NfEntrada::class, 'nf_entrada_id'); }
    public function epi(): BelongsTo       { return $this->belongsTo(EPI::class, 'epi_id'); }
    public function tamanho(): BelongsTo   { return $this->belongsTo(Tamanho::class); }
}
