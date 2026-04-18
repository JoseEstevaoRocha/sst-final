<?php
return [
    'name'             => env('APP_NAME', 'SST Manager'),
    'env'              => env('APP_ENV', 'production'),
    'debug'            => (bool) env('APP_DEBUG', false),
    'url'              => env('APP_URL', 'http://localhost'),
    'timezone'         => env('APP_TIMEZONE', 'America/Sao_Paulo'),
    'locale'           => env('APP_LOCALE', 'pt_BR'),
    'fallback_locale'  => env('APP_FALLBACK_LOCALE', 'pt_BR'),
    'faker_locale'     => env('APP_FAKER_LOCALE', 'pt_BR'),
    'cipher'           => 'AES-256-CBC',
    'key'              => env('APP_KEY'),
    'previous_keys'    => [],
    'maintenance'      => ['driver' => 'file'],
    'providers'        => \Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),
    'aliases'          => \Illuminate\Support\Facades\Facade::defaultAliases()->merge([
        'PDF' => Barryvdh\DomPDF\Facade\Pdf::class,
    ])->toArray(),
];
