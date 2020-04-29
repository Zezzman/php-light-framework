<?php
/**
 * Bootstrap Application
 */
require_once(dirname(__DIR__) . '/bootstrap/app.php');

/**
 * Create Application
 */
$app = Launcher::setup();
setConfig('APP', ['ARGV' => ($argv ?? [])]);

/**
 * Routes
 */
$router = new System\Router();
$request = $router->request;

/**
 * Run Application
 */
$app->run($request);

/**
 * Close application
 */
exit();