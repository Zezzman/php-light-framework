<?php
namespace System;

use System\Interfaces\IRequest;
use System\Interfaces\IController;
use System\Models\HttpRequestModel;
use System\Helpers\DataCleanerHelper;
use System\Helpers\HTTPHelper;
use Exception;
/**
 * Base class for api controller classes
 * 
 * Api class is used to inherit the base api controller features.
 * 
 * @author  Francois Le Roux <francoisleroux97@gmail.com>
 */
abstract class APIController implements IController
{
    protected $request = null;
    protected $exception = null;
    protected $inputs = null;
    protected $view = null;
    protected $body = null;

    protected function Options() {}
        
    public function getRequest()
    {
        return $this->request ?? HttpRequestModel::empty();
    }
    public function isMethod(string $method)
    {
        if (! is_null($this->request)) {
            return ($this->request->method === $method);
        }
        return false;
    }
    /**
     * Setup Api request and input
     * 
     * Store request and input from php://input into their respected variables
     * 
     * @param   IRequest    $request        request sent from client for resource access
     */
    public function __construct(IRequest $request = null)
    {
        $this->request = $request;
        $inputs = [];
        parse_str(file_get_contents('php://input'), $inputs);
        $this->inputs = $inputs;
        if ($this->request->method === 'OPTIONS') {
            $this->Options();
            self::respond(204);
            exit();
        }
    }
    /**
     * Create view
     * 
     * @param   string          $name       view name (file_name with extension omit)
     * @param   IViewModel      $model      model for view that holds information from controller to view
     * 
     * @return  View    return new created view
     */
    public function view(string $name = '', IViewModel $model = null, array $bag = [])
    {
        if (! empty($name)) {
            try {
                if (is_null($this->view)) {
                    $this->view = View::create($this, $name, 'view', $model, $bag, false);
                } else {
                    $this->view->appendView($name, $model, $bag);
                }
            } catch (RespondException $e) {
                self::respond($e->respondCode(), "", null, $e);
            } catch (PDOException $e) {
                self::respond(503, "", null, $e);
            } catch (Exception $e) {
                self::respond(500, "", null, $e);
            }
        }
        return $this->view;
    }
    /**
     * Render Controller Views
     */
    public function render()
    {
        if ($this->view instanceof \System\View)
        {
            if (($content = $this->view->render()) !== false)
            {
                $this->body = $content;
                self::respond(200, '', $this->request, null, ['html' => $content]);
                return true;
            }
        }
        return false;
    }
    /**
     * Get Output Content
     */
    public function getContent()
    {
        return $this->body;
    }
    /**
     * 
     */
    public static function respond(int $code, $message = null, IRequest $request = null, Exception $exception = null, $body = [])
    {
        $responses = \Launcher::Responses();

        if (! is_null($request)) {
            $redirect = $request->redirect;
            if (! is_null($redirect)) {
                $responseCode = http_response_code();
                HTTPHelper::redirect($redirect, $request->params, ($responseCode !== 200 ? $responseCode : null));
            }
        }
        if (isset($responses[$code])) {
            $response = $responses[$code];
            http_response_code($code);
            $body['response'] = $code;
            $body['type'] = $response;
            if (is_string($message)) {
                $body['message'] = DataCleanerHelper::cleanValue($message ?? '');
            } else {
                $message = DataCleanerHelper::cleanArray((array) $message);
                $body['message'] = $message;
            }
            if (! is_null($exception) && config('SETTINGS.DEBUG', false)) {
                $body['request'] = $request;
                $body['Exception'] = $exception;
                if ($body['message'] === '') {
                    $body['message'] = DataCleanerHelper::cleanValue($exception->getMessage());
                }
            }
            echo json_encode($body);
        }
        exit();
    }
}