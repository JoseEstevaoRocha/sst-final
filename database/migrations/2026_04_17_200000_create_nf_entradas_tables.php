<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {

        // ── Fornecedores ──────────────────────────────────────────────────────
        Schema::create('nf_fornecedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('inscricao_estadual', 50)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'cnpj']);
        });

        // ── Notas Fiscais de Entrada ──────────────────────────────────────────
        Schema::create('nf_entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('fornecedor_id')->nullable()->constrained('nf_fornecedores')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();

            // Identificação da nota
            $table->string('numero', 20);
            $table->string('serie', 5)->default('1');
            $table->char('chave_acesso', 44)->nullable();
            $table->date('data_emissao');
            $table->date('data_entrada');
            $table->string('natureza_operacao')->nullable();
            $table->string('xml_path', 500)->nullable();

            // Valores
            $table->decimal('valor_produtos', 12, 2)->default(0);
            $table->decimal('valor_frete',    12, 2)->default(0);
            $table->decimal('valor_desconto', 12, 2)->default(0);
            $table->decimal('valor_total',    12, 2)->default(0);

            $table->string('status', 20)->default('ativa'); // ativa, cancelada
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'numero', 'serie']);
            $table->index('data_entrada');
        });

        // ── Itens da Nota Fiscal ──────────────────────────────────────────────
        Schema::create('nf_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nf_entrada_id')->constrained('nf_entradas')->cascadeOnDelete();
            $table->foreignId('epi_id')->constrained('epis')->cascadeOnDelete();
            $table->foreignId('tamanho_id')->nullable()->constrained('tamanhos')->nullOnDelete();

            $table->string('codigo_fornecedor', 60)->nullable();
            $table->string('nome');
            $table->string('unidade', 20)->default('un');
            $table->decimal('quantidade',    10, 3);
            $table->decimal('valor_unitario', 12, 4);
            $table->decimal('valor_total',    12, 2);
            $table->string('lote', 60)->nullable();
            $table->date('data_validade')->nullable();
            $table->timestamps();
        });

        // ── Rastreabilidade: adiciona nf_entrada_id às movimentações ──────────
        Schema::table('epi_movimentacoes', function (Blueprint $table) {
            $table->foreignId('nf_entrada_id')->nullable()->after('usuario')
                ->constrained('nf_entradas')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('epi_movimentacoes', function (Blueprint $table) {
            $table->dropForeign(['nf_entrada_id']);
            $table->dropColumn('nf_entrada_id');
        });
        Schema::dropIfExists('nf_itens');
        Schema::dropIfExists('nf_entradas');
        Schema::dropIfExists('nf_fornecedores');
    }
};
