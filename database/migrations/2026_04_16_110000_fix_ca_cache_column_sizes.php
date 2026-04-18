<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ca_cache', function (Blueprint $table) {
            $table->text('referencia')->nullable()->change();
            $table->text('descricao_equipamento')->nullable()->change();
            $table->text('norma')->nullable()->change();
            $table->string('natureza', 500)->nullable()->change();
            $table->string('razao_social', 500)->nullable()->change();
            $table->string('nome_equipamento', 500)->nullable()->change();
            $table->string('marca', 500)->nullable()->change();
            $table->string('cnpj_fabricante', 30)->nullable()->change();
        });
    }
    public function down(): void {}
};
