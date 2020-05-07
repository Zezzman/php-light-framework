<?php
namespace System;

use System\ViewData;
use System\Interfaces\IController;
use System\Interfaces\IViewModel;
use System\Factories\ViewFactory;
use System\Helpers\HTTPHelper;
use System\Helpers\FileHelper;
use System\Helpers\ArrayHelper;
use Exception;
/**
 * View
 * 
 * Holds and manages the viewData, model and layout
 */
class View
{
    public $hasRendered = false;
    private $controller = null;
    private $viewData = [];
    private $layout = null;
    private $currentView = null;
    private $content = '';
    private $append = '';

    /**
     * 
     */
    private function __construct(IController $controller)
    {
        $this->controller = $controller;
    }
    /**
     * 
     */
    public static function create(IController $controller, string $name, string $type, IViewModel $model = null, array $bag = [])
    {
        $view = new self($controller);
        $viewData = ViewFactory::createView($name, $type, $model, $bag);
        if ($viewData->valid()) {
            $view->currentView = $viewData;
            $view->viewData[$name] = $viewData;
            if (config('LAYOUT.DEFAULT', false) !== false) {
                $view->layout(config('LAYOUT.DEFAULT'));
            }
        }
        if (! is_null($view->currentView) && $view->currentView->valid()) {
            return $view;
        } else {
            return null;
        }
    }
    /**
     * 
     */
    public function viewData(string $name)
    {
        return $this->viewData[$name] ?? null;
    }
    /**
     * 
     */
    public function appendView(string $name, string $type, IViewModel $model = null, array $bag = [])
    {
        if (! isset($this->viewData[$name])) {
            $viewData = ViewFactory::createView($name, $type, $model, $bag);
            if ($viewData->valid()) {
                $this->currentView = $viewData;
                $this->viewData[$name] = $viewData;
                return true;
            }
        }
        return false;
    }
    /**
     * 
     */
    public function append(string $content)
    {
        $this->append .= $content;
    }
    /**
     * 
     */
    public function layout(string $name = null)
    {
        $this->layout = 'layouts/' . $name  . '.php';
    }
    private function header(string $name, array $bag = null)
    {
        return $this->loadFile('headers/' . $name  . '.php', $bag);
    }
    private function footer(string $name, array $bag = null)
    {
        return $this->loadFile('footers/' . $name  . '.php', $bag);
    }
    /**
     * 
     */
    private function section(string $name, array $bag = null)
    {
        return $this->loadFile('sections/' . $name  . '.php', $bag);
    }
    /**
     * 
     */
    private function loadFile(string $path, array $bag = null)
    {
        // file local pre-defined variables
        $controller = $this->controller ?? null;
        $layout = $this->layout ?? null;
        $viewData = $this->currentView ?? null;
        $model = $viewData->model ?? null;
        if (is_null($bag)) {
            if (! is_null($viewData)
            && ! is_null($viewData->bag)) {
                $bag = $this->currentView->bag; // view bag
            } else {
                $bag = [];
            }
        }
        else
        {
            if (! is_null($viewData)
            && ! is_null($viewData->bag)) {
                $bag = ArrayHelper::mergeRecursively($this->currentView->bag, $bag); // add additional items to view bag
            }
        }

        $path = FileHelper::secureRequiredPath($path);
        if (! empty($path)) {
            if (file_exists($path)) {
                return include($path);
            }
        }
        return false;
    }
    /**
     * 
     */
    public function card(string $name, array $codes = null, array $defaults = [], bool $list = false, int $listLength = 0, bool $allowEmpty = false)
    {
        echo FileHelper::loadFile('cards/' . $name, $codes, $defaults, $list, $listLength, $allowEmpty);
    }
    /**
     * 
     */
    public function hasRendered()
    {
        return $this->hasRendered;
    }
    /**
     * 
     */
    public function render()
    {
        if (! is_array($this->viewData)
        || empty($this->viewData)
        || $this->hasRendered == true) {
            return false;
        }

        $hasView = false;
        $body = '';
        // buffer view
        ob_start();
        foreach ($this->viewData as $view) {
            if ($this->loadFile($view->path)) {
                $body .= ob_get_clean();
                $hasView = true;
            } else {
                ob_clean();
            }
        }
        // buffer layout
        if (is_null($this->layout) || ! $hasView) {
            $this->hasRendered = true;
            // render view
            echo $body . $this->append;
            ob_flush();
        } else {
            // include body within layout
            $this->content = $body;
            $layout = $this->loadFile($this->layout ?? '');
            $content = ob_get_clean();
            if ($layout) {
                $this->hasRendered = true;
                // render view
                echo $content . $this->append;
            } else {
                throw new Exception('Loaded Empty Layout');
            }
        }
    }
    /**
     * 
     */
    public function link(string $uri = null, array $params = null)
    {
        if (is_null($uri) && ! is_null($this->controller->getRequest())) {
            return config('LINKS.PUBLIC') . $this->controller->getRequest()->uri;
        } else {
            return HTTPHelper::link($uri, $params);
        }
    }
}