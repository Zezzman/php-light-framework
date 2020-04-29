<?php
return [
    // Database Configurations
    'DATABASE' => [
        'MYSQL' => [
            'HOST' => 'localhost',
            'DATABASE_NAME' => 'database_name',
            'USERNAME' => 'username',
            'PASSWORD' => 'password1',
        ],
        'EMULATE_PREPARES' => false,
        'ERROR_MODE' => PDO::ERRMODE_EXCEPTION,
    ]
];