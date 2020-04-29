<?php
namespace System\Interfaces;
/**
 * 
 */
interface IRequest
{
    function valid();

    static function empty();
}