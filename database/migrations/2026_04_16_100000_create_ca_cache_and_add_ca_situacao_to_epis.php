<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ca_cache', function (Blueprint $table) {
            $table->string('ca', 20)->primary();
            $table->string('situacao', 30)->nullable();          // VÁLIDO, VENCIDO, CANCELADO
            $table->date('data_validade')->nullable();
            $table->string('nome_equipamento')->nullable();
            $table->text('descricao_equipamento')->nullable();
            $table->string('marca', 150)->nullable();
            $table->string('referencia', 100)->nullable();
            $table->string('natureza', 255)->nullable();
            $table->string('razao_social')->nullable();          // fabricante
            $table->string('cnpj_fabricante', 20)->nullable();
            $table->string('norma')->nullable();
            $table->jsonb('dados_completos')->nullable();        // todos os 19 campos
            $table->timestamp('atualizado_em')->nullable();
            $table->boolean('api_disponivel')->default(true);
        });

        Schema::table('epis', function (Blueprint $table) {
            $table->string('ca_situacao', 30)->nullable()->after('validade_ca');
        });
    }

    public function down(): void {
        Schema::dropIfExists('ca_cache');
        Schema::table('epis', function (Blueprint $table) {
            $table->dropColumn('ca_situacao');
        });
    }
};
