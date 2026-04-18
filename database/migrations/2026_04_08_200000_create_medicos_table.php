<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('crm')->nullable();
            $table->string('especialidade')->default('Medicina do Trabalho');
            $table->foreignId('clinica_id')->nullable()->constrained('clinicas')->nullOnDelete();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('medicos');
    }
};
