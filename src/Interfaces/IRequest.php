<?php
namespace System\Interfaces;
/**
 * 
 */
interface IRequest
{
    function valid();
    function isEmpty();
    function match($pattern);
    function replace($pattern, $replace);

    function cache(string $path = '', string $file = '');
    function output(string $path = '', string $file = '');
    function staticView(int $refreshRate = null, string $refreshType = 'minutes', string $path = 'public', string $file = '');

    static function empty();
}