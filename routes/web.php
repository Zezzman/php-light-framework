<?php
/* $provider->get('uri', 'Controller@Action'); */
/**
 *  Documentation
 */
// $this->get('/', 'Home@Index')->output(config('PATHS.ROOT~STORAGE'));
$this->get('/', 'Home@Index');
$this->get('document/', 'Home@Document');
/**
 *  Image Convert to Data
 */
$this->get('imageData/', 'Storage@ImageConvert')->cache(config('PATHS.PUBLIC'));
$this->post('imageData/', 'Storage@ImageConvert')->output(config('PATHS.PUBLIC'));
