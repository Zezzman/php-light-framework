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
