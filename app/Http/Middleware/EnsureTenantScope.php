<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTenantScope — Middleware Multiempresa
 *
 * REGRA DE OURO: Todo usuário só enxerga dados da SUA empresa.
 * Este middleware seta o tenant_id na sessão/request para que
 * todos os scopes do Eloquent filtrem automaticamente.
 *
 * Usuários admin podem ver todas as empresas.
 * Usuários normais SEMPRE ficam presos ao seu empresa_id.
 */
class EnsureTenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Define o tenant ativo para este request
        // Models com HasTenantScope usam isto automaticamente
        if (!$user->hasRole('super-admin') && $user->empresa_id) {
            app()->instance('tenant_id', $user->empresa_id);
            $request->merge(['_tenant_id' => $user->empresa_id]);
        }

        return $next($request);
    }
}
