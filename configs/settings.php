<?php
// Settings for how the application works
return [
    'SETTINGS' => [
        // Display 503 Service Unavailable
        'MAINTENANCE' => (getenv('MAINTENANCE') == true),
        // Show debug output
        'DEBUG' => (getenv('DEBUG') == true),
        // Log structure
        'LOG_STRUCTURE' => (getenv('LOG_STRUCTURE') == true),
        // Disable cache
        'NO_CACHE' => (getenv('NO_CACHE') == true),
        // Use Minified Scripts and CSS
        'MIN_SCRIPTS' => true
    ]
];