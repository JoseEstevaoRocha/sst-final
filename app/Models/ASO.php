<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ASO extends BaseModel {
    protected $table = 'asos';
    protected $fillable = ['empresa_id','colaborador_id','tipo','data_exame','data_vencimento','data_agendada','horario_agendado','exames_complementares','resultado','clinica_id','clinica_nome','medico_responsavel','status_logistico','observacoes','whatsapp_enviado','novo_setor_id','nova_funcao_id','local_exame'];
    protected function casts(): array { return ['data_exame'=>'date','data_vencimento'=>'date','data_agendada'=>'date','whatsapp_enviado'=>'boolean']; }
    public function colaborador(): BelongsTo { return $this->belongsTo(Colaborador::class); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function clinica(): BelongsTo { return $this->belongsTo(Clinica::class); }
    public function novoSetor(): BelongsTo { return $this->belongsTo(Setor::class, 'novo_setor_id'); }
    public function novaFuncao(): BelongsTo { return $this->belongsTo(Funcao::class, 'nova_funcao_id'); }
    public function getDiasRestantesAttribute(): ?int {
        if (!$this->data_vencimento) return null;
        return Carbon::today()->diffInDays($this->data_vencimento, false);
    }
    public function getSituacaoAttribute(): string {
        if (!$this->data_vencimento) return 'Pendente';
        if ($this->data_vencimento->isPast()) return 'Vencido';
        if ($this->data_vencimento->lte(Carbon::today()->addDays(30))) return 'A Vencer';
        return 'Em Dia';
    }
    public function scopeVencidos($q) { return $q->where('data_vencimento','<',today()); }
    public function scopeAVencer($q, int $dias=30) { return $q->whereBetween('data_vencimento',[today(),today()->addDays($dias)]); }
}
