<?php
namespace System\Models;

/**
 * 
 */
class SecretModel
{
    public $secrets = null;

    public function __construct(array $secrets)
    {
        $this->set($secrets);
    }

    /**
     * 
     */
    public function set(array $secrets){
        $this->secrets = $secrets;
    }
    /**
     * 
     */
    public function get(){
        return $this->secrets;
    }

    /**
     * 
     */
    public function index(int $index, $default = false){
        if (/* $index < 0 || */ ! isset($this->secrets[$index])) return $default;
        
        return $this->secrets[$index];
    }
}