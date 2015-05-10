<?php

return array(
    'mysql' => array(
        'db_type' => 'mysql',
        'db_host' => 'localhost',
        'db_port' => 3306,
        'db_name' => 'cats',
        'db_user' => 'root',
        'db_password' => 'password',

        'db_table' => 'categories',
        'db_prefix' => 'test_',
        'id_column' => 'id',
        'left_column' => 'left_key',
        'right_column' => 'right_key',
    ),
    'postgresql' => array(
        'db_type' => 'pgsql',
        'db_host' => 'localhost',
        'db_port' => 5432,
        'db_name' => 'cats',
        'db_user' => 'postgres',
        'db_password' => 'password',

        'db_table' => 'categories',
        'db_prefix' => 'test_',
        'id_column' => 'id',
        'left_column' => 'left_key',
        'right_column' => 'right_key',
    ),
    'sqlite' => array(
        'db_type' => 'sqlite',
        'db_host' => __DIR__ . '/cats.db',

        'db_table' => 'categories',
        'db_prefix' => 'test_',
        'id_column' => 'id',
        'left_column' => 'left_key',
        'right_column' => 'right_key',
    ),
);
