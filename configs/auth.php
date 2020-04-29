<?php
// Permissions for what the user is allowed to do
return [
    'AUTH' => [
        'ADMIN' => [],
        'USER' => [
            // Redirect to value on restricted page
            'RESTRICTED_REDIRECT_URL' => false,
            // when true, show restricted access or page not found
            'VISIBLE_RESTRICTIONS' => true,
        ],
        'GUEST' => [
            // Redirect to value on restricted page
            'RESTRICTED_REDIRECT_URL' => false,
            // when true, show restricted access or page not found
            'VISIBLE_RESTRICTIONS' => true,
        ],
    ]
];