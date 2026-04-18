<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('cnae', 10)->nullable()->after('cnpj')->comment('Código CNAE ex: 2011-8/00');
            $table->char('grau_risco_incendio', 1)->nullable()->after('cnae')->comment('A=Baixo B=Médio C=Alto D=Elevado (NBR 14276) — deixe nulo para auto-calcular pelo CNAE');
        });
    }
    public function down(): void {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['cnae', 'grau_risco_incendio']);
        });
    }
};
