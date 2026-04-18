<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('manutencao_responsaveis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manutencao_id')->constrained('manutencoes')->cascadeOnDelete();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->unique(['manutencao_id', 'colaborador_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('manutencao_responsaveis');
    }
};
