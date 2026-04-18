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
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->text('modelo_mudanca_funcao')->nullable()->after('modelo_mensagem');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->dropColumn('modelo_mudanca_funcao');
        });
    }
};
