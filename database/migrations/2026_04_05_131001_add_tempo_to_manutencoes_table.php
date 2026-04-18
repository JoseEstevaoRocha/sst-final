<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('manutencoes', function (Blueprint $table) {
            $table->time('hora_inicio')->nullable()->after('data_manutencao');
            $table->time('hora_fim')->nullable()->after('hora_inicio');
            $table->unsignedInteger('duracao_minutos')->nullable()->after('hora_fim');
        });
    }

    public function down(): void
    {
        Schema::table('manutencoes', function (Blueprint $table) {
            $table->dropColumn(['hora_inicio','hora_fim','duracao_minutos']);
        });
    }
};
