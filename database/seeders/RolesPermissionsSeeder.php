<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';
        $now   = now();

        // ── PERMISSIONS ────────────────────────────────────────────
        $permissions = [
            // Colaboradores
            'colaboradores.view','colaboradores.create','colaboradores.edit','colaboradores.delete','colaboradores.import',
            // ASO
            'asos.view','asos.create','asos.edit','asos.delete',
            // EPI
            'epis.view','epis.create','epis.edit','epis.delete','epis.estoque',
            // Uniformes
            'uniformes.view','uniformes.create','uniformes.edit','uniformes.delete',
            // Máquinas
            'maquinas.view','maquinas.create','maquinas.edit','maquinas.delete',
            // Emergência
            'emergencia.view','emergencia.create','emergencia.edit','emergencia.delete',
            // GHE
            'ghe.view','ghe.create','ghe.edit','ghe.delete',
            // Relatórios
            'relatorios.view','relatorios.export',
            // Configurações
            'config.view','config.edit',
            // Empresas (apenas super-admin)
            'empresas.view','empresas.create','empresas.edit','empresas.delete',
            // Usuários
            'users.view','users.create','users.edit','users.delete',
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->insertOrIgnore(['name'=>$perm,'guard_name'=>$guard,'created_at'=>$now,'updated_at'=>$now]);
        }

        // ── ROLES ──────────────────────────────────────────────────
        $roles = [
            'super-admin' => $permissions,  // Tudo
            'admin'       => array_filter($permissions, fn($p) => !str_starts_with($p, 'empresas.')),
            'gestor'      => [
                'colaboradores.view','colaboradores.create','colaboradores.edit','colaboradores.import',
                'asos.view','asos.create','asos.edit',
                'epis.view','epis.create','epis.edit','epis.estoque',
                'uniformes.view','uniformes.create','uniformes.edit',
                'maquinas.view','maquinas.create','maquinas.edit',
                'emergencia.view','emergencia.create','emergencia.edit',
                'ghe.view','ghe.create','ghe.edit',
                'relatorios.view','relatorios.export',
            ],
            'operador'    => [
                'colaboradores.view','asos.view','epis.view','uniformes.view',
                'maquinas.view','emergencia.view','ghe.view','relatorios.view',
            ],
            'visualizador'=> [
                'colaboradores.view','asos.view','epis.view','uniformes.view','relatorios.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $roleId = DB::table('roles')->insertGetId(['name'=>$roleName,'guard_name'=>$guard,'created_at'=>$now,'updated_at'=>$now]);

            foreach (array_values($rolePermissions) as $perm) {
                $permId = DB::table('permissions')->where('name',$perm)->value('id');
                if ($permId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$permId,'role_id'=>$roleId]);
                }
            }
        }
    }
}
