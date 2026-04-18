<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            // Remove FKs antigas (RESTRICT por padrão — bloqueia exclusão)
            $table->dropForeign(['setor_id']);
            $table->dropForeign(['funcao_id']);

            // Torna as colunas nullable para aceitar NULL quando setor/função for excluído
            $table->unsignedBigInteger('setor_id')->nullable()->change();
            $table->unsignedBigInteger('funcao_id')->nullable()->change();

            // Recria FKs com nullOnDelete — ao excluir setor/função, colaborador fica com NULL
            $table->foreign('setor_id')->references('id')->on('setores')->nullOnDelete();
            $table->foreign('funcao_id')->references('id')->on('funcoes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropForeign(['setor_id']);
            $table->dropForeign(['funcao_id']);

            $table->unsignedBigInteger('setor_id')->nullable(false)->change();
            $table->unsignedBigInteger('funcao_id')->nullable(false)->change();

            $table->foreign('setor_id')->references('id')->on('setores');
            $table->foreign('funcao_id')->references('id')->on('funcoes');
        });
    }
};
