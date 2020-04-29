<?php
namespace System\Interfaces;
/**
 *
 * @author  Francois Le Roux <francoisleroux97@gmail.com>
 */
interface IDatabase
{
    /**
     * 
     */
    static function instance();
    /**
     * 
     */
    static function connect();
    /**
     * 
     */
    static function DB();
    /**
     * 
     */
    static function close();
    /**
     * 
     */
    static function lastID();
    /**
     * 
     */
    static function logError(int $user_id, string $message, $ip = '');
}