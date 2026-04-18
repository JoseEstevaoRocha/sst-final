<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // ── Extensão da tabela asos ───────────────────────────────────────
        Schema::table('asos', function (Blueprint $table) {
            $table->unsignedBigInteger('novo_setor_id')->nullable()->after('clinica_id');
            $table->unsignedBigInteger('nova_funcao_id')->nullable()->after('novo_setor_id');
            $table->string('local_exame', 20)->default('clinica')->after('nova_funcao_id')
                  ->comment('clinica | in_company');
            $table->foreign('novo_setor_id')->references('id')->on('setores')->nullOnDelete();
            $table->foreign('nova_funcao_id')->references('id')->on('funcoes')->nullOnDelete();
        });

        // ── Tabela de alertas / tarefas ───────────────────────────────────
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->unsignedBigInteger('colaborador_id')->nullable();
            $table->string('tipo', 50)->default('geral')
                  ->comment('ppp | vencimento_aso | mudanca_funcao | geral');
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('status', 20)->default('pendente')
                  ->comment('pendente | resolvido | cancelado');
            $table->date('data_prevista')->nullable();
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->nullOnDelete();
            $table->foreign('colaborador_id')->references('id')->on('colaboradores')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('alertas');
        Schema::table('asos', function (Blueprint $table) {
            $table->dropForeign(['novo_setor_id']);
            $table->dropForeign(['nova_funcao_id']);
            $table->dropColumn(['novo_setor_id', 'nova_funcao_id', 'local_exame']);
        });
    }
};
