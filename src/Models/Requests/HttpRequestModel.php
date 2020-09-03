<?php
namespace System\Models\Requests;

use System\Interfaces\IViewModel;
use System\Models\RequestModel;
use System\Providers\EnvironmentProvider;
use System\Providers\AuthProvider;
use System\Helpers\HTTPHelper;
use System\Helpers\TimeHelper;
use System\Helpers\FileHelper;
use System\Helpers\QueryHelper;
/**
 * 
 */
class HttpRequestModel extends RequestModel
{
    public $route = null;
    public $listed = null;
    public $size = 0;
    public $expanding = false;

    public $uri = '';
    public $requestPattern = '';
    public $method = '';
    public $redirect = null;
    public $view = null;
    public $viewString = null;
    public $model = null;
    public $settings = [];
    
    public $cachedLocation = null;

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
     * Check if request is empty
     */
    public function isEmpty()
    {
        if (empty($this->requestPattern)
        || $this->size == 0
        || empty($this->route)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Check if request match item
     * 
     * @param       mix         $pattern       item used to match against request
     * 
     * @return      boolean     returns true if request match item
     */
    public function match($pattern)
    {
        if (empty($this->requestPattern) || ! is_string($pattern)) return false;

        return (\preg_match($pattern, $this->requestPattern));
    }
    /**
     * Replace request pattern
     * 
     * @param       mix         $pattern       item used to match against request
     * @param       mix         $replace       replace item with this
     * 
     * @return      boolean     returns true if request match item
     */
    public function replace($pattern, $replace)
    {
        if (empty($this->requestPattern) || gettype($pattern) !== gettype($replace)
        || ! is_string($pattern)) return false;

        $oldRequest = $this->requestPattern;
        $newRequest = (\preg_replace($pattern, $replace, $oldRequest));

        if ($newRequest === $oldRequest) return false;
        $this->requestPattern = $newRequest;
        return $newRequest;
    }
    /**
     * Check if request is valid
     * 
     * Request needs specific fields filled
     * to be a valid request
     * 
     * @return   boolean    returns true if request is valid
     */
    public function validChain()
    {
        if ($this->chainState === false
        || ! is_null($this->response)
        || ! is_array($this->route)) {
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
     * Set Cache Locations
     */
    public function cache(string $path = '', string $file = '')
    {
        if ($this->validChain())
        {
            $path = ((empty($trimPath = trim($path, '/'))) ? '': $trimPath. '/');
            $this->onMatched(function ($self) use ($path, $file)
            {
                if (is_null($self->uri)) return;
                $newFile = QueryHelper::insertCodes($self->params, $file);
                $file = (empty($newFile) ? $file : $newFile);
                if (empty($file))
                {
                    $file = 'index.html';
                    $path = $path. ((empty($uri = trim($self->uri, '/'))) ? '': $uri. '/');
                }
                $self->cachedLocation = config('PATHS.ROOT'). $path. $file;
            });
        }
        return $this;
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
    /**
     * Set View
     */
    public function setView(string $viewString, IViewModel $model = null)
    {
        if ($this->validChain() && ! empty($viewString))
        {
            $request = $this;
            $request->viewString = $viewString;
            $request->model = $model;
        }
        return $this;
    }

    /* Matching Actions */
    /**
     * Set request parameters
     */
    public function setParams($params)
    {
        if (! empty($params)
        && $this->validChain())
        {
            $this->onMatching(function ($self) use ($params)
            {
                $self->params = $self->getParams($params);
            });
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
        && $this->validChain())
        {
            $this->onMatching(function ($self) use ($params)
            {
                $self->params = array_merge($self->params, $self->getParams($params));
            });
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
        if ($this->validChain() && ! empty($headers))
        {
            $request = $this;
            $this->onMatching(function () use ($request, $headers, $serverHeaders)
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
            });
        }
        return $this;
    }
    /**
     * 
     */
    public function hasMethod($method)
    {
        $methods = (array) $method;
        if ($this->validChain() && ! empty($this->method) && ! empty($method))
        {
            $request = $this;
            $this->onMatching(function () use ($request, $methods)
            {
                foreach ($methods as $key => $value)
                {
                    $methods[$key] = trim($value);
                }
                if (! (in_array($request->method, $methods)
                || $request->method === 'OPTIONS')) {
                    $request->respond(405, 'Request Method not allowed');
                }
            });
        }
        return $this;
    }
    /**
     * Only Allow Authorized Users
     */
    public function isAuth()
    {
        if ($this->validChain())
        {
            $request = $this;
            $this->onMatching(function () use ($request)
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
            });
        }
        return $this;
    }
    /**
     * Only Allow Guests
     */
    public function isGuest()
    {
        if ($this->validChain())
        {
            $request = $this;
            $this->onMatching(function () use ($request)
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
            });
        }
        return $this;
    }
    /**
     * Check if requested file extension is valid
     */
    public function hasExtension($ext)
    {
        $extensions = (array) $ext;
        if ($this->validChain()) {
            $request = $this;
            $this->onMatching(function () use ($request, $extensions)
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
            });
        }
        return $this;
    }
    /**
     * Declare redirection
     */
    public function redirect(string $uri, bool $clearParams = true, int $code = 307)
    {
        if (! $this->validChain()) {
            $request = $this;
            $this->onMatching(function () use ($request, $uri, $clearParams, $code)
            {
                if (is_null($request->redirect))
                {
                    $request->redirect = $uri;
                    if ($clearParams === true) {
                        $request->params = [];
                    }
                    $request->onMatched(function () use ($request, $code)
                    {
                        http_response_code($code);
                    }, 'redirect');
                }
            });
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
        if ($this->validChain())
        {
            $request = $this;
            $this->onMatching(function () use ($request, $actionString)
            {
                RequestFactory::controllerAction($request, $actionString);
            });
        }
        return $this;
    }
    /**
     * Close request if request isn't valid
     */
    public function isValid()
    {
        if ($this->validChain()) {
            $this->onMatching(function ()
            {
                if (! $this->valid())
                {
                    return false;
                }
            });
        }
        return $this;
    }
    /* Post-matching Actions */
    /**
     * Set request environment
     */
    public function setEnvironment($environment)
    {
        if (is_string($environment) || is_array($environment))
        {
            if ($this->validChain()) {
                if (is_string($environment)) {
                    $this->settings = EnvironmentProvider::instance()->loadEnvironment($environment);
                }
                else if (is_array($environment))
                {
                    $this->settings = $environment;
                }
                $request = $this;
                $this->onMatched(function () use ($request)
                {
                    if (is_array($request->settings) && ! empty($request->settings))
                    {
                        EnvironmentProvider::instance()->add($request->settings);
                    }
                }, 'setEnvironment');
            }
        }
        return $this;
    }

    /* Rendered Actions */
    /**
     * Output Static View to file
     * 
     */
    public function output(string $path = '', string $file = '')
    {
        if ($this->validChain())
        {
            $path = ((empty($trimPath = trim($path, '/'))) ? '': $trimPath. '/');
            $this->onRendered(function ($self) use ($path, $file)
            {
                if (is_null($self->view)) return;
                $newFile = QueryHelper::insertCodes($self->params, $file);
                $file = (empty($newFile) ? $file : $newFile);
                if (empty($file))
                {
                    $file = 'index.html';
                    $path = $path. ((empty($uri = trim($self->uri, '/'))) ? '': $uri. '/');
                }
                $path = config('PATHS.ROOT'). $path;
                if (! is_dir($path. $file))
                {
                    if (! file_exists($path) && ! mkdir($path, 0755, true)) return;
                    file_put_contents($path. $file, $self->view->getContent());
                }
            });
        }
        return $this;
    }
    /**
     * Output Static View to file
     * 
     * Re-output view to file at refresh rate
     * or loaded file as cache
     */
    public function staticView(int $refreshRate = null, string $refreshType = 'minutes', string $path = 'public', string $file = '')
    {
        if ($this->validChain())
        {
            $path = ((empty($trimPath = trim($path, '/'))) ? '': $trimPath. '/');
            $this->onMatched(function ($self) use ($path, $file)
            {
                if (is_null($self->view)) return;
                if (empty($file))
                {
                    $file = 'index.html';
                    $path = $path. ((empty($uri = trim($self->uri, '/'))) ? '': $uri. '/');
                }
                $dir = config('PATHS.ROOT'). $path;
                if (($refreshRate ?? 0) > 0 && is_file($dir . $file))
                {
                    $refreshAt = TimeHelper::create(filemtime($dir. $file))->add($refreshRate, $refreshType);
                    if (TimeHelper::create()->smallerThan($refreshAt))
                    {
                        $self->cache($dir. $file);
                        return;
                    }
                }
                $self->output($path, $file);
            });
        }
        return $this;
    }
}