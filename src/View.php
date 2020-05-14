<?php
namespace System;

use System\ViewData;
use System\Interfaces\IController;
use System\Interfaces\IViewModel;
use System\Factories\ViewFactory;
use System\Helpers\HTTPHelper;
use System\Helpers\FileHelper;
use System\Helpers\QueryHelper;
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
    private $prepend = '';
    private $content = '';
    private $append = '';

    private $cacheCards = [];

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
    public static function create(IController $controller, string $name, string $type, IViewModel $model = null, array $bag = [], bool $includeLayout = false)
    {
        $view = new self($controller);
        $viewData = ViewFactory::createView($name, $type, $model, $bag);
        if ($viewData->valid()) {
            $view->currentView = $viewData;
            $view->viewData[$name] = $viewData;
            if ($includeLayout && config('LAYOUT.DEFAULT', false) !== false) {
                $view->layout(config('LAYOUT.DEFAULT'));
            }
            return $view;
        }
        return null;
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
    public function bag($changes = null, bool $save = true)
    {
        $bag = [];
        if (! is_null($changes)
        && ! empty($changes))
        {
            $changes = (array) $changes;
            if (! is_null($this->currentView))
            {
                if (! is_null($this->currentView->bag)) {
                    $bag = ArrayHelper::mergeRecursively($this->currentView->bag, $changes); // add additional items to view bag
                }
                else
                {
                    $bag = $changes;
                }
                if ($save == true)
                {
                    $this->currentView->bag = $bag;
                }
            }
            else
            {
                $bag = $changes;
            }
        }
        else
        {
            if (! is_null($this->currentView)
            && ! is_null($this->currentView->bag))
            {
                $bag = $this->currentView->bag;
            }
            else
            {
                $bag = [];
            }
        }
        
        return $bag;
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
     * Prepend Content Before Views and Layout
     */
    public function prepend(string $content)
    {
        $this->prepend .= $content;
    }
    /**
     * Append Content After Views and Layout
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
        $this->layout = $name;
    }
    private function header(string $name, array $bag = null)
    {
        if (($content = $this->loadFile('headers/' . $name  . '.php', $bag)) !== false)
        {
            echo $content;
        }
    }
    private function footer(string $name, array $bag = null)
    {
        if (($content = $this->loadFile('footers/' . $name  . '.php', $bag)) !== false)
        {
            echo $content;
        }
    }
    /**
     * 
     */
    private function section(string $name, array $bag = null)
    {
        if (($content = $this->loadFile('sections/' . $name  . '.php', $bag)) !== false)
        {
            echo $content;
        }
    }
    /**
     * 
     */
    private function loadFile(string $path, array $bag = null)
    {
        // file local pre-defined variables
        $controller = $this->controller ?? null;
        $viewData = $this->currentView ?? null;
        $model = $viewData->model ?? null;
        $bag = $this->bag($bag, false);
        
        $path = FileHelper::secureRequiredPath($path);
        if (! empty($path)) {
            if (file_exists($path)) {
                ob_start();
                if ((include($path)) !== false)
                {
                    $content = ob_get_clean();
                    return $content;
                }
                else
                {
                    return false;
                }
            }
        }
        return false;
    }
    /**
     * 
     */
    public function card(string $name, array $codes = null, array $defaults = [], bool $list = false, int $listLength = 0, bool $allowEmpty = false)
    {
        if (! isset($this->cacheCards[$name]))
        {
            $this->cacheCards[$name] = (string) FileHelper::loadFile('cards/' . $name);
        }
        echo QueryHelper::scanCodes($this->bag($codes, false), $this->cacheCards[$name], $defaults, $list, $listLength, $allowEmpty);
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
        // render views
        foreach ($this->viewData as $view) {
            $this->currentView = $view;
            $this->bag(config('APP', null));
            if (($viewContent = $this->loadFile($view->path)) !== false) {
                $body .= $view->prepend . $viewContent . $view->append;
                $hasView = true;
            }
        }
        // buffer layout
        if (empty($this->layout) || ! $hasView) {
            $this->hasRendered = true;
            // render view
            $this->content = $this->prepend . $body . $this->append;
        } else {
            // include body within layout
            $this->content = $body;
            $this->bag(['scripts' => [ ($this->layout) => ['path' => '../public/assets/javascript/' . $this->layout . '.js']]]);
            if (($layout = $this->loadFile('layouts/' . $this->layout . '.php', [
                'style' => $style = FileHelper::loadFile('../public/assets/css/' . $this->layout . '.css')
            ])) !== false)
            {
                $this->hasRendered = true;
                // render view
                $this->content = $this->prepend . $layout . $this->append;
            }
            else
            {
                throw new Exception('Loaded Empty Layout');
            }
        }
        $this->cacheCards = [];
        return $this->content;
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