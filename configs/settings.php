<?php
// Settings for how the application works
return [
    'SETTINGS' => [
        // Display 503 Service Unavailable
        'MAINTENANCE' => (getenv('MAINTENANCE') == true),
        // Show debug output
        'DEBUG' => (getenv('DEBUG') == true),
        // Log errors to file
        'LOG_ERRORS' => false,
        // Disable cache
        'NO_CACHE' => false,
    ]
];