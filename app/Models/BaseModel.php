<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * BaseModel — Modelo Base com Multiempresa
 *
 * Todos os models que têm empresa_id devem extender este.
 * O TenantScope é aplicado automaticamente em TODAS as queries,
 * garantindo isolamento total de dados entre empresas.
 */
abstract class BaseModel extends Model
{
    /**
     * Se true, aplica tenant scope automaticamente.
     * Override para false em models globais (ex: Empresa, User).
     */
    protected bool $tenantScoped = true;

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('tenant', function (Builder $builder) {
            $model = new static;

            // Só aplica se o model tem empresa_id E o tenant está definido
            if (
                $model->tenantScoped &&
                in_array('empresa_id', $model->getFillable()) &&
                app()->bound('tenant_id')
            ) {
                $tenantId = app('tenant_id');
                $table    = $model->getTable();
                $builder->where("{$table}.empresa_id", $tenantId);
            }
        });

        // Auto-setar empresa_id ao criar
        static::creating(function ($model) {
            if (
                $model->tenantScoped &&
                in_array('empresa_id', $model->getFillable()) &&
                !$model->empresa_id &&
                app()->bound('tenant_id')
            ) {
                $model->empresa_id = app('tenant_id');
            }
        });
    }

    /**
     * Escapa o tenant scope para queries administrativas.
     * Use com CUIDADO — apenas para super-admins.
     */
    public static function withoutTenant(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
