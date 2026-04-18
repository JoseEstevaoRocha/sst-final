<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{Hash, DB};
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── ROLES & PERMISSIONS ────────────────────────────────────
        $this->call(RolesPermissionsSeeder::class);

        // ── SYSTEM CONFIG ──────────────────────────────────────────
        DB::table('system_configs')->insertOrIgnore([
            ['key' => 'system_name',         'value' => 'SST Manager', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'system_logo',         'value' => null,          'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dark_mode_default',   'value' => '1',           'created_at' => now(), 'updated_at' => now()],
            ['key' => 'system_version',      'value' => '2.0.0',       'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── TAMANHOS ───────────────────────────────────────────────
        $tamanhos = [
            ['codigo'=>'PP','descricao'=>'Extra Pequeno','ordem'=>1],
            ['codigo'=>'P', 'descricao'=>'Pequeno',      'ordem'=>2],
            ['codigo'=>'M', 'descricao'=>'Médio',        'ordem'=>3],
            ['codigo'=>'G', 'descricao'=>'Grande',       'ordem'=>4],
            ['codigo'=>'GG','descricao'=>'Extra Grande', 'ordem'=>5],
            ['codigo'=>'XG','descricao'=>'Extra Extra',  'ordem'=>6],
            ['codigo'=>'36','descricao'=>'Nº 36',        'ordem'=>10],
            ['codigo'=>'38','descricao'=>'Nº 38',        'ordem'=>11],
            ['codigo'=>'40','descricao'=>'Nº 40',        'ordem'=>12],
            ['codigo'=>'42','descricao'=>'Nº 42',        'ordem'=>13],
            ['codigo'=>'44','descricao'=>'Nº 44',        'ordem'=>14],
        ];
        foreach ($tamanhos as $t) {
            DB::table('tamanhos')->insertOrIgnore(array_merge($t, ['created_at'=>now(),'updated_at'=>now()]));
        }

        // ── TIPOS DE EXAME ─────────────────────────────────────────
        $tipos = [
            ['nome'=>'Audiometria',     'periodicidade_padrao_dias'=>365],
            ['nome'=>'Espirometria',    'periodicidade_padrao_dias'=>365],
            ['nome'=>'Acuidade Visual', 'periodicidade_padrao_dias'=>365],
            ['nome'=>'ECG',             'periodicidade_padrao_dias'=>365],
            ['nome'=>'Hemograma',       'periodicidade_padrao_dias'=>365],
            ['nome'=>'Glicemia',        'periodicidade_padrao_dias'=>365],
            ['nome'=>'Raio-X Tórax',    'periodicidade_padrao_dias'=>365],
            ['nome'=>'Clínico Geral',   'periodicidade_padrao_dias'=>180],
        ];
        foreach ($tipos as $t) {
            DB::table('tipos_exame')->insertOrIgnore(array_merge($t, ['created_at'=>now(),'updated_at'=>now()]));
        }

        // ── RISCOS PADRÃO ──────────────────────────────────────────
        $riscos = [
            ['nome'=>'Ruído',             'categoria'=>'fisico',     'nr_referencia'=>'NR-15'],
            ['nome'=>'Calor',             'categoria'=>'fisico',     'nr_referencia'=>'NR-15'],
            ['nome'=>'Vibrações',         'categoria'=>'fisico',     'nr_referencia'=>'NR-15'],
            ['nome'=>'Poeiras Minerais',  'categoria'=>'quimico',    'nr_referencia'=>'NR-15'],
            ['nome'=>'Agentes Biológicos','categoria'=>'biologico',  'nr_referencia'=>'NR-32'],
            ['nome'=>'Esforço Repetitivo','categoria'=>'ergonomico', 'nr_referencia'=>'NR-17'],
            ['nome'=>'Eletricidade',      'categoria'=>'acidente',   'nr_referencia'=>'NR-10'],
            ['nome'=>'Queda em altura',   'categoria'=>'acidente',   'nr_referencia'=>'NR-35'],
        ];
        foreach ($riscos as $r) {
            DB::table('riscos')->insertOrIgnore(array_merge($r, ['created_at'=>now(),'updated_at'=>now()]));
        }

        // ── EMPRESA DEMO ───────────────────────────────────────────
        $empresa1 = DB::table('empresas')->insertGetId([
            'razao_social'  => 'Indústria MetalSP Ltda',
            'nome_fantasia' => 'MetalSP',
            'cnpj'          => '12345678000100',
            'cidade'        => 'São Paulo',
            'estado'        => 'SP',
            'status'        => 'ativa',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $empresa2 = DB::table('empresas')->insertGetId([
            'razao_social'  => 'Construtora Horizonte S.A.',
            'nome_fantasia' => 'Horizonte',
            'cnpj'          => '98765432000155',
            'cidade'        => 'Rio de Janeiro',
            'estado'        => 'RJ',
            'status'        => 'ativa',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // ── USERS ──────────────────────────────────────────────────
        $superAdmin = DB::table('users')->insertGetId([
            'name'          => 'Super Admin',
            'email'         => 'admin@sst.com',
            'password'      => Hash::make('password'),
            'empresa_id'    => null,
            'cargo'         => 'Chief Safety Officer',
            'active'        => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $adminEmp = DB::table('users')->insertGetId([
            'name'          => 'Técnico SST',
            'email'         => 'tecnico@sst.com',
            'password'      => Hash::make('password'),
            'empresa_id'    => $empresa1,
            'cargo'         => 'Técnico de Segurança',
            'active'        => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Assign roles via spatie
        $superAdminRole = DB::table('roles')->where('name','super-admin')->value('id');
        $gestorRole     = DB::table('roles')->where('name','gestor')->value('id');

        if ($superAdminRole) DB::table('model_has_roles')->insertOrIgnore(['role_id'=>$superAdminRole,'model_type'=>'App\\Models\\User','model_id'=>$superAdmin]);
        if ($gestorRole)     DB::table('model_has_roles')->insertOrIgnore(['role_id'=>$gestorRole,    'model_type'=>'App\\Models\\User','model_id'=>$adminEmp]);

        // ── SETORES E FUNÇÕES ──────────────────────────────────────
        $s1 = DB::table('setores')->insertGetId(['empresa_id'=>$empresa1,'nome'=>'Produção',      'created_at'=>now(),'updated_at'=>now()]);
        $s2 = DB::table('setores')->insertGetId(['empresa_id'=>$empresa1,'nome'=>'Manutenção',    'created_at'=>now(),'updated_at'=>now()]);
        $s3 = DB::table('setores')->insertGetId(['empresa_id'=>$empresa1,'nome'=>'Administrativo','created_at'=>now(),'updated_at'=>now()]);
        $s4 = DB::table('setores')->insertGetId(['empresa_id'=>$empresa2,'nome'=>'Obra',          'created_at'=>now(),'updated_at'=>now()]);

        $f1 = DB::table('funcoes')->insertGetId(['empresa_id'=>$empresa1,'setor_id'=>$s1,'nome'=>'Operador de Máquina','cbo'=>'7171-10','periodicidade_aso_dias'=>365,'created_at'=>now(),'updated_at'=>now()]);
        $f2 = DB::table('funcoes')->insertGetId(['empresa_id'=>$empresa1,'setor_id'=>$s1,'nome'=>'Soldador',           'cbo'=>'7243-35','periodicidade_aso_dias'=>180,'created_at'=>now(),'updated_at'=>now()]);
        $f3 = DB::table('funcoes')->insertGetId(['empresa_id'=>$empresa1,'setor_id'=>$s2,'nome'=>'Mecânico Industrial','cbo'=>'9141-05','periodicidade_aso_dias'=>365,'created_at'=>now(),'updated_at'=>now()]);
        $f4 = DB::table('funcoes')->insertGetId(['empresa_id'=>$empresa1,'setor_id'=>$s3,'nome'=>'Aux. Administrativo','cbo'=>'4110-05','periodicidade_aso_dias'=>730,'created_at'=>now(),'updated_at'=>now()]);
        $f5 = DB::table('funcoes')->insertGetId(['empresa_id'=>$empresa2,'setor_id'=>$s4,'nome'=>'Mestre de Obras',    'cbo'=>'7119-05','periodicidade_aso_dias'=>365,'created_at'=>now(),'updated_at'=>now()]);

        // ── COLABORADORES ──────────────────────────────────────────
        $colabs = [
            [$empresa1,$s1,$f1,'Carlos Eduardo Silva',   '12345678901','1985-03-15','M','2018-06-01','Contratado','2024-06-01','E001','7171-10','Ensino Médio Completo'],
            [$empresa1,$s1,$f2,'Marcos Antonio Ferreira','23456789012','1990-07-22','M','2020-01-15','Contratado','2023-12-10','E002','7243-35','Ensino Médio Completo'],
            [$empresa1,$s2,$f3,'Roberto Lima Santos',    '34567890123','1978-11-08','M','2015-03-20','Contratado',null,         'E003','9141-05','Superior Completo'],
            [$empresa1,$s3,$f4,'Ana Paula Costa',        '45678901234','1995-05-30','F','2022-08-01','Contratado',null,         'E004','4110-05','Superior Completo'],
            [$empresa2,$s4,$f5,'José Carlos Oliveira',   '56789012345','1975-12-01','M','2019-04-10','Contratado','2024-04-10','E005','7119-05','Ensino Médio Completo'],
        ];

        foreach ($colabs as [$eid,$sid,$fid,$nome,$cpf,$nasc,$sexo,$adm,$status,$ultAso,$mat,$cbo,$esc]) {
            DB::table('colaboradores')->insertOrIgnore([
                'empresa_id'=>$eid,'setor_id'=>$sid,'funcao_id'=>$fid,'nome'=>$nome,'cpf'=>$cpf,
                'data_nascimento'=>$nasc,'sexo'=>$sexo,'data_admissao'=>$adm,'status'=>$status,
                'matricula'=>$mat,'cbo'=>$cbo,'escolaridade'=>$esc,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        // ── CLÍNICA + ASOs ─────────────────────────────────────────
        $clinica1 = DB::table('clinicas')->insertGetId(['nome'=>'Clínica Saúde Ocupacional SP','whatsapp'=>'11999990001','cidade'=>'São Paulo','estado'=>'SP','ativo'=>true,'created_at'=>now(),'updated_at'=>now()]);

        $col1 = DB::table('colaboradores')->where('cpf','12345678901')->value('id');
        $col2 = DB::table('colaboradores')->where('cpf','23456789012')->value('id');
        if ($col1) DB::table('asos')->insertOrIgnore(['empresa_id'=>$empresa1,'colaborador_id'=>$col1,'tipo'=>'periodico','data_exame'=>'2024-06-01','data_vencimento'=>'2025-06-01','resultado'=>'apto','clinica_nome'=>'Clínica SSP','status_logistico'=>'entregue_colaborador','created_at'=>now(),'updated_at'=>now()]);
        if ($col2) DB::table('asos')->insertOrIgnore(['empresa_id'=>$empresa1,'colaborador_id'=>$col2,'tipo'=>'periodico','data_exame'=>'2023-12-10','data_vencimento'=>'2024-06-10','resultado'=>'apto','clinica_nome'=>'Clínica SSP','status_logistico'=>'pendente','created_at'=>now(),'updated_at'=>now()]);

        // ── EPIs DEMO ──────────────────────────────────────────────
        $ep1 = DB::table('epis')->insertGetId(['nome'=>'Capacete de Segurança','tipo'=>'Capacete','numero_ca'=>'498232','fornecedor'=>'MSA Safety','vida_util_dias'=>365,'estoque_minimo'=>10,'unidade'=>'un','custo_unitario'=>24.50,'status'=>'Ativo','created_at'=>now(),'updated_at'=>now()]);
        $ep2 = DB::table('epis')->insertGetId(['nome'=>'Luva de Proteção Nitrila','tipo'=>'Luva','numero_ca'=>'321445','fornecedor'=>'Ansell','vida_util_dias'=>90,'estoque_minimo'=>20,'unidade'=>'par','custo_unitario'=>12.20,'status'=>'Ativo','created_at'=>now(),'updated_at'=>now()]);
        $ep3 = DB::table('epis')->insertGetId(['nome'=>'Protetor Auricular 3M','tipo'=>'Protetor Auricular','numero_ca'=>'14235','fornecedor'=>'3M','vida_util_dias'=>180,'estoque_minimo'=>15,'unidade'=>'un','custo_unitario'=>89.90,'status'=>'Ativo','created_at'=>now(),'updated_at'=>now()]);

        DB::table('epi_estoques')->insertOrIgnore([
            ['epi_id'=>$ep1,'empresa_id'=>$empresa1,'quantidade'=>45,'created_at'=>now(),'updated_at'=>now()],
            ['epi_id'=>$ep2,'empresa_id'=>$empresa1,'quantidade'=>8, 'created_at'=>now(),'updated_at'=>now()],  // below minimum
            ['epi_id'=>$ep3,'empresa_id'=>$empresa1,'quantidade'=>22,'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ── UNIFORMES ──────────────────────────────────────────────
        $u1 = DB::table('uniformes')->insertGetId(['nome'=>'Camisa Polo','tipo'=>'Camisa','fornecedor'=>'TextilSP','custo_unitario'=>35.00,'status'=>'Ativo','created_at'=>now(),'updated_at'=>now()]);
        $u2 = DB::table('uniformes')->insertGetId(['nome'=>'Calça Operacional','tipo'=>'Calça','fornecedor'=>'TextilSP','custo_unitario'=>89.00,'status'=>'Ativo','created_at'=>now(),'updated_at'=>now()]);
        $tM  = DB::table('tamanhos')->where('codigo','M')->value('id');
        $tG  = DB::table('tamanhos')->where('codigo','G')->value('id');
        $tGG = DB::table('tamanhos')->where('codigo','GG')->value('id');
        if ($tM) {
            DB::table('uniforme_estoques')->insertOrIgnore([
                ['uniforme_id'=>$u1,'tamanho_id'=>$tM, 'quantidade'=>20,'minimo'=>5,'created_at'=>now(),'updated_at'=>now()],
                ['uniforme_id'=>$u1,'tamanho_id'=>$tG, 'quantidade'=>15,'minimo'=>5,'created_at'=>now(),'updated_at'=>now()],
                ['uniforme_id'=>$u1,'tamanho_id'=>$tGG,'quantidade'=>2, 'minimo'=>3,'created_at'=>now(),'updated_at'=>now()],
                ['uniforme_id'=>$u2,'tamanho_id'=>$tM, 'quantidade'=>10,'minimo'=>4,'created_at'=>now(),'updated_at'=>now()],
                ['uniforme_id'=>$u2,'tamanho_id'=>$tG, 'quantidade'=>8, 'minimo'=>4,'created_at'=>now(),'updated_at'=>now()],
            ]);
        }

        // ── EXTINTOR DEMO ──────────────────────────────────────────
        DB::table('extintores')->insertOrIgnore([
            ['empresa_id'=>$empresa1,'setor_id'=>$s1,'tipo'=>'co2','capacidade'=>'6kg','localizacao'=>'Linha A','ultima_recarga'=>'2024-01-10','proxima_recarga'=>'2025-01-10','status'=>'regular','created_at'=>now(),'updated_at'=>now()],
            ['empresa_id'=>$empresa1,'setor_id'=>$s2,'tipo'=>'po_quimico','capacidade'=>'12kg','localizacao'=>'Oficina','ultima_recarga'=>'2023-06-01','proxima_recarga'=>'2024-06-01','status'=>'vencido','created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
