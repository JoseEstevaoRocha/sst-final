<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Funcao extends BaseModel {
    protected $table = 'funcoes';
    protected $fillable = ['empresa_id','setor_id','nome','descricao','cbo','periodicidade_aso_dias'];
    public function setor(): BelongsTo { return $this->belongsTo(Setor::class); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function colaboradores(): HasMany { return $this->hasMany(Colaborador::class); }
    public function exames(): BelongsToMany {
        return $this->belongsToMany(ExameClinico::class, 'funcao_exames', 'funcao_id', 'exame_id')
            ->withPivot(['periodicidade_meses','obrigatorio','origem'])
            ->withTimestamps();
    }
}
