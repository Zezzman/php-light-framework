<?php
if ($this->isAuth(1))
{
    $this->request('imageData/', 'Storage@ImageConvert')
    ->if(\System\Helpers\HTTPHelper::isGet('url') || \System\Helpers\HTTPHelper::isFile('file'))
    ->output('public/static/');
    
    return;
}
/* $provider->get('uri', 'Controller@Action'); */
/**
 *  Documentation
 */
$this->get('/', 'Home@Index');
$this->get('document/', 'Home@Document');
/**
 *  Image Convert to Data
 */
$this->request('imageData/', 'Storage@ImageConvert')->cache('public/static/');