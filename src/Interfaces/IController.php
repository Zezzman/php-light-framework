<?php
namespace System\Interfaces;

use Exception;
/**
 * 
 */
interface IController
{
    function getRequest();
    function isMethod(string $method);
    function render();
    
    static function respond(int $code, string $message = null, IRequest $request = null, Exception $exception = null);
}