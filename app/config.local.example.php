<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Ajude o Pedro',
        'timezone' => 'America/Sao_Paulo',
        // Na raiz deixe ''. Em https://site.com/rifa use '/rifa'.
        'base_path' => '',
        'reservation_minutes' => 30,
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'SEU_BANCO',
        'user' => 'SEU_USUARIO',
        'pass' => 'SUA_SENHA',
        'charset' => 'utf8mb4',
    ],
];