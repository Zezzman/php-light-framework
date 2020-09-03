<?php
namespace System\Factories;

use System\Models\RequestModel;
use System\Models\Requests\HttpRequestModel;
use System\Models\Requests\CLIRequestModel;
use System\Helpers\QueryHelper;
use System\Interfaces\IRequest;

/**
 * 
 */
class RequestFactory
{
    /**
     * Simple request creation
     */
    public static function simpleRequest(string $requestType, string $requestString, string $method, string $type)
    {
        if ($requestType == 'http')
        {
            return self::simpleHttpRequest($requestString, $method, $type);
        }
        throw new Exception('Request Type Not Supported');
    }
    /**
     * Request creation
     */
    public static function request(string $requestType, string $requestString, string $actionString, string $method, string $type, array $availableParams = [])
    {
        if ($requestType == 'http')
        {
            return self::httpRequest($requestString, $actionString, $method, $type, $availableParams);
        }
        throw new Exception('Request Type Not Supported');
    }
    /**
     * Request creation
     */
    public static function emptyRequest(string $requestType)
    {
        if ($requestType == 'http')
        {
            return self::emptyHttpRequest();
        }
        throw new Exception('Request Type Not Supported');
    }
    
    /**
     * Simple Http request creation
     */
    public static function simpleHttpRequest(string $requestString, string $method, string $type)
    {
        $request = new HttpRequestModel($requestString);
        $request->uri = $requestString;
        $request->method = $method;
        $request->type = $type;
        if (! self::httpRouteMap($request, $requestString)) return null;
        return $request;
    }
    /**
     * Http request creation
     */
    public static function httpRequest(string $requestString, string $actionString, string $method, string $type, HttpRequestModel $clientRequest = null, array &$designatedRoutes = [])
    {
        $request = new HttpRequestModel($requestString);
        $request->uri = $requestString;
        $request->method = $method;
        $request->type = $type;
        if (! self::httpRouteMap($request, $requestString, $clientRequest, $designatedRoutes)) return null;
        self::controllerAction($request, $actionString);
        return $request;
    }
    /**
     * Empty Http request
     */
    public static function emptyHttpRequest()
    {
        return HttpRequestModel::empty();
    }
    /**
     * 
     */
    private static function httpRouteMap(HttpRequestModel $request, string $requestString, HttpRequestModel $clientRequest = null, array &$designatedRoutes = [])
    {
        // Route alias changes route matching
        $definedRoutes = [
            '/' => 'home/index'
        ];

        $request->route = [];
        $requestString = preg_replace("/[&](.+)/", '', $requestString);

        // Set alias route
        foreach ($definedRoutes as $key => $value) {
            if (preg_match("#(^|\s)$key#", $requestString)) {
                $requestString = $value;
            }
        }
        $requestString = trim($requestString, '/');
        if (empty($requestString)) return false;
        
        // map route
        $params = explode('/', $requestString);
        $paramSize = count($params);
        $layoutSize = 0;
        $layout = [];
        $entry = &$layout;
        if (empty($clientRequest->route ?? []))
        {
            for ($i = 0; $i < $paramSize; $i++)
            {
                $layoutSize++;
                $key = $params[$i];
                $entry[$key] = [];
                $entry = &$entry[$key];
            }
            $entry = $request;
            unset($entry);
            $request->route = (array) $layout;
            $request->listed = $params;
            $request->size = $layoutSize;
            $request->requestPattern = $requestString;
            return true;
        }
        $route = ($clientRequest->route ?? []);
        $routes = $designatedRoutes;
        $routesEntry = &$routes;
        for ($i = 0; $i < $paramSize; $i++)
        {
            $value = $params[$i];
            $key = trim($value, '.{}');
            if (! isset($routesEntry[$key]))
            {
                $routesEntry[$key] = [];
            }
            $routesEntry = &$routesEntry[$key];
            $entry[$key] = [];
            $entry = &$entry[$key];
            $layoutSize++;
            if (! is_object($route))
            {
                if ((strpos($value, '{')) !== false) {
                    self::setParam($request, $i, $value, $clientRequest->listed);
                    if ($request->expanding) break;
                    
                    $keys = array_keys($route);
                    $route = $route[$keys[0]];
                    continue;
                }
                if (isset($route[$key]))
                {
                    $route = $route[$key];
                    continue;
                }
            }
            return false;
        }
        if ($layoutSize !== $clientRequest->size && ! $request->expanding) return false;
        if (($request->method !== $clientRequest->method)) return false;
        
        $entry = $request;
        $routesEntry[] = $request;
        $designatedRoutes = $routes;

        $request->route = (array) $layout;
        $request->listed = $params;
        $request->size = $layoutSize;
        $request->requestPattern = $requestString;
        return true;
    }
    /**
     * 
     */
    public static function controllerAction(RequestModel $request, string $actionString)
    {
        // Get controller and action value
        $params = explode('@', $actionString);
        
        if (isset($params[0])) {
            $controller = $params[0];
            // Set current controller
            $request->controller = $controller;

            if (isset($params[1])) {
                $action = $params[1];
                // Set current action
                $request->action = $action;
                return true;
            }
        }
        return false;
    }
    /**
     * 
     */
    public static function setParam(HttpRequestModel $request, int $position, string $index, array $availableParams = [])
    {
        $key = trim($index, '.{}');
        if (substr($index, -3) === '...') {
            $items = array_slice($availableParams, ($position + 1));
            $request->params[$key] = $items;
            $request->expanding = true;
        }
        else
        {
            if (isset($availableParams[$position])) {
                $request->params[$key] = $availableParams[$position];
            } else {
                $request->params[$key] = '';
            }
        }
    }
    /**
     * 
     */
    public static function setURI(HttpRequestModel $request)
    {
        $params = $request->params;
        if (is_array($params) && ! empty($params)) {
            $request->uri = QueryHelper::scanCodes($params, $request->requestPattern);
        } else {
            $request->uri = $request->requestPattern;
        }
    }

    /**
     * CLI request creation
     */
    public static function simpleCLIRequest(array $commands)
    {
        $args = self::formatArgs($commands);
        $request = new CLIRequestModel($args['commands'], $args['inputs'], $args['flags']);
        return $request;
    }
    /**
     * CLI request creation
     */
    public static function cliRequest(array $commands, array $params, array $flags, string $actionString)
    {
        $request = new CLIRequestModel($commands, $params, $flags);
        self::controllerAction($request, $actionString);
        return $request;
    }
    /**
     * CLI request creation
     */
    public static function emptyCLIRequest()
    {
        return CLIRequestModel::empty();
    }

    /**
     * 
     */
    public static function formatArgs(array $args)
    {
        $formatedArgs = [
            'commands' => [],
            'flags' => [],
            'inputs' => [],
        ];
        for ($i = 1; $i < count($args); $i++) {
            $argument = $args[$i];
            if ($argument !== '--'
            && preg_match("/(^--)(.*)/", $argument, $matches)) {
                // commands
                $matches;
                $command = trim($argument, '-');
                $flags = [];
                $inputs = [];
                while (($i + 1) < count($args)
                && ! preg_match("/(^--)(.*)/", $args[$i+1])) {
                    $argument = $args[++$i];
                    if (preg_match("/(^-)(.*)/", $argument)) {
                        // flags
                        $argument = trim($argument, '-');
                        if ($argument !== '') {
                            $flags[$argument] = true;
                        }
                    } else {
                        // input
                        $inputs[] = $argument;
                    }
                }
                $formatedArgs['commands'][$command] = [
                    'flags' => $flags,
                    'inputs' => $inputs
                ];
            } elseif (preg_match("/(^-)(.*)/", $argument)) {
                // flags
                $argument = trim($argument, '-');
                if ($argument !== '') {
                    $formatedArgs['flags'][$argument] = true;
                }
            } else {
                // input
                $formatedArgs['inputs'][] = $argument;
            }
        }
        
        return $formatedArgs;
    }
}