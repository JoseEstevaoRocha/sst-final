<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class NfFornecedor extends BaseModel
{
    protected $table = 'nf_fornecedores';

    protected $fillable = [
        'empresa_id', 'razao_social', 'nome_fantasia', 'cnpj',
        'inscricao_estadual', 'logradouro', 'numero', 'complemento',
        'bairro', 'municipio', 'uf', 'cep', 'telefone', 'email',
    ];

    public function notas(): HasMany
    {
        return $this->hasMany(NfEntrada::class, 'fornecedor_id');
    }

    public function getNomeDisplayAttribute(): string
    {
        return $this->nome_fantasia ?: $this->razao_social;
    }
}
