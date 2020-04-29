<?php
namespace System\Models;

use System\Interfaces\IRequest;
/**
 * 
 */
abstract class RequestModel implements IRequest
{
    public $type = null;
    public $controller = null;
    public $action = null;
    public $params = [];
    public $response = null;
    public $message = null;

    /**
     * Check if request is valid
     * 
     * Request needs specific fields filled
     * to be a valid request
     * 
     * @return   boolean    returns true if request is valid
     */
    public abstract function valid();
    /**
     * Empty Request
     */
    public static function empty()
    {
        return new static();
    }
    /**
     * Set Response code
     * 
     * When request is handled the response will be set
     * 
     * @param   int     $code       response code
     */
    public function respond(int $code, string $message = null)
    {
        $this->response = $code;
        if (! is_null($message))
        {
            $this->message = $message;
        }
        return $this;
    }
}