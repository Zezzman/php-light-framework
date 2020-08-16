<?php
/* $provider->get('uri', 'Controller@Action'); */
/**
 *  Documentation
 */
$this->get('/', 'Home@Index');
$this->get('document/', 'Home@Document');
/**
 *  Image Convert to Data
 */
$this->request('imageData/', 'Storage@ImageConvert');

$this->request('data/height/lift/{ur}', 'Storage@ImageConvert');
$this->request('data/{al}...', 'Storage@ImageConvert');
