<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExameClinico extends Model {
    protected $table = 'exames_clinicos';
    protected $fillable = ['nome','tipo','descricao','obrigatorio_nr'];

    const TIPOS = [
        'audiometria'  => 'Audiometria',
        'laboratorial' => 'Laboratorial',
        'imagem'       => 'Imagem (Rx/US)',
        'clinico'      => 'Clínico',
        'espirometria' => 'Espirometria',
        'ecg'          => 'Eletrocardiograma',
        'outros'       => 'Outros',
    ];

    public function setores(): BelongsToMany {
        return $this->belongsToMany(Setor::class, 'setor_exames', 'exame_id', 'setor_id')
            ->withPivot(['periodicidade_meses','obrigatorio'])
            ->withTimestamps();
    }

    public function funcoes(): BelongsToMany {
        return $this->belongsToMany(Funcao::class, 'funcao_exames', 'exame_id', 'funcao_id')
            ->withPivot(['periodicidade_meses','obrigatorio','origem'])
            ->withTimestamps();
    }
}
