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
 * Requests
 */
$app->getRequest();

/**
 * Run Application
 */
$app->run();

/**
 * Close application
 */
exit();