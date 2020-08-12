<?php
use System\Router;
use System\Interfaces\IController;
use System\Controller;
use System\APIController;
use System\CLIController;
use System\Interfaces\IRequest;
use System\Providers\EnvironmentProvider;
use System\Exceptions\RespondException;
/**
 * Handles App
 * 
 * Initiates Application
 * and executes Controller
 * 
 * @author  Francois Le Roux <francoisleroux97@gmail.com>
 */
final class Launcher
{
    private static $instance = null;
    public $environment = null;
    public $router = null;

    /**
     * Private the constructor to stop instantiations
     */
    private function __construct(){}
    /**
     * Configure App pre-launch
     * 
     * @param   string      $applicationName        app name
     * 
     * @return  self     return self instance
     */
    public static function setup()
    {
        if (is_null(self::$instance)) {
            $instance = &self::$instance;
            $instance = new self();

            // Default debug output
            if (getenv('DEBUG') == true) {
                error_reporting(E_ALL);
                ini_set('display_errors', E_ALL);
            }

            // Load configurations
            $instance->environment = EnvironmentProvider::instance();
            $instance->environment->setup();

            // Create Router
            $instance->router = new Router();
            
            // Set debug output
            if (config('SETTINGS.DEBUG', false)) {
                error_reporting(E_ALL);
                ini_set('display_errors', E_ALL);
            }
            
            // Set timezone
            date_default_timezone_set(config('TIMEZONE', 'Australia/Brisbane'));
            
            // Set class Instance
            self::$instance = $instance;
        }

        return self::$instance;
    }
    /**
     * Instance of the App
     * 
     * @return  self     return self instance
     */
    public static function instance()
    {
        return self::$instance;
    }
    /**
     * Get Resource Request
     */
    public function getRequest()
    {
        if ($this->router->type() === 'webserver') {
            $this->getWebRequest();
        } elseif ($this->router->type() === 'api') {
            $this->getAPIRequest();
        } elseif ($this->router->type() === 'cli') {
            $this->getCLIRequest();
        }
    }
    public function getWebRequest()
    {
        $this->router->webRoutes();
    }
    public function getAPIRequest()
    {
        $this->router->apiRoutes();
    }
    public function getCLIRequest()
    {
        $this->router->cliRoutes();
    }
    /**
     * Run app with request provided to from client
     * to access resources
     * 
     * Process request into web or api controller and construct
     * controller and method requested from client request
     * 
     * @param   IRequest     $request           request sent from the client
     */
    public function run(IRequest $request = null)
    {
        $request = ($request ?? ($this->router->request()));
        $type = config('CLIENT_TYPE');
        if ($type === 'webserver') {
            /**
             * Handle request for Web Servers
             */
            if ($this->webController($request) === false) {
                // route to 404 error view
                Controller::respond(404, '', $request);
            }
            return true;
        } elseif ($type === 'api') {
            /**
             * Handle request for APIs
             */
            if ($this->apiController($request) === false) {
                // return 404 error response
                APIController::respond(404, '', $request);
            }
            return true;
        } elseif ($type === 'cli') {
            /**
             * Handle request for Commands
             */
            if ($this->cliController($request) === false) {
                // return 404 error response
                // CLIController::respond(404, "Invalid Request", $request);
            }
            return true;
        } elseif ($type === 'cronjob') {
            /**
             * Handle request for Cronjobs
             */
            return true;
        }
        http_response_code(404);
        exit();
    }
    /**
     * Call controller and method requested from client request
     * 
     * @param   IRequest     $request           request sent from the client
     * 
     * @return  bool     return true if controller and method is successfully called
     */
    private function webController(IRequest $request = null)
    {
        if (! is_null($request)) {
            $controller = $request->controller;
            $action = $request->action;
            $params = $request->params;
            $view = $request->view;

            if (! is_null($request->response)) {
                Controller::respond($request->response, $request->message, $request);
            }
            try {
                http_response_code(200);
                if (! is_null($controller) && ! is_null($action) && ! is_null($params)) {
                    $path = requireConfig('NAMESPACES.CONTROLLERS') . "{$controller}Controller";
                    $controller = $this->executeController($request, $path, $action, $params);
                } else if(! is_null($view)) {
                    $controller = $this->executeView($request, $view, $params);
                } else {
                    Controller::respond(404, '', $request);
                }
            } catch (RespondException $e) {
                Controller::respond($e->respondCode(), '', $request, $e);
            } catch (PDOException $e) {
                Controller::respond(503, '', $request, $e);
            } catch (Exception $e) {
                Controller::respond(500, '', $request, $e);
            }
            return true;
        }
        return false;
    }
    /**
     * Call controller and method requested from client request
     * 
     * @param   IRequest     $request           request sent from the client
     * 
     * @return  bool     return true if controller and method is successfully called
     */
    private function apiController(IRequest $request = null)
    {
        if (! is_null($request)) {
            $controller = $request->controller;
            $action = $request->action;
            $params = $request->params;

            if (! is_null($request->response)) {
                APIController::respond($request->response, $request->message, $request);
            }
            if (! is_null($controller) && ! is_null($action) && ! is_null($params)) {
                $path = config('NAMESPACES.API') . "{$controller}Controller";
                try {
                    http_response_code(200);
                    $controller = $this->executeController($request, $path, $action, $params);
                    if (is_null($controller->getContent())) {
                        APIController::respond(204, '', $request);
                    }
                } catch (RespondException $e) {
                    APIController::respond($e->respondCode(), '', $request, $e);
                } catch (PDOException $e) {
                    APIController::respond(503, '', $request, $e);
                } catch (Exception $e) {
                    APIController::respond(500, '', $request, $e);
                }
                return true;
            } else {
                APIController::respond(404, '', $request);
            }
        }
        return false;
    }
    /**
     * Call controller and method requested from client request
     * 
     * @param   IRequest     $request           request sent from the client
     * 
     * @return  bool     return true if controller and method is successfully called
     */
    private function cliController(IRequest $request = null)
    {
        if (! is_null($request)) {
            $controller = $request->controller;
            $action = $request->action;
            $params = $request->params;

            if (! is_null($request->response)) {
                CLIController::respond($request->response, $request->message, $request);
            }
            if (! is_null($controller) && ! is_null($action) && ! is_null($params)) {
                $path = config('NAMESPACES.CLI') . "{$controller}Controller";
                try {
                    $controller = $this->executeController($request, $path, $action, $params);
                } catch (RespondException $e) {
                    CLIController::respond($e->respondCode(), '', $request, $e);
                } catch (PDOException $e) {
                    CLIController::respond(503, '', $request, $e);
                } catch (Exception $e) {
                    CLIController::respond(500, '', $request, $e);
                }
                return true;
            } else {
                CLIController::respond(404, 'No commands executed', $request);
            }
        }
        return false;
    }
    /**
     * Call controller and method requested
     */
    private function executeController(IRequest $request,
        string $controllerPath, string $action, array $params)
    {
        if (class_exists($controllerPath, true)) {
            $controller = new $controllerPath($request);
            if ($controller instanceof IController) {
                if (method_exists($controller, $action)) {
                    $params = array_values($params);
                    if (is_array($params) && count($params) > 0) {
                        $controller->$action(...$params);
                    } else {
                        $controller->$action();
                    }
                    $controller->render();
                    return $controller;
                } else {
                    throw new Exception("Error finding Controller Method: $controllerPath::$action()");
                }
            } else {
                throw new Exception("Controller does not implement IController: $controllerPath");
            }
        } else {
            throw new Exception("Error finding Controller: $controllerPath");
        }
    }
    /**
     * Call controller and method requested
     */
    private function executeView(IRequest $request, $view, array $params)
    {
        $controller = new Controller($request);
        $controller->view($view, ($request->model ?? null), $params);
        $controller->render();
        return $controller;
    }
    public function isAuth()
    {
        return AuthProvider::isAuthorized();
    }
    public function Referer()
    {
        return SessionProvider::get('refererURI');
    }
    public function RefererCode()
    {
        return SessionProvider::get('refererCode');
    }
    public static function Responses()
    {
        return [
            200 => 'OK',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            415 => 'Invalid Media Type',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];
    }
}

/**
 * Get Configuration
 * 
 * @param   string      $constant           constant name within configuration settings
 * @param   mix         $default            default value returned when key does not exist
 * 
 * @return  mix     return value related to the $key or $default when value is not found
 */
function config(string $constant, $default = false)
{
    if (defined($constant))
    {
        return constant($constant) ?? $default;
    }
    else if (! is_null(Launcher::instance())) {
        return EnvironmentProvider::instance()->configurations($constant, $default);
    }
    throw new Exception('App Not Instantiated');
}
/**
 * Set Configuration
 * 
 * @param   string      $constant           constant name within configuration settings
 * @param   bool        $value              value of the constant that will be set
 * 
 * @return  bool     return true when value is set
 */
function setConfig(string $constant, $value, bool $append = false)
{
    if (! is_null(Launcher::instance())) {
        return EnvironmentProvider::instance()->set($constant, $value, $append);
    }
    throw new Exception('App Not Instantiated');
}
/**
 * Require Configuration
 * 
 * Throw Exception when value is null or empty string
 * 
 * @param   string      $constant           constant name within configuration settings
 * 
 * @return  mix     return value related to the $key or $default when value is not found
 */
function requireConfig(string $constant)
{
    $value = config($constant, null);
    if (is_null($value) || (is_string($value) && empty($value))) {
        throw new Exception('Empty Required Configuration: ' . $constant);
    }
    return $value;
}