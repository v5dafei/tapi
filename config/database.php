<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' =>  \Yaconf::get(YACONF_PRO_ENV.'.DATABASE_URL'),
            'database' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' =>  \Yaconf::get(YACONF_PRO_ENV.'.DATABASE_URL'),
            'host' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_HOST', '127.0.0.1'),
            'port' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_PORT', '3306'),
            'database' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_DATABASE', 'forge'),
            'username' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_USERNAME', 'forge'),
            'password' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_PASSWORD', ''),
            'unix_socket' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA =>  \Yaconf::get(YACONF_PRO_ENV.'.MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' =>  \Yaconf::get(YACONF_PRO_ENV.'.DATABASE_URL'),
            'host' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_HOST', '127.0.0.1'),
            'port' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_PORT', '5432'),
            'database' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_DATABASE', 'forge'),
            'username' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_USERNAME', 'forge'),
            'password' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' =>  \Yaconf::get(YACONF_PRO_ENV.'.DATABASE_URL'),
            'host' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_HOST', 'localhost'),
            'port' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_PORT', '1433'),
            'database' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_DATABASE', 'forge'),
            'username' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_USERNAME', 'forge'),
            'password' =>  \Yaconf::get(YACONF_PRO_ENV.'.DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_CLIENT', 'predis'),

        'options' => [
            'cluster' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_CLUSTER', 'redis'),
            'prefix' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_PREFIX', ''),
        ],

        'default' => [
            'url' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_URL'),
            'host' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_HOST', '127.0.0.1'),
            'password' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_PASSWORD', null),
            'port' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_PORT', '6379'),
            'database' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_DB', '0'),
        ],

        'cache' => [
            'url' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_URL'),
            'host' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_HOST', '127.0.0.1'),
            'password' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_PASSWORD', null),
            'port' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_PORT', '6379'),
            'database' =>  \Yaconf::get(YACONF_PRO_ENV.'.REDIS_CACHE_DB', '1'),
        ],

    ],

];
