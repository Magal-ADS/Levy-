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
            'src' => 'https://cdn-icons-png.flaticon.com/512/5501/5501375.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
