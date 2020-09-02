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
        if (config('SETTINGS.LOG_STRUCTURE', false)) echo "Web Request: $uri<br>\n";
        // Load available request
        $provider = new RequestProvider('http', config('PATHS.ROOT~ROUTES') . 'web.php', $uri);
        if (config('SETTINGS.LOG_STRUCTURE', false)) echo "Load Available Requests<br>\n";
        // Find matching request
        $this->request = $provider->matchRequests();
        if (config('SETTINGS.LOG_STRUCTURE', false)) echo "Matched Requests<br>\n";

        return $this->request;
    }
    /**
     * 
     */
    public function apiRoutes()
    {
        $uri = HTTPHelper::URI();
        if (config('SETTINGS.LOG_STRUCTURE', false)) echo "API Request: $uri<br>\n";

        $provider = new RequestProvider('http', config('PATHS.ROOT~ROUTES') . 'api.php', $uri);
        if (config('SETTINGS.LOG_STRUCTURE', false)) echo "Load Available Requests<br>\n";

        if ($this->requestMethod === 'OPTIONS') {
            header('Access-Control-Allow-Origin: '. \config('DOMAIN'));
            header("Access-Control-Max-Age: 3600");
        }
        $this->request = $provider->matchRequests();
        if (config('SETTINGS.LOG_STRUCTURE', false)) echo "Matched Requests<br>\n";

        return $this->request;
    }
    /**
     * 
     */
    public function cliRoutes()
    {
        if (config('SETTINGS.LOG_STRUCTURE', false)) echo "Process CLI Request\n";

        $commands = config('APP.ARGV');
        $provider = new CLIRequestProvider($commands);
        $cli = config('PATHS.ROOT~ROUTES') . 'cli.php';
        if (file_exists($cli)) {
            require($cli);
        } else {
            throw new Exception('CLI Route File Not Found');
        }
        $this->request = $provider->matchRequests($commands);
        if (config('SETTINGS.LOG_STRUCTURE', false)) echo "Matched Requests<br>\n";
        
        return $this->request;
    }
    /**
     * 
     */
    public function cronjobRoutes()
    {

    }
}