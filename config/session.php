<?php
return [
    'driver'          => env('SESSION_DRIVER', 'file'),
    'lifetime'        => env('SESSION_LIFETIME', 120),
    'expire_on_close' => true,
    'encrypt'         => (bool) env('SESSION_ENCRYPT', true),
    'files'           => storage_path('framework/sessions'),
    'cookie'          => env('SESSION_COOKIE', \Illuminate\Support\Str::slug(env('APP_NAME', 'sst'), '_').'_session'),
    'path'            => '/',
    'domain'          => env('SESSION_DOMAIN', null),
    'secure'          => env('SESSION_SECURE_COOKIE', false),
    'http_only'       => true,
    'same_site'       => 'lax',
    'partitioned'     => false,
];
