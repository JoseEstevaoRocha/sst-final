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
        Schema::table('asos', function (Blueprint $table) {
            $table->date('data_agendada')->nullable()->after('data_vencimento');
            $table->time('horario_agendado')->nullable()->after('data_agendada');
            $table->text('exames_complementares')->nullable()->after('horario_agendado');
        });
    }

    public function down(): void
    {
        Schema::table('asos', function (Blueprint $table) {
            $table->dropColumn(['data_agendada','horario_agendado','exames_complementares']);
        });
    }
};
