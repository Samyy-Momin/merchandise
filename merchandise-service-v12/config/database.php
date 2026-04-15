<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [
    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                defined('Pdo\Mysql::ATTR_SSL_CA') ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'master_db' => [
            'driver' => env('MASTER_DB_CONNECTION', env('APP_ENV', 'production') === 'testing' ? 'sqlite' : 'mysql'),
            'url' => env('MASTER_DB_URL'),
            'host' => env('MASTER_DB_HOST', '127.0.0.1'),
            'port' => env('MASTER_DB_PORT', '3306'),
            'database' => env('APP_ENV', 'production') === 'testing'
                ? ':memory:'
                : (env('MASTER_DB_DATABASE') ?: env('DB_DATABASE', database_path('database.sqlite'))),
            'username' => env('MASTER_DB_USERNAME', ''),
            'password' => env('MASTER_DB_PASSWORD', ''),
            'unix_socket' => env('MASTER_DB_SOCKET', ''),
            'charset' => env('MASTER_DB_CHARSET', 'utf8mb4'),
            'collation' => env('MASTER_DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                defined('Pdo\Mysql::ATTR_SSL_CA') ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA => env('MASTER_MYSQL_ATTR_SSL_CA'),
            ]) : [],
            'read' => [
                'host' => env('MASTER_DB_HOST', '127.0.0.1'),
                'port' => env('MASTER_DB_PORT', '3306'),
                'database' => env('MASTER_DB_DATABASE', ''),
                'username' => env('MASTER_DB_USERNAME', ''),
                'password' => env('MASTER_DB_PASSWORD', ''),
            ],
            'write' => [
                'host' => env('MASTER_DB_HOST', '127.0.0.1'),
                'port' => env('MASTER_DB_PORT', '3306'),
                'database' => env('MASTER_DB_DATABASE', ''),
                'username' => env('MASTER_DB_USERNAME', ''),
                'password' => env('MASTER_DB_PASSWORD', ''),
            ],
        ],

        'pgsql_ofd' => [
            'driver' => 'pgsql',
            'url' => env('OFD_DB_URL'),
            'host' => env('OFD_DB_HOST', '127.0.0.1'),
            'port' => env('OFD_DB_PORT', '5432'),
            'database' => env('OFD_DB_DATABASE', 'onefooddialer_core'),
            'username' => env('OFD_DB_USERNAME', 'ofd_app_user'),
            'password' => env('OFD_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => env('OFD_DB_SCHEMA', 'ofd'),
            'sslmode' => env('OFD_DB_SSLMODE', 'prefer'),
            'options' => extension_loaded('pdo_pgsql') ? [
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_PERSISTENT => false,
            ] : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
