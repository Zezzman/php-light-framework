<?php
namespace System;

use System\Interfaces\IRepository;
use Exception;
/**
 * 
 */
abstract class Repository implements IRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = $this->connect();
    }
    public function connection()
    {
        return $this->connection;
    }
    abstract public function connect();
}