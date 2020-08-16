<?php
namespace System\Interfaces;
/**
 * 
 */
interface IRequest
{
    function valid();

    function output(string $file);
    function staticView(string $file, int $refreshRate);

    static function empty();
}