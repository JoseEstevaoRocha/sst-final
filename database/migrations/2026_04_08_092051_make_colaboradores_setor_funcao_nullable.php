<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ->change() do Laravel não removeu o NOT NULL no PostgreSQL.
        // SQL nativo garante que as colunas aceitem NULL quando setor/função for excluído.
        DB::statement('ALTER TABLE colaboradores ALTER COLUMN setor_id DROP NOT NULL');
        DB::statement('ALTER TABLE colaboradores ALTER COLUMN funcao_id DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE colaboradores ALTER COLUMN setor_id SET NOT NULL');
        DB::statement('ALTER TABLE colaboradores ALTER COLUMN funcao_id SET NOT NULL');
    }
};
