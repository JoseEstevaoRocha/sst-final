<?php
// database/migrations/2024_01_01_000001_create_all_sst_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── USERS ──────────────────────────────────────────────────
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('empresa_id')->nullable(); // null = super-admin
            $table->string('cargo')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['empresa_id', 'active']);
        });

        // ── PERMISSION TABLES (Spatie) ─────────────────────────────
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); $table->string('guard_name'); $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); $table->string('guard_name'); $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type'); $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id','model_id','model_type']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->index(['model_id','model_type']);
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type'); $table->unsignedBigInteger('model_id');
            $table->primary(['role_id','model_id','model_type']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->index(['model_id','model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id','role_id']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        // ── SYSTEM CONFIG ──────────────────────────────────────────
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // ── EMPRESAS ───────────────────────────────────────────────
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj', 14)->unique();
            $table->text('endereco')->nullable();
            $table->string('cidade', 100)->nullable();
            $table->char('estado', 2)->nullable();
            $table->string('cep', 8)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('status', 20)->default('ativa');
            $table->timestamps();
            $table->softDeletes();
        });

        // Add FK now that empresas exists
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('empresa_id')->references('id')->on('empresas')->nullOnDelete();
        });

        // ── SETORES ────────────────────────────────────────────────
        Schema::create('setores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->timestamps();
            $table->index('empresa_id');
        });

        // ── FUNÇÕES ────────────────────────────────────────────────
        Schema::create('funcoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('setor_id')->constrained('setores')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('cbo', 10)->nullable();
            $table->unsignedInteger('periodicidade_aso_dias')->default(365);
            $table->timestamps();
            $table->index(['empresa_id', 'setor_id']);
        });

        // ── COLABORADORES ──────────────────────────────────────────
        Schema::create('colaboradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('setor_id')->constrained('setores');
            $table->foreignId('funcao_id')->constrained('funcoes');
            $table->string('nome');
            $table->string('cpf', 11)->unique();
            $table->string('rg', 20)->nullable();
            $table->string('pis', 11)->nullable();
            $table->string('matricula', 50)->nullable();
            $table->string('matricula_esocial', 50)->nullable();
            $table->string('cbo', 10)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->char('sexo', 1)->nullable();
            $table->date('data_admissao')->nullable();
            $table->date('data_demissao')->nullable();
            $table->string('status', 20)->default('Contratado');
            $table->boolean('jovem_aprendiz')->default(false);
            $table->string('escolaridade', 100)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['empresa_id', 'status']);
            $table->index('nome');
        });

        // ── GHE & RISCOS ───────────────────────────────────────────
        Schema::create('riscos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('categoria', 50); // fisico,quimico,biologico,ergonomico,acidente
            $table->text('descricao')->nullable();
            $table->string('nr_referencia', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('ghes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 30);
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->timestamps();
            $table->unique(['empresa_id', 'codigo']);
        });

        Schema::create('ghe_riscos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ghe_id')->constrained('ghes')->cascadeOnDelete();
            $table->foreignId('risco_id')->constrained('riscos')->cascadeOnDelete();
            $table->unsignedTinyInteger('probabilidade');
            $table->unsignedTinyInteger('severidade');
            $table->string('nivel_risco', 20)->virtualAs("CASE WHEN probabilidade * severidade >= 15 THEN 'Crítico' WHEN probabilidade * severidade >= 8 THEN 'Alto' WHEN probabilidade * severidade >= 4 THEN 'Médio' ELSE 'Baixo' END");
            $table->text('medidas_epc')->nullable();
            $table->text('medidas_epi')->nullable();
            $table->timestamps();
            $table->unique(['ghe_id', 'risco_id']);
        });

        Schema::create('ghe_setores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ghe_id')->constrained('ghes')->cascadeOnDelete();
            $table->foreignId('setor_id')->constrained('setores')->cascadeOnDelete();
            $table->unique(['ghe_id', 'setor_id']);
        });

        // ── CLÍNICAS ───────────────────────────────────────────────
        Schema::create('clinicas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cnpj', 14)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('endereco')->nullable();
            $table->string('cidade', 100)->nullable();
            $table->char('estado', 2)->nullable();
            $table->string('responsavel')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('empresa_clinica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->boolean('padrao')->default(false);
            $table->unique(['empresa_id', 'clinica_id']);
        });

        // ── ASOs ───────────────────────────────────────────────────
        Schema::create('asos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->foreignId('clinica_id')->nullable()->constrained('clinicas')->nullOnDelete();
            $table->string('tipo', 50); // admissional,periodico,demissional,retorno,mudanca_funcao
            $table->date('data_exame')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->string('resultado', 30)->default('pendente');
            $table->string('clinica_nome')->nullable();
            $table->string('medico_responsavel')->nullable();
            $table->string('status_logistico', 50)->default('pendente');
            $table->text('observacoes')->nullable();
            $table->boolean('whatsapp_enviado')->default(false);
            $table->timestamps();
            $table->index(['empresa_id', 'data_vencimento']);
            $table->index(['colaborador_id', 'tipo']);
        });

        // ── TIPOS DE EXAME ─────────────────────────────────────────
        Schema::create('tipos_exame', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->unsignedInteger('periodicidade_padrao_dias')->nullable();
            $table->timestamps();
        });

        // ── EPIs ───────────────────────────────────────────────────
        Schema::create('epis', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('tipo', 100);
            $table->string('numero_ca', 30)->nullable();
            $table->date('validade_ca')->nullable();
            $table->string('fornecedor')->nullable();
            $table->string('fabricante')->nullable();
            $table->unsignedInteger('vida_util_dias')->nullable();
            $table->unsignedInteger('estoque_minimo')->default(0);
            $table->string('unidade', 20)->default('un');
            $table->decimal('custo_unitario', 10, 2)->nullable();
            $table->string('status', 20)->default('Ativo');
            $table->timestamps();
        });

        Schema::create('epi_estoques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_id')->constrained('epis')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->integer('quantidade')->default(0);
            $table->timestamps();
            $table->unique(['epi_id', 'empresa_id']);
        });

        Schema::create('epi_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_id')->constrained('epis')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo', 20); // entrada,saida,ajuste
            $table->integer('quantidade');
            $table->text('motivo')->nullable();
            $table->string('usuario')->nullable();
            $table->timestamps();
        });

        Schema::create('entregas_epi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->foreignId('epi_id')->constrained('epis');
            $table->unsignedInteger('quantidade')->default(1);
            $table->date('data_entrega');
            $table->date('data_prevista_troca')->nullable();
            $table->string('status', 20)->default('Ativo');
            $table->string('responsavel')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'colaborador_id']);
            $table->index('data_prevista_troca');
        });

        // ── UNIFORMES ──────────────────────────────────────────────
        Schema::create('tamanhos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('descricao', 100)->nullable();
            $table->unsignedInteger('ordem')->default(99);
            $table->timestamps();
        });

        Schema::create('uniformes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo', 100);
            $table->text('descricao')->nullable();
            $table->string('fornecedor')->nullable();
            $table->decimal('custo_unitario', 10, 2)->nullable();
            $table->string('status', 20)->default('Ativo');
            $table->timestamps();
        });

        Schema::create('uniforme_estoques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uniforme_id')->constrained('uniformes')->cascadeOnDelete();
            $table->foreignId('tamanho_id')->constrained('tamanhos');
            $table->integer('quantidade')->default(0);
            $table->integer('minimo')->default(0);
            $table->timestamps();
            $table->unique(['uniforme_id', 'tamanho_id']);
        });

        Schema::create('entregas_uniforme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->foreignId('uniforme_id')->constrained('uniformes');
            $table->foreignId('tamanho_id')->constrained('tamanhos');
            $table->unsignedInteger('quantidade')->default(1);
            $table->date('data_entrega');
            $table->date('data_prevista_troca')->nullable();
            $table->string('motivo', 30)->default('admissao');
            $table->string('responsavel')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'colaborador_id']);
        });

        // ── MÁQUINAS NR12 ──────────────────────────────────────────
        Schema::create('maquinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('setor_id')->nullable()->constrained('setores')->nullOnDelete();
            $table->string('nome');
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('numero_serie', 100)->nullable()->unique();
            $table->unsignedSmallInteger('ano_fabricacao')->nullable();
            $table->string('status', 30)->default('operacional');
            $table->date('ultima_manutencao')->nullable();
            $table->date('proxima_manutencao')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->index('empresa_id');
        });

        Schema::create('manutencoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('maquina_id')->constrained('maquinas')->cascadeOnDelete();
            $table->string('tipo', 20); // preventiva,corretiva
            $table->date('data_manutencao');
            $table->text('descricao')->nullable();
            $table->string('responsavel')->nullable();
            $table->decimal('custo', 10, 2)->nullable();
            $table->date('proxima_manutencao')->nullable();
            $table->timestamps();
        });

        // ── EMERGÊNCIA ─────────────────────────────────────────────
        Schema::create('extintores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('setor_id')->nullable()->constrained('setores')->nullOnDelete();
            $table->string('numero_serie', 100)->nullable();
            $table->string('tipo', 50); // agua,po_quimico,co2,espuma
            $table->string('capacidade', 20)->nullable();
            $table->text('localizacao')->nullable();
            $table->date('data_fabricacao')->nullable();
            $table->date('ultima_recarga')->nullable();
            $table->date('proxima_recarga')->nullable();
            $table->date('ultimo_teste_hidrostatico')->nullable();
            $table->date('proximo_teste_hidrostatico')->nullable();
            $table->string('status', 20)->default('regular');
            $table->timestamps();
            $table->index('empresa_id');
        });

        Schema::create('inspecoes_extintor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('extintor_id')->constrained('extintores')->cascadeOnDelete();
            $table->date('data_inspecao');
            $table->string('responsavel')->nullable();
            $table->string('resultado', 20)->nullable(); // conforme,nao_conforme
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });

        Schema::create('brigadistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('funcao_brigada', 100)->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_validade_cert')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->unique(['empresa_id', 'colaborador_id']);
        });

        Schema::create('cipa_membros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('cargo', 100)->nullable();
            $table->date('mandato_inicio')->nullable();
            $table->date('mandato_fim')->nullable();
            $table->string('tipo', 20)->nullable(); // eleito,indicado
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // ── WHATSAPP ───────────────────────────────────────────────
        Schema::create('whatsapp_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->text('modelo_mensagem')->nullable();
            $table->boolean('incluir_cpf')->default(false);
            $table->string('telefone_retorno', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->foreignId('clinica_id')->nullable()->constrained('clinicas')->nullOnDelete();
            $table->foreignId('aso_id')->nullable()->constrained('asos')->nullOnDelete();
            $table->string('tipo_exame', 50)->nullable();
            $table->text('mensagem_texto');
            $table->string('status', 30)->default('pendente');
            $table->timestamp('data_envio')->nullable();
            $table->date('data_agendada')->nullable();
            $table->string('horario_agendado', 10)->nullable();
            $table->string('usuario_envio')->nullable();
            $table->timestamps();
            $table->index('empresa_id');
        });

        // ── AUDIT LOG ──────────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 150)->nullable();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->string('acao', 50);
            $table->string('tabela', 100);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->jsonb('dados_antes')->nullable();
            $table->jsonb('dados_depois')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['empresa_id', 'created_at']);
            $table->index(['tabela', 'registro_id']);
        });
    }

    public function down(): void
    {
        $tables = [
            'audit_logs','whatsapp_mensagens','whatsapp_configs','cipa_membros',
            'brigadistas','inspecoes_extintor','extintores','manutencoes','maquinas',
            'entregas_uniforme','uniforme_estoques','uniformes','tamanhos',
            'entregas_epi','epi_movimentacoes','epi_estoques','epis','tipos_exame',
            'asos','empresa_clinica','clinicas','ghe_setores','ghe_riscos','ghes',
            'riscos','colaboradores','funcoes','setores',
            'role_has_permissions','model_has_roles','model_has_permissions',
            'roles','permissions','system_configs','users','empresas',
        ];
        foreach ($tables as $t) Schema::dropIfExists($t);
    }
};
