<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class NfEntrada extends BaseModel
{
    protected $table = 'nf_entradas';

    protected $fillable = [
        'empresa_id', 'fornecedor_id', 'usuario_id',
        'numero', 'serie', 'chave_acesso', 'data_emissao', 'data_entrada',
        'natureza_operacao', 'xml_path',
        'valor_produtos', 'valor_frete', 'valor_desconto', 'valor_total',
        'status', 'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao'    => 'date',
            'data_entrada'    => 'date',
            'valor_produtos'  => 'decimal:2',
            'valor_frete'     => 'decimal:2',
            'valor_desconto'  => 'decimal:2',
            'valor_total'     => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo   { return $this->belongsTo(Empresa::class); }
    public function fornecedor(): BelongsTo { return $this->belongsTo(NfFornecedor::class, 'fornecedor_id'); }
    public function usuario(): BelongsTo   { return $this->belongsTo(User::class, 'usuario_id'); }
    public function itens(): HasMany       { return $this->hasMany(NfItem::class, 'nf_entrada_id'); }
    public function movimentacoes(): HasMany { return $this->hasMany(EpiMovimentacao::class, 'nf_entrada_id'); }
}
