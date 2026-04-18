<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration {
    public function up(): void {
        Schema::create('backup_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('ativo')->default(false);
            $table->string('horario', 5)->default('02:00');
            $table->integer('retencao')->default(7);
            $table->string('pg_dump_path', 500)->default('pg_dump');
            $table->boolean('google_drive_ativo')->default(false);
            $table->string('google_drive_pasta_id')->nullable();
            $table->text('google_credenciais_json')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('nome_arquivo');
            $table->bigInteger('tamanho_bytes')->nullable();
            $table->string('status', 20)->default('sucesso');
            $table->text('mensagem')->nullable();
            $table->string('google_drive_id')->nullable();
            $table->boolean('google_drive_ok')->default(false);
            $table->string('tipo', 20)->default('manual');
            $table->timestamps();
        });

        DB::table('backup_configs')->insert([
            'ativo'          => false,
            'horario'        => '02:00',
            'retencao'       => 7,
            'pg_dump_path'   => 'pg_dump',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('backup_configs');
    }
};
