<?php

declare(strict_types=1);

return [
    'default' => \AaoSikheSystem\Env::get('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => \AaoSikheSystem\Env::get('DB_HOST', 'localhost'),
            'port'      => \AaoSikheSystem\Env::get('DB_PORT', '3306'),
            'database'  => \AaoSikheSystem\Env::get('DB_DATABASE', 'aaosikhe'),
            'username'  => \AaoSikheSystem\Env::get('DB_USERNAME', 'root'),
            'password'  => \AaoSikheSystem\Env::get('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],

        'pgsql' => [
            'driver'    => 'pgsql',
            'host'      => \AaoSikheSystem\Env::get('DB_HOST', 'localhost'),
            'port'      => \AaoSikheSystem\Env::get('DB_PORT', '5432'),
            'database'  => \AaoSikheSystem\Env::get('DB_DATABASE', 'aaosikhe'),
            'username'  => \AaoSikheSystem\Env::get('DB_USERNAME', 'root'),
            'password'  => \AaoSikheSystem\Env::get('DB_PASSWORD', ''),
            'charset'   => 'utf8',
        ],

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => \AaoSikheSystem\Env::get('DB_DATABASE', __DIR__ . '/../../storage/database.sqlite'),
        ],
    ],
];
