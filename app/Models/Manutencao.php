<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};

class Manutencao extends BaseModel {
    protected $table = 'manutencoes';
    protected $fillable = ['empresa_id','maquina_id','tipo','data_manutencao','hora_inicio','hora_fim','duracao_minutos','descricao','responsavel','custo','proxima_manutencao'];
    protected function casts(): array { return ['data_manutencao'=>'date','proxima_manutencao'=>'date','custo'=>'decimal:2']; }
    public function maquina(): BelongsTo { return $this->belongsTo(Maquina::class); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function responsaveis(): BelongsToMany {
        return $this->belongsToMany(Colaborador::class, 'manutencao_responsaveis', 'manutencao_id', 'colaborador_id');
    }
    public function getNomesResponsaveisAttribute(): string {
        $nomes = $this->responsaveis->pluck('nome')->toArray();
        if ($this->responsavel) $nomes[] = $this->responsavel;
        return implode(', ', array_unique(array_filter($nomes))) ?: '—';
    }
}
