<?php
namespace System\Models\Requests;

use System\Interfaces\IViewModel;
use System\Models\RequestModel;
use System\Providers\EnvironmentProvider;
use System\Providers\AuthProvider;
use System\Helpers\HTTPHelper;
/**
 * 
 */
class HttpRequestModel extends RequestModel
{
    public $uri = '';
    public $requestPattern = '';
    public $route = null;
    public $method = '';
    public $redirect = null;
    public $view = null;
    public $model = null;
    public $settings = [];
    public $onMatching = [];
    public $onMatched = [];

    /**
     *  Initiate a request
     * 
     * @param   string  $uri        request query
     */
    public function __construct(string $requestPattern = '')
    {
        $this->requestPattern = $requestPattern;
    }
    /**
     * Check if request is valid
     * 
     * Request needs specific fields filled
     * to be a valid request
     * 
     * @return   boolean    returns true if request is valid
     */
    public function valid()
    {
        if (empty($this->uri)
        || ! is_null($this->response)
        || ! is_array($this->route)
        || empty($this->route)
        || empty($this->type)) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * Compare given method with request method
     */
    public function requestMethod($method)
    {
        if (! empty($this->method) && ! empty($method)
        && $this->method !== $method)
        {
            return false;
        }
        return true;
    }
    /**
     * Match requests
     */
    public function matchRequest(self $request)
    {
        if (! isset($request->route)) {
            return false;
        }
        return $this->matchRoutes($request->route);
    }
    /**
     * Match routes
     */
    public function matchRoutes(array $route)
    {
        $route1 = $this->route;
        $route2 = $route;
        reset($route1);
        reset($route2);

        if (count($route1) === count($route2) || array_key_exists('append', $route2)) {
            foreach ($route2 as $key => $value) {
                $index = trim($value, '{}');
                if ($key !== 'append' && $index === $value) {
                    if (isset($route1[$key])) {
                        if ($value !== $route1[$key]) {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }
    /**
     * On Matching Event
     */
    public function isMatching()
    {
        foreach ($this->onMatching as $action)
        {
            if (\is_callable($action))
            {
                if ($action() === false)
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
    public function isMatched()
    {
        foreach ($this->onMatched as $action)
        {
            if (\is_callable($action))
            {
                if ($action() === false)
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
     * Search settings
     */
    private function settings(string $key, $default = false)
    {
        return EnvironmentProvider::searchConfig($this->settings, $key, config($key, $default));
    }
    /**
     * Get request parameters
     */
    private function getParams($params)
    {
        $requestParams = $this->params;
        $params = (array) $params;
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $index = trim($value, '{}');
                if ($value !== $index) {
                    $content = null;
                    if (is_numeric($key)) {
                        unset($params[$key]);
                        if (isset($requestParams[$index])) {
                            $content = $requestParams[$index];
                        } elseif (HTTPHelper::isGet($index)) {
                            $content = HTTPHelper::get($index);
                        }
                    } else {
                        if (isset($requestParams[$index])) {
                            $content = $requestParams[$index];
                        } elseif (HTTPHelper::isGet($index)) {
                            $content = HTTPHelper::get($index);
                        }
                        $index = $key;
                    }
                    $params[$index] = $content;
                }
            }
        }
        return $params;
    }

    /* Matching Checks */
    /**
     * Set request parameters
     */
    public function setParams($params)
    {
        if (! empty($params)
        && $this->valid())
        {
            $request = $this;
            $this->onMatching[] = function () use ($request, $params)
            {
                $request->params = $request->getParams($params);
            };
        }
        
        return $this;
    }
    /**
     * Add to request parameters
     * 
     */
    public function addParams($params)
    {
        if (! empty($params)
        && $this->valid())
        {
            $request = $this;
            $this->onMatching[] = function () use ($request, $params)
            {
                $request->params = array_merge($request->params, $request->getParams($params));
            };
        }
        return $this;
    }
    /**
     * Check request header
     */
    public function hasHeader($header)
    {
        $serverHeaders = $_SERVER;
        $headers = (array) $header;
        if ($this->valid() && ! empty($headers))
        {
            $request = $this;
            $this->onMatching[] = function () use ($request, $headers, $serverHeaders)
            {
                foreach ($headers as $key => $header)
                {
                    if (is_numeric($key) && is_string($header))
                    {
                        if (! array_key_exists($header, $serverHeaders))
                        {
                            $request->respond(404, 'Headers do not match');
                        }
                    }
                    elseif (is_string($key) && is_string($header))
                    {
                        if (! array_key_exists($key, $serverHeaders)
                        || $serverHeaders[$key] !== $header)
                        {
                            $request->respond(404, 'Headers do not match');
                        }
                    }
                    else
                    {
                        $request->respond(404, 'Headers do not match');
                    }
                }
            };
        }
        return $this;
    }
    /**
     * 
     */
    public function hasMethod($method)
    {
        $methods = (array) $method;
        if ($this->valid() && ! empty($this->method) && ! empty($method))
        {
            $request = $this;
            $this->onMatching[] = function () use ($request, $methods)
            {
                foreach ($methods as $key => $value)
                {
                    $methods[$key] = trim($value);
                }
                if (! (in_array($request->method, $methods)
                || $request->method === 'OPTIONS')) {
                    $request->respond(405, 'Request Method not allowed');
                }
            };
        }
        return $this;
    }
    /**
     * Set View
     */
    public function setView(string $view, IViewModel $model = null)
    {
        if ($this->valid() && ! empty($view))
        {
            $request = $this;
            $this->onMatching[] = function () use ($request, $view, $model)
            {
                $request->view = $view;
                $request->model = $model;
            };
        }
        return $this;
    }
    /**
     * Only Allow Authorized Users
     */
    public function isAuth()
    {
        if ($this->valid())
        {
            $request = $this;
            $this->onMatching[] = function () use ($request)
            {
                if (! AuthProvider::isAuthorized())
                {
                    $guestsRedirect = $request->settings('AUTH.GUEST.RESTRICTED_REDIRECT_URL');
                    if (is_string($guestsRedirect)
                    && ! empty($guestsRedirect))
                    {
                        $request->respond(404);
                        $request->redirect((string) $guestsRedirect);
                    }
                    else
                    {
                        if ($request->settings('AUTH.USER.VISIBLE_RESTRICTIONS', true) === true)
                        {
                            $request->respond(401, 'Request requires authorization');
                        }
                        else
                        {
                            $request->respond(404);
                        }
                    }
                }
            };
        }
        return $this;
    }
    /**
     * Only Allow Guests
     */
    public function isGuest()
    {
        if ($this->valid())
        {
            $request = $this;
            $this->onMatching[] = function () use ($request)
            {
                if (AuthProvider::isAuthorized())
                {
                    if ($request->settings('AUTH.GUEST.VISIBLE_RESTRICTIONS', true) === true)
                    {
                        $request->respond(403, 'Request only allowed as guest');
                    }
                    else
                    {
                        $request->respond(404);
                    }
                }
            };
        }
        return $this;
    }
    /**
     * Check if requested file extension is valid
     */
    public function hasExtension($ext)
    {
        $extensions = (array) $ext;
        if ($this->valid()) {
            $request = $this;
            $this->onMatching[] = function () use ($request, $extensions)
            {
                foreach ($extensions as $file => $type) {
                    $extension = '';
                    if (is_numeric($file)) {
                        if (isset($request->params['ext'])) {
                            $extension = $request->params['ext'];
                        } elseif (isset($request->params['file'])) {
                            $file = $request->params['file'];
                            $pos = strpos(strrev($file), '.');
                            $extension = substr($file, -$pos);
                        }
                        if (! in_array($extension, $ext)) {
                            $request->respond(415, 'Invalid File Extension');
                            return;
                        }
                    } elseif (is_string($file)) {
                        if (isset($request->params[$file])) {
                            $file = $request->params[$file];
                            $pos = strpos(strrev($file), '.');
                            $extension = substr($file, -$pos);
                        }
                        if (is_array($type)) {
                            if (! in_array($extension, $type)) {
                                $request->respond(415, 'Invalid File Extension');
                                return;
                            }
                        } elseif (is_string($type)) {
                            if ($extension !== $type) {
                                $request->respond(415, 'Invalid File Extension');
                                return;
                            }
                        }
                    }
                }
            };
        }
        return $this;
    }
    /**
     * Declare redirection
     */
    public function redirect(string $uri, bool $clearParams = true, int $code = 307)
    {
        if (! $this->valid()) {
            $request = $this;
            $this->onMatching[] = function () use ($request, $uri, $clearParams, $code)
            {
                if (is_null($request->redirect))
                {
                    $request->redirect = $uri;
                    if ($clearParams === true) {
                        $request->params = [];
                    }
                    $request->onMatched['redirect'] = function () use ($request, $code)
                    {
                        http_response_code($code);
                    };
                }
            };
        }
        return $this;
    }
    /**
     * Change Route Controller and Action
     * 
     * @param   string      $actionString       'Controller@Action'
     */
    public function changeAction(string $actionString)
    {
        if ($this->valid())
        {
            $request = $this;
            $this->onMatching[] = function () use ($request, $actionString)
            {
                RequestFactory::controllerAction($request, $actionString);
            };
        }
        return $this;
    }
    /**
     * Close request on $state true
     */
    public function close($state)
    {
        if ($this->valid()) {
            $this->onMatching[] = function ()
            {
                if (\is_callable($state))
                {
                    if (! empty($state()))
                    {
                        return false;
                    }
                }
                else if (! empty($state))
                {
                    return false;
                }
            };
        }
        return $this;
    }
    /**
     * Close request if request isn't valid
     */
    public function isValid()
    {
        if ($this->valid()) {
            $this->onMatching[] = function ()
            {
                if (! $this->valid())
                {
                    return false;
                }
            };
        }
        return $this;
    }
    /* Post-matching Checks */
    /**
     * Set request environment
     */
    public function setEnvironment($environment)
    {
        if (is_string($environment) || is_array($environment))
        {
            if ($this->valid()) {
                if (is_string($environment)) {
                    $this->settings = EnvironmentProvider::instance()->loadEnvironment($environment);
                }
                else if (is_array($environment))
                {
                    $this->settings = $environment;
                }
                $request = $this;
                $this->onMatched['setEnvironment'] = function () use ($request)
                {
                    if (is_array($request->settings) && ! empty($request->settings))
                    {
                        EnvironmentProvider::instance()->add($request->settings);
                    }
                };
            }
        }
        return $this;
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
}