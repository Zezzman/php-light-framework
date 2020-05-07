<?php
// Directory paths relative to ROOT
$root = config('PATHS.ROOT');
return [
    'PATHS' => [
        'APP' => $root,
        'PUBLIC' => 'public/',
        'SRC' => 'src/',
        'ROUTES' => 'routes/',
        'VENDOR' => 'vendor/',
        'STORAGE' => 'storage/',
        'RESOURCES' => ['resources/'],
    ],
];