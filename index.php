<?php
/**
 * Bootstrap Application
 */
require_once(__DIR__ . '/bootstrap/app.php');

/**
 * Create Application
 */
$app = Launcher::setup();

header('Location: ' . requireConfig('LINKS.PUBLIC'));