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
    'CLOSURES' =>
    [
        'RESOURCE' => function (string $name)
        {
            $root = requireConfig('PATHS.ROOT');
            $resources = (array) requireConfig('PATHS.RESOURCES');
            foreach ($resources as $resource)
            {
                if (file_exists($path = $root . $resource .  $name))
                    return $path;
            }
        }
    ]
];