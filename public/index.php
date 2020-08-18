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
$time = \System\Helpers\TimeHelper::createMicro();
$request = $app->getRequest();
$micro1 = $time->elapse(true);
$request->onRendered(function () use ($time, $micro1)
{
    echo $micro1 . '<br>';
    echo $micro2 = $time->elapse();
    $content = "Routing: $micro1, Rendering: $micro2;\n";
    // file_put_contents(config('PATHS.ROOT~STORAGE'). 'logs/output_'. $time->format('Y_m_d'). ".log", $content, FILE_APPEND);
});

/**
 * Run Application
 */
$app->run();

/**
 * Close application
 */
exit();