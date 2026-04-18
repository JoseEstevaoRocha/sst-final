<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setor extends BaseModel {
    protected $table = 'setores';
    protected $fillable = ['empresa_id','nome','descricao'];
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function funcoes(): HasMany { return $this->hasMany(Funcao::class); }
    public function colaboradores(): HasMany { return $this->hasMany(Colaborador::class); }
    public function exames(): BelongsToMany {
        return $this->belongsToMany(ExameClinico::class, 'setor_exames', 'setor_id', 'exame_id')
            ->withPivot(['periodicidade_meses','obrigatorio'])
            ->withTimestamps();
    }
}
