<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Extintor extends BaseModel {
    protected $table = 'extintores';
    protected $fillable = ['empresa_id','setor_id','numero_serie','tipo','capacidade','localizacao','ultima_recarga','proxima_recarga','ultimo_teste_hidrostatico','proximo_teste_hidrostatico','status'];
    protected function casts(): array { return ['ultima_recarga'=>'date','proxima_recarga'=>'date','ultimo_teste_hidrostatico'=>'date','proximo_teste_hidrostatico'=>'date']; }
    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function setor(): BelongsTo { return $this->belongsTo(Setor::class); }
    public function inspecoes(): HasMany { return $this->hasMany(InspecaoExtintor::class); }
    public function getStatusCalculadoAttribute(): string {
        if ($this->proxima_recarga && $this->proxima_recarga->isPast()) return 'vencido';
        return $this->status ?? 'regular';
    }
}
