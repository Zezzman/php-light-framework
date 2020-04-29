<?php
// Permissions for what the application is allowed to do
return [
    'PERMISSIONS' => [
        // Display 503 Service Unavailable
        'MAINTENANCE' => (getenv('MAINTENANCE') == true),
        // Show debug output
        'DEBUG' => (getenv('DEBUG') == true),
        'SHOW_CONFIGURATIONS' => false,
        'ALLOW_GUESTS' => true,
        'ALLOW_UPLOADS' => false,
        'LOG_ERRORS' => false,
        'NO_CACHE' => false,
    ]
];