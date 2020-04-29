<?php
namespace System\Providers\Requests;

use System\Factories\RequestFactory;
/**
 * Manage client request
 * 
 * Create/setup client request to from url
 * and manage request state.
 */
final class CLIRequestProvider
{
    private $request = null;
    private $currentRequests = null;
    private $designatedRequests = [];

    public function __construct($commands)
    {
        $this->request = RequestFactory::simpleCLIRequest($commands);
    }
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * 
     */
    public function createRequest(array $commands, array $params, array $flags, string $actionString)
    {
        $this->currentRequests = RequestFactory::cliRequest($commands, $params, $flags, $actionString);
    }
    public function command(string $command, array $params, string $actionString){
        $command = trim($command, '-');
        // $inputs = [];
        // foreach ($params as $param) {
        //     $inputs[$param] = null;
        // }
        $this->createRequest([$command], $params, [], $actionString);
        if (! is_null($this->currentRequests)) {
            $this->designatedRequests[] = $this->currentRequests;
        }
    }
    public function default(string $actionString){
        $this->createRequest([], [], [], $actionString);
        if (! is_null($this->currentRequests)) {
            $this->designatedRequests[] = $this->currentRequests;
        }
    }
    public function matchRequests()
    {
        $selectedRequest = RequestFactory::emptyCLIRequest();

        foreach ($this->designatedRequests as $request) {
            if ($this->request->matchRequest($request)) {
                if (! $selectedRequest->valid()) {
                    $selectedRequest = $request;
                    break;
                }
            }
        }
        
        $this->request = $selectedRequest;
        return $this->request;
    }
}