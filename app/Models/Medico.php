<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medico extends BaseModel {
    protected $table = 'medicos';
    protected $fillable = ['nome','crm','especialidade','clinica_id','ativo'];
    protected function casts(): array { return ['ativo' => 'boolean']; }

    public function clinica(): BelongsTo { return $this->belongsTo(Clinica::class); }

    public function getNomeComCrmAttribute(): string {
        return $this->crm ? "{$this->nome} — CRM: {$this->crm}" : $this->nome;
    }

    public function scopeAtivos($q) { return $q->where('ativo', true); }
}
