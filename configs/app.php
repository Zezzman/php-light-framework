<?php
return [
    // Application Information
    'APP' => [
        'NAME' => 'Application Name',
        'ENVIRONMENT' => getenv('APP_ENVIRONMENT'),
        'AUTHOR' => 'Francois Le Roux',
        'DESCRIPTION' => 'Application Description',
        'COPYRIGHT' => [
            'DATE' => date('Y'),
        ],
        'EMAIL' => 'francoisleroux97@gmail.com',
        'GITHUB' => 'https://github.com/Zezzman'
    ],
    // Method client used to connect to server
    'CLIENT_TYPE' => $this->clientType(),
    // Domain of the server
    'DOMAIN' => $this->domain(), // recommend: Hard code to domain name
    // Server name
    'HOST' => gethostname(),
    // Directory paths
    'PATHS' => [
        'ROOT' => dirname(__DIR__) . '/',
        'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] . '/',
    ],
    // Timezone of application
    'TIMEZONE' => 'Australia/Brisbane',
];