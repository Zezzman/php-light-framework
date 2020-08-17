<?php
/* $provider->get('uri', 'Controller@Action'); */
/**
 *  Documentation
 */
$this->get('/', 'Home@Index');
$this->get('home/', 'Home@Document');
$this->get('home/index', 'Home@Document');
$this->get('home/{index}', 'Home@Document');
$this->get('{home}/{index}', 'Home@Document');
$this->get('{home}/index', 'Home@Document');
$this->get('home/index/{next}', 'Home@Document');
$this->get('home/{next}...', 'Home@Document');
$this->get('home/{index}/{next}...', 'Home@Document');
