<?php
error_reporting(0);
chdir(dirname(__DIR__));

/**
 * Register Composer auto loader
 */
if (is_file(dirname(__DIR__) . '/vendor/autoload.php'))
{
    require_once(dirname(__DIR__) . '/vendor/autoload.php');
}
else
{
    echo 'Autoload Not Found';
    throw new \Exception('Autoload Not Found');
    exit();
}
/**
 * Load Application
 */
if (is_file($launcher = dirname(__DIR__) . '/src/Launcher.php'))
{
    require_once($launcher);
}
else
{
    echo 'Launcher Not Found';
    throw new \Exception('Launcher Not Found: ' . $launcher);
    exit();
}