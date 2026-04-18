<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Colaborador extends BaseModel {
    use SoftDeletes;
    protected $table = 'colaboradores';
    protected $fillable = ['empresa_id','setor_id','funcao_id','nome','cpf','rg','pis','matricula','matricula_esocial','cbo','data_nascimento','sexo','data_admissao','data_demissao','demissao_motivo','periodo_experiencia','status','jovem_aprendiz','escolaridade','telefone','email','observacoes'];
    protected function casts(): array { return ['data_nascimento'=>'date','data_admissao'=>'date','data_demissao'=>'date','jovem_aprendiz'=>'boolean','periodo_experiencia'=>'boolean']; }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function setor(): BelongsTo { return $this->belongsTo(Setor::class); }
    public function funcao(): BelongsTo { return $this->belongsTo(Funcao::class); }
    public function asos(): HasMany { return $this->hasMany(ASO::class); }
    public function entregasEpi(): HasMany { return $this->hasMany(EntregaEPI::class); }
    public function entregasUniforme(): HasMany { return $this->hasMany(EntregaUniforme::class); }
    public function getIdadeAttribute(): int { return $this->data_nascimento ? $this->data_nascimento->age : 0; }
    public function getInitialsAttribute(): string {
        $p = explode(' ', $this->nome);
        return strtoupper(substr($p[0],0,1) . (isset($p[1])?substr($p[1],0,1):''));
    }
    public function scopeAtivos($q) { return $q->where('status','Contratado'); }
}
