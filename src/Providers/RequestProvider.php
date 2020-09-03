<?php
namespace System\Providers;

use System\Factories\RequestFactory;
use System\Providers\SessionProvider;
use System\Providers\AuthProvider;
use System\Repositories\UserAuthRepository;
use System\Interfaces\IRequest;
use Exception;
/**
 * Manage client request
 * 
 * Create/setup client request to from url
 * and manage request state.
 */
final class RequestProvider
{
    private $requestType = null;
    private $request = null;
    private $matchedRequest = null;
    private $designatedRequests = [];
    private $designatedRoutes = [];

    public function __construct(string $requestType, string $routeFile, string $uri)
    {
        $this->requestType = $requestType;
        $request = RequestFactory::simpleRequest($requestType, $uri, getenv('REQUEST_METHOD'), config('CLIENT_TYPE'));
        $this->request = $request;
        if (! empty($request))
        {
            // load routes file
            if (file_exists($routeFile)) {
                $result = require($routeFile);
                if ($result instanceof IRequest)
                {
                    // if result is a returned request, overwrite other request;
                    $this->matchedRequest = $result;
                }
            } else {
                if (config('SETTINGS.DEBUG', false))
                {
                    throw new Exception('Route File Not Found: '. $routeFile);
                }
                else
                {
                    exit('Something went wrong');
                }
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

        if (is_null($this->matchedRequest))
        {
            foreach ($this->designatedRequests as $key => $request)
            {
                $request->triggerMatching();
                if ($this->request->requestMethod($request->method))
                {
                    if (! $selectedRequest->valid() || $request->size > $selectedRequest->size)
                    {
                        $request->uri = $this->request->uri;
                        $request->triggerMatched();
                        $selectedRequest = $request;
                    }
                }
            }
            $this->matchedRequest = $selectedRequest;
        }

        return $this->matchedRequest;
    }
    /**
     * 
     */
    private function createRequest(string $method, string $requestString, string $actionString = '')
    {
        $currentRequest = RequestFactory::httpRequest($requestString, $actionString, $method, config('CLIENT_TYPE'), $this->request, $this->designatedRoutes);
        if (is_null($currentRequest) )
        {
            $currentRequest = RequestFactory::emptyHttpRequest();
        }
        if (config('PERMISSIONS.ALLOW_GUESTS') === false) {
            if ($this->isGuest())
            {
                if ($currentRequest->settings('AUTH.GUEST.VISIBLE_RESTRICTIONS', true) === true)
                {
                    $currentRequest->respond(403, 'Request only allowed as guest');
                }
                else
                {
                    $currentRequest->respond(404);
                }
            }
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
    /**
     * Only Allow Authorized Users
     */
    public function isAuth(int $access = -1)
    {
        return AuthProvider::isAuthorized($access);
    }
    /**
     * Compare Login Token to Database Token
     */
    public function authCheck()
    {
        SessionProvider::startSession();
        if (empty($username = SessionProvider::get('username'))) return false;
        if (empty($session_token = SessionProvider::get('session_token'))) return false;

        $repo = new UserAuthRepository();
        $userData = $repo->getUserAuthWithUsername($username);
        if (! $userData || $userData['session_token'] !== $token)
        {
            SessionProvider::destroySession();
            return false;
        }
        return true;
    }
    /**
     * Only Allow Guests
     */
    public function isGuest()
    {
        return ! AuthProvider::isAuthorized();
    }
    /**
     * Match regex to request
     */
    public function match($needle)
    {
        return $this->request->match($needle);
    }
    /**
     * Match regex to request and replace
     */
    public function replace($needle, $replace)
    {
        if (empty($uri = $this->request->replace($needle, $replace))) return false;

        $this->newClientRequest($uri);
        return true;
    }
    /**
     * Reconstruct client request
     */
    public function newClientRequest(string $uri)
    {
        $request = RequestFactory::simpleRequest($this->requestType, $uri, $this->request->method, $this->request->type);
        if (empty($request)) return;
        $this->request = $request;
    }
    /**
     * Create Response
     */
    public function respond(int $code, string $message = null)
    {
        return $this->createRequest(getenv('REQUEST_METHOD'), '', '')->respond($code, $message);
    }
}