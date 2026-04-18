<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de exames clínicos
        Schema::create('exames_clinicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo', 50)->default('clinico'); // audiometria, laboratorial, imagem, clinico, espirometria, ecg, outros
            $table->text('descricao')->nullable();
            $table->string('obrigatorio_nr', 50)->nullable(); // ex: NR-7, NR-15
            $table->timestamps();
        });

        // Exames associados ao setor
        Schema::create('setor_exames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setor_id')->constrained('setores')->cascadeOnDelete();
            $table->foreignId('exame_id')->constrained('exames_clinicos')->cascadeOnDelete();
            $table->unsignedInteger('periodicidade_meses')->nullable(); // null = conforme ASO
            $table->boolean('obrigatorio')->default(true);
            $table->timestamps();
            $table->unique(['setor_id', 'exame_id']);
        });

        // Exames associados à função (herda do setor + adicionais)
        Schema::create('funcao_exames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcao_id')->constrained('funcoes')->cascadeOnDelete();
            $table->foreignId('exame_id')->constrained('exames_clinicos')->cascadeOnDelete();
            $table->unsignedInteger('periodicidade_meses')->nullable();
            $table->boolean('obrigatorio')->default(true);
            $table->string('origem', 20)->default('funcao'); // setor | funcao
            $table->timestamps();
            $table->unique(['funcao_id', 'exame_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcao_exames');
        Schema::dropIfExists('setor_exames');
        Schema::dropIfExists('exames_clinicos');
    }
};
