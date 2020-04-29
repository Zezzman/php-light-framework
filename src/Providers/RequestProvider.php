<?php
namespace System\Providers;

use System\Factories\RequestFactory;
use Exception;
/**
 * Manage client request
 * 
 * Create/setup client request to from url
 * and manage request state.
 */
final class RequestProvider
{
    private $request = null;
    private $designatedRequests = [];

    public function __construct(string $requestType, string $routeFile, string $uri)
    {
        $request = RequestFactory::simpleRequest($requestType, $uri, getenv('REQUEST_METHOD'), config('CLIENT_TYPE'));
        $this->request = $request;
        if (! empty($request))
        {
            //load routes
            if (file_exists($routeFile)) {
                require($routeFile);
            } else {
                throw new Exception('Route File Not Found: '. $routeFile);
            }
        }
    }
    public function getRequest()
    {
        return $this->request;
    }
    public function matchRequests()
    {
        $selectedRequest = RequestFactory::emptyHttpRequest();

        foreach ($this->designatedRequests as $key => $request)
        {
            if ($this->request->matchRequest($request))
            {
                if ($this->request->requestMethod($request->method))
                {
                    if (! $selectedRequest->valid())
                    {
                        $request->isMatching();
                        $request->uri = $this->request->uri;
                        if ($request->valid()
                        && $request->isMatched())
                        {
                            $selectedRequest = $request;
                            break;
                        }
                        $selectedRequest = $request;
                    }
                }
            }
        }
        
        $this->request = $selectedRequest;
        return $this->request;
    }
    /**
     * 
     */
    private function createRequest(string $method, string $requestString, string $actionString = '')
    {
        $currentRequest = RequestFactory::httpRequest($requestString, $actionString, $method, config('CLIENT_TYPE'), $this->request->route);
        if (config('PERMISSIONS.ALLOW_GUESTS') === false) {
            $this->auth();
        }
        return $currentRequest;
    }
    /**
     * 
     */
    public function request(string $match, string $actionString = '')
    {
        $currentRequest = $this->createRequest(getenv('REQUEST_METHOD'), $match, $actionString);
        if (! is_null($currentRequest)) {
            $this->designatedRequests[] = $currentRequest;
        }
        return $currentRequest;
    }
    /**
     * 
     */
    public function get(string $match, string $actionString = '')
    {
        $currentRequest = $this->createRequest('GET', $match, $actionString);
        if (! is_null($currentRequest)) {
            $this->designatedRequests[] = $currentRequest;
        }
        return $currentRequest;
    }
    /**
     * 
     */
    public function post(string $match, string $actionString = '')
    {
        $currentRequest = $this->createRequest('POST', $match, $actionString);
        if (! is_null($currentRequest)) {
            $this->designatedRequests[] = $currentRequest;
        }
        return $currentRequest;
    }
}