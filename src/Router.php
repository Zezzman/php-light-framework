<?php
namespace System;

use System\Helpers\HTTPHelper;
use System\Providers\AuthProvider;
use System\Providers\RequestProvider;
use System\Providers\Requests\HttpRequestProvider;
use System\Providers\Requests\CLIRequestProvider;
use System\Models\HttpRequestModel;
use System\Models\CLIRequestModel;
use Exception;

/**
 * Process client requests into request object
 * 
 * @author  Francois Le Roux <francoisleroux97@gmail.com>
 */
class Router
{
    private $request = null;
    private $requestType = null;
    private $requestMethod = null;
    /**
     * 
     */
    public function __construct()
    {
        $this->requestMethod = getenv('REQUEST_METHOD');
        $this->requestType = config('CLIENT_TYPE');
    }
    /**
     * 
     */
    public function request()
    {
        return $this->request;
    }
    /**
     * 
     */
    public function type()
    {
        return $this->requestType;
    }
    /**
     * 
     */
    public function method()
    {
        return $this->requestMethod;
    }
    /**
     * 
     */
    public function webRoutes()
    {
        // Client request
        $uri = HTTPHelper::URI();
        // Load available request
        $provider = new RequestProvider('http', config('PATHS.ROOT~ROUTES') . 'web.php', $uri);
        // Find matching request
        $this->request = $provider->matchRequests();
        return $this->request;
    }
    /**
     * 
     */
    public function apiRoutes()
    {
        $uri = HTTPHelper::URI();
        $provider = new RequestProvider('http', config('PATHS.ROOT~ROUTES') . 'api.php', $uri);
        if ($this->requestMethod === 'OPTIONS') {
            header('Access-Control-Allow-Origin: https://localhost');
            header("Access-Control-Max-Age: 3600");
        }
        $this->request = $provider->matchRequests();
        return $this->request;
    }
    /**
     * 
     */
    public function cliRoutes()
    {
        $commands = config('APP.ARGV');
        $provider = new CLIRequestProvider($commands);
        $cli = config('PATHS.ROOT~ROUTES') . 'cli.php';
        if (file_exists($cli)) {
            require($cli);
        } else {
            throw new Exception('CLI Route File Not Found');
        }
        $this->request = $provider->matchRequests($commands);
        return $this->request;
    }
    /**
     * 
     */
    public function cronjobRoutes()
    {

    }
}