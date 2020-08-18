<?php
namespace System;

use System\Interfaces\IController;
use System\Interfaces\IRequest;
use System\Interfaces\IViewModel;
use System\Providers\SessionProvider;
use System\Helpers\HTTPHelper;
use System\Helpers\FileHelper;
use System\Helpers\DataCleanerHelper;
use System\Exceptions\RespondException;
use System\Models\Requests\HttpRequestModel;
use System\ViewModels\ViewModel;
use System\ViewModels\ExceptionViewModel;
use System\View;
use Exception;
/**
 * Base class for web controller classes
 * 
 * Controller class is used to inherit the base controller features.
 * 
 * @author  Francois Le Roux <francoisleroux97@gmail.com>
 */
class Controller implements IController
{
    private $request = null;
    private $exception = null;
    protected $view = null;
    
    /**
     * Setup Controller request
     * 
     * @param   IRequest        $request    request sent from client for resource access
     */
    public function __construct(IRequest $request = null)
    {
        if (config('SETTINGS.NO_CACHE', false)) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
        }

        $this->request = $request;
    }
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
     * Create view
     * 
     * @param   string          $name       view name (file_name with extension omit)
     * @param   IViewModel      $model      model for view that holds information from controller to view
     * 
     * @return  View    return new created view
     */
    public function view(string $name = '', IViewModel $model = null, array $bag = [], string $layout = null)
    {
        if (! empty($name)) {
            try {
                if (is_null($this->view)) {
                    $this->view = View::create($this, $name, 'view', $model, $bag, $layout ?? config('LAYOUT.DEFAULT'));
                } else {
                    $this->view->appendView($name, 'view', $model, $bag);
                }
            } catch (RespondException $e) {
                $this->error($e->respondCode(), $e);
            } catch (PDOException $e) {
                $this->error(503, $e);
            } catch (Exception $e) {
                $this->error(500, $e);
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
            $this->getRequest()->triggerProcessed();
            if (($content = $this->view->render()) !== false)
            {
                echo $content;
                $request = $this->getRequest();
                $request->view = $this->view;
                $request->triggerRendered();
                return true;
            }
        }
        return false;
    }
    /**
     * Create Error View
     * 
     * @param   int             $code           header response code
     * @param   Exception       $exception      exceptions caught from try and catch
     */
    public function error(int $code, Exception $exception = null)
    {
        self::respond($code, '', $this->request, $exception);
    }
    /**
     * Redirect view
     * 
     * @param   string          $uri        view to redirect to
     */
    public function redirect(string $uri, int $responseCode = null)
    {
        // redirect to new $uri || create view here
        HTTPHelper::redirect($uri, null, $responseCode);
    }

    /**
     * 
     */
    public static function respond(int $code, string $message = null, IRequest $request = null, Exception $exception = null)
    {
        $responses = \Launcher::Responses();

        if (! is_null($request)) {
            $redirect = $request->redirect;
            if (! is_null($redirect)) {
                SessionProvider::set('refererURI', $request->uri);
                $responseCode = http_response_code();
                SessionProvider::set('refererCode', $responseCode);
                HTTPHelper::redirect($redirect, $request->params, ($responseCode !== 200 ? $responseCode : null));
            }
        }
        if (isset($responses[$code])) {
            $response = $responses[$code];
            http_response_code($code);

            $respond = new static();
            $respond->request = $request;
            $respond->exception = $exception;
            
            $viewModel = new ExceptionViewModel();
            $viewModel->responseTitle = $response;
            $viewModel->responseCode = $code;
            $viewModel->exception = $exception;
            if (! empty($message)) {
                $viewModel->feedback(DataCleanerHelper::cleanValue($message));
            }

            if (FileHelper::findResource("responses/{$code}.php") !== false)
            {
                $file = $code;
            }
            else
            {
                $file = 'index';
            }
            try {
                $view = View::create($respond, $file, 'response', $viewModel, [], 'fill-screen');
                if (! is_null($view)
                && ($content = $view->render()) !== false)
                {
                    echo $content;
                }
                else
                {
                    if (config('SETTINGS.DEBUG', false))
                    {
                        echo "({$code}) : ". $exception->getMessage();
                    }
                    else
                    {
                        echo 'Something went wrong';
                    }
                }
            } catch (Exception $e) {
                if (config('SETTINGS.DEBUG', false))
                {
                    echo "({$code}) : ". $e->getMessage();
                }
                else
                {
                    echo 'Something went wrong';
                }
                exit();
            }
        }
        exit();
    }
}