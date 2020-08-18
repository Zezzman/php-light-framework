<?php
namespace System\Models;

use System\Interfaces\IRequest;
/**
 * 
 */
abstract class RequestModel implements IRequest
{
    public $timestamp = null;

    public $type = null;
    public $controller = null;
    public $action = null;
    public $params = [];
    public $response = null;
    public $message = null;
    
    public $onMatching = [];
    public $onMatched = [];
    public $onProcessed = [];
    public $onRendered = [];

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
     * Output Static View to file
     * 
     */
    public abstract function output(string $file);
    /**
     * Output Static View to file
     * and output new view at set refresh rate
     * 
     */
    public abstract function staticView(int $refreshRate, string $refreshType, string $path, string $file);
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

    /* Events */
    /**
     * On Matching Event
     */
    public function triggerMatching()
    {
        foreach ($this->onMatching as $action)
        {
            if (\is_callable($action))
            {
                if ($action($this) === false)
                {
                    $this->onMatching = [];
                    return false;
                }
            }
        }
        $this->onMatching = [];
        return true;
    }
    /**
     * On Matched Event
     */
    public function triggerMatched()
    {
        foreach ($this->onMatched as $action)
        {
            if (\is_callable($action))
            {
                if ($action($this) === false)
                {
                    $this->onMatched = [];
                    return false;
                }
            }
        }
        $this->onMatched = [];
        return true;
    }
    /**
     * On Processed Event
     */
    public function triggerProcessed()
    {
        foreach ($this->onProcessed as $action)
        {
            if (\is_callable($action))
            {
                if ($action($this) === false)
                {
                    $this->onProcessed = [];
                    return false;
                }
            }
        }
        $this->onProcessed = [];
        return true;
    }
    /**
     * On Rendered Event
     */
    public function triggerRendered()
    {
        foreach ($this->onRendered as $action)
        {
            if (\is_callable($action))
            {
                if ($action($this) === false)
                {
                    $this->onRendered = [];
                    return false;
                }
            }
        }
        $this->onRendered = [];
        return true;
    }
    /**
     * Execute Function When Matching
     */
    public function onMatching(\Closure $func, string $name = null)
    {
        if ($this->valid()) {
            if (! \is_null($name))
            {
                $this->onMatching[$name] = $func;
            }
            else
            {
                $this->onMatching[] = $func;
            }
        }
        return $this;
    }
    /**
     * Execute Function When Matched
     */
    public function onMatched(\Closure $func, string $name = null)
    {
        if ($this->valid()) {
            if (! \is_null($name))
            {
                $this->onMatching[$name] = $func;
            }
            else
            {
                $this->onMatching[] = $func;
            }
        }
        return $this;
    }
    /**
     * Execute Function When Request Has Been Processed By Controller
     */
    public function onProcessed(\Closure $func, string $name = null)
    {
        if ($this->valid()) {
            if (! \is_null($name))
            {
                $this->onProcessed[$name] = $func;
            }
            else
            {
                $this->onProcessed[] = $func;
            }
        }
        return $this;
    }
    /**
     * Execute Function When View Has Rendered
     */
    public function onRendered(\Closure $func, string $name = null)
    {
        if ($this->valid()) {
            if (! \is_null($name))
            {
                $this->onRendered[$name] = $func;
            }
            else
            {
                $this->onRendered[] = $func;
            }
        }
        return $this;
    }
}