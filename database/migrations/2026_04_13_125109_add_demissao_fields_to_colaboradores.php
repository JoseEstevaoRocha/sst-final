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
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('demissao_motivo', 300)->nullable()->after('data_demissao');
            $table->boolean('periodo_experiencia')->default(false)->after('demissao_motivo');
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn(['demissao_motivo','periodo_experiencia']);
        });
    }
};
