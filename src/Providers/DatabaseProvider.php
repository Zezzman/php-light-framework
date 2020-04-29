<?php
namespace System\Providers;

use System\Databases\MySQLDatabase;
use System\Databases\JSONDatabase;
/**
 * Provides database
 * 
 * connect to selected database
 */
final class DatabaseProvider
{
    private static $database = null;
    private static $connection = null;

    private function __construct() {}

    public static function database ()
    {
        return self::$database;
    }
    public static function connection ()
    {
        return self::$connection;
    }
    
    public static function connectMySQL()
    {
        if (is_null(self::$connection)) {
            MySQLDatabase::connect();
            self::$connection = MySQLDatabase::DB();
            self::$database = MySQLDatabase::instance();
            return self::$database;
        } elseif (self::$connection == MySQLDatabase::DB()) {
            return self::$database;
        }
        return null;
    }
    public static function connectJSON()
    {
        if (is_null(self::$connection)) {
            JSONDatabase::connect();
            self::$connection = JSONDatabase::DB();
            self::$database = JSONDatabase::instance();
            return self::$database;
        } elseif (self::$connection == JSONDatabase::DB()) {
            return self::$database;
        }
        return null;
    }
}