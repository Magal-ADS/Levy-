<?php

require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/manifest+json; charset=UTF-8');

echo json_encode([
    'name' => 'Levy Controle',
    'short_name' => 'Levy',
    'start_url' => app_url(),
    'scope' => asset_url(),
    'display' => 'standalone',
    'background_color' => '#0f172a',
    'theme_color' => '#4f46e5',
    'description' => 'Meu Controle Financeiro Pessoal',
    'icons' => [
        [
            'src' => asset_url('favicon.svg'),
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
