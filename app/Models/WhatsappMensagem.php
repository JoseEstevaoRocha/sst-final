<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMensagem extends BaseModel {
    protected $table = 'whatsapp_mensagens';
    protected $fillable = ['empresa_id','colaborador_id','clinica_id','aso_id','tipo_exame','mensagem_texto','status','data_envio','data_agendada','horario_agendado','usuario_envio'];
    protected function casts(): array { return ['data_envio'=>'datetime','data_agendada'=>'date']; }
    public function colaborador(): BelongsTo { return $this->belongsTo(Colaborador::class); }
    public function clinica(): BelongsTo { return $this->belongsTo(Clinica::class); }
    public function aso(): BelongsTo { return $this->belongsTo(ASO::class); }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
}
