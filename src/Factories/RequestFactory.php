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
        self::httpRouteMap($request, $requestString);
        return $request;
    }
    /**
     * Http request creation
     */
    public static function httpRequest(string $requestString, string $actionString, string $method, string $type, array $availableParams = [])
    {
        $request = new HttpRequestModel($requestString);
        $request->uri = $requestString;
        $request->method = $method;
        $request->type = $type;
        self::httpRouteMap($request, $requestString);
        self::controllerAction($request, $actionString);
        self::setParams($request, $availableParams);
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
    private static function httpRouteMap(HttpRequestModel $request, string $requestString)
    {
        // Route alias changes route matching
        $definedRoutes = [
            '/' => [
                'home',
                'index'
            ]
        ];

        $route = [];
        $requestString = preg_replace("/[&](.+)/", '', $requestString);

        // Set alias route
        foreach ($definedRoutes as $key => $value) {
            if (preg_match("#(^|\s)$key#", $requestString)) {
                $request->route = $value;
                return;
            }
        }
        $requestString = trim($requestString, '/');
        if (! empty($requestString)) {
            // map route
            $params = explode('/', $requestString);
            for ($i = 0; $i < count($params); $i++) {
                $value = $params[$i];
                $route[] = trim($value, '.');
                if ($i === count($params) - 1) {
                    if (strpos($value, '...') !== false) {
                        $route['append'] = trim($value, '.');
                    }
                }
            }
        }
        
        $request->route = $route;
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
    public static function setParams(HttpRequestModel $request, array $availableParams = [])
    {
        // clean request parameters
        $request->params = [];

        // Get parameters for requestPattern
        if (strpos($request->requestPattern, '{') !== false) {
            $params = [];
            $routes = $request->route;
            $matches = array_values($routes);
            $matchLength = count($matches);
            $append = null;
            if (isset($routes['append'])) {
                $append = $routes['append'];
                $matchLength--;
            }
            for ($i = 0; $i < $matchLength; $i++) {
                $match = $matches[$i];
                $index = trim($match, '{}');
                if (strpos($match, '{') !== false) {
                    if ($append == $match) {
                        $items = [];
                        $j = ($i - 1);
                        while (++$j < ($i + 10)) {
                            if (isset($availableParams[$j])) {
                                $items[] = $availableParams[$j];
                            } else {
                                break;
                            }
                        }
                        $params[$index] = $items;
                        break;
                    } else {
                        if (isset($availableParams[$i])) {
                            $params[$index] = $availableParams[$i];
                        } else {
                            $params[$index] = '';
                        }
                    }
                }
            }
            $request->params = $params;
        }
    }
    /**
     * 
     */
    public static function setURI(HttpRequestModel $request)
    {
        $params = $request->params;
        if (is_array($params) && ! empty($params)) {
            $request->uri = QueryHelper::insertCodes($params, $request->requestPattern);
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