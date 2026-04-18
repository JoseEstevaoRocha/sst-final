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
        Schema::table('backup_configs', function (Blueprint $table) {
            $table->string('google_client_id')->nullable()->after('google_credenciais_json');
            $table->text('google_client_secret')->nullable()->after('google_client_id');
            $table->text('google_refresh_token')->nullable()->after('google_client_secret');
        });
    }

    public function down(): void
    {
        Schema::table('backup_configs', function (Blueprint $table) {
            $table->dropColumn(['google_client_id','google_client_secret','google_refresh_token']);
        });
    }
};
