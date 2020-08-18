<?php
namespace System\Interfaces;
/**
 * 
 */
interface IRequest
{
    function valid();

    function output(string $file);
    function staticView(int $refreshRate, string $refreshType, string $path, string $file);

    static function empty();
}