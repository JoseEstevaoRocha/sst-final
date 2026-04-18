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
        Schema::table('epis', function (Blueprint $table) {
            $table->string('marca')->nullable()->after('fabricante');
        });
    }

    public function down(): void
    {
        Schema::table('epis', function (Blueprint $table) {
            $table->dropColumn('marca');
        });
    }
};
