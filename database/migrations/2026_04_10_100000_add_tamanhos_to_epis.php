<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flag para indicar que o EPI usa grade de tamanhos
        Schema::table('epis', function (Blueprint $table) {
            $table->boolean('tem_tamanho')->default(false)->after('status');
        });

        // Tabela pivot: quais tamanhos estão disponíveis para cada EPI
        Schema::create('epi_tamanhos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_id')->constrained('epis')->cascadeOnDelete();
            $table->foreignId('tamanho_id')->constrained('tamanhos')->cascadeOnDelete();
            $table->unique(['epi_id', 'tamanho_id']);
            $table->timestamps();
        });

        // Adiciona tamanho_id (nullable) em epi_estoques para rastrear estoque por tamanho
        Schema::table('epi_estoques', function (Blueprint $table) {
            $table->foreignId('tamanho_id')->nullable()->after('empresa_id')
                ->constrained('tamanhos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('epi_estoques', function (Blueprint $table) {
            $table->dropForeign(['tamanho_id']);
            $table->dropColumn('tamanho_id');
        });
        Schema::dropIfExists('epi_tamanhos');
        Schema::table('epis', function (Blueprint $table) {
            $table->dropColumn('tem_tamanho');
        });
    }
};
