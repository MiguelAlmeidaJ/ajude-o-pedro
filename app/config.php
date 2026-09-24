<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Ajude o Pedro',
        'timezone' => 'America/Sao_Paulo',
        // Se o projeto estiver em https://site.com/rifa, use '/rifa'. Na raiz, deixe vazio.
        'base_path' => '',
        'reservation_minutes' => 30,
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'ajude_o_pedro',
        'user' => 'SEU_USUARIO_MYSQL',
        'pass' => 'SUA_SENHA_MYSQL',
        'charset' => 'utf8mb4',
    ],
];