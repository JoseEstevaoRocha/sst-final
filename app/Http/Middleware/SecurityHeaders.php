<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders
 *
 * Adiciona headers HTTP de segurança em todas as respostas.
 * Protege contra XSS, clickjacking, sniffing de MIME type, etc.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Previne clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Previne MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Força HTTPS por 1 ano (habilitar só com HTTPS ativo)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy básica
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Controla informações no Referer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Remove informação do servidor
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // Permissions Policy (desativa acesso a hardware sensível)
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        return $response;
    }
}
