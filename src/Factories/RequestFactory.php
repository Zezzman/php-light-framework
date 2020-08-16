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
        self::httpRouteMap($request, $requestString, $availableParams);
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
    private static function httpRouteMap(HttpRequestModel $request, string $requestString, array $availableParams = [])
    {
        // Route alias changes route matching
        $definedRoutes = [
            '/' => ['home', 'index']
        ];

        $request->route = [];
        $requestString = preg_replace("/[&](.+)/", '', $requestString);

        // Set alias route
        foreach ($definedRoutes as $key => $value) {
            if (preg_match("#(^|\s)$key#", $requestString)) {
                $layout = [];
                $layoutSize = 0;
                for ($i = (count($value) - 1); $i >= 0; $i--) {
                    $index = $value[$i];
                    if ($i !== (count($value) - 1))
                    {
                        $layout = [$index => $layout];
                        $layoutSize++;
                    }
                    else
                    {
                        $layout = [$index => $request];
                        $layoutSize++;
                    }
                }
                $request->route = (array) $layout;
                $request->listed = $value;
                return;
            }
        }
        $requestString = trim($requestString, '/');
        if (! empty($requestString)) {
            // map route
            $params = explode('/', $requestString);
            $paramSize = count($params);
            $layout = [];
            $layoutSize = 0;
            for ($i = ($paramSize - 1); $i >= 0; $i--) {
                $value = $params[$i];
                if (empty($value)) continue; 
                $index = trim($value, '.');
                if ((strpos($index, '{')) !== false) {
                    $index = trim($index, '{}');
                    // self::setParam($request, $index);
                    if (substr($value, -3) === '...') {
                        $items = array_slice($availableParams, ($i + 1));
                        $request->params[$index] = $items;
                        $request->expanding = true;
                    }
                    else
                    {
                        if (isset($availableParams[$i])) {
                            $request->params[$index] = $availableParams[$i];
                        } else {
                            $request->params[$index] = '';
                        }
                    }
                }
                if ($i !== ($paramSize - 1))
                {
                    $layout = [$index => $layout];
                    $layoutSize++;
                }
                else
                {
                    $layout = [$index => $request];
                    $layoutSize++;
                }
            }
            $request->route = (array) $layout;
            $request->listed = $params;
            $request->size = $layoutSize;
        }
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
        // Get parameters for requestPattern
        if (strpos($request->requestPattern, '{') !== false) {
            $routes = $request->route;
            $matches = array_values($routes);
            $matchLength = count($matches);
            $append = null;
            if (isset($request->params['append'])) {
                $append = $request->params['append'];
                $matchLength--;
            }
            // clean request parameters
            $params = [];
            $request->params = [];
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