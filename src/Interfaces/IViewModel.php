<?php
namespace System\Interfaces;

use Exception;
/**
 * 
 */
interface IViewModel
{
    function getTitle();
    function setTitle(string $title);
    function messages($types, string $name, string $style, int $length);
    function respond(int $code, $types, string $name, string $style, int $length, Exception $exception);
}