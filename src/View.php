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
    public static function create(IController $controller, string $name, string $type, IViewModel $model = null, array $bag = [], string $layout = null)
    {
        $view = new self($controller);
        $viewData = ViewFactory::createView($name, $type, $model, $bag);
        if ($viewData->valid()) {
            $view->currentView = $viewData;
            $view->viewData[$name] = $viewData;
            if (! is_null($layout)) {
                $view->layout($layout);
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
     * Get Bag Contents
     */
    public function bag(array $changes = null)
    {
        if (! is_null($this->currentView) && ! is_null($this->currentView->bag))
        {
            if (empty($changes ?? []))
            {
                return $this->currentView->bag;
            }
            else
            {
                return ArrayHelper::mergeRecursively($this->currentView->bag, $changes); // add additional items to view bag
            }
        }
        return $changes;
    }
    /**
     * Get Bag Contents
     */
    public function findInBag(string $key)
    {
        $bag = $this->bag();
        $result = ArrayHelper::deepSearch($bag, strtoupper($key), '.');
        return $result;
    }
    /**
     * Add Items to Bag
     */
    public function addToBag(array $changes)
    {
        if (empty($changes) || is_null($this->currentView)) return;

        $bag = [];
        if (! is_null($this->currentView->bag)) {
            $bag = ArrayHelper::mergeRecursively($this->currentView->bag, $changes); // add additional items to view bag
        }
        $this->currentView->bag = $bag;
        return $bag;
    }
    /**
     * View Content
     */
    public function getContent()
    {
        return $this->content;
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
    public function prepend(string $content, string $view = null)
    {
        if (is_null($view))
        {
            $this->prepend .= $content;
        }
        else
        {
            $this->viewData($view)->prepend($content);
        }
    }
    /**
     * Append Content After Views and Layout
     */
    public function append(string $content, string $view = null)
    {
        if (is_null($view))
        {
            $this->append .= $content;
        }
        else
        {
            $this->viewData($view)->append($content);
        }
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
            return $content;
        }
        return '';
    }
    private function footer(string $name, array $bag = null)
    {
        if (($content = $this->loadFile('footers/' . $name  . '.php', $bag)) !== false)
        {
            return $content;
        }
        return '';
    }
    /**
     * 
     */
    public function section(string $name, array $bag = null)
    {
        if (($content = $this->loadFile('sections/' . $name  . '.php', $bag)) !== false)
        {
            return $content;
        }
        return '';
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
        $bag = $this->bag($bag);
        
        $path = FileHelper::secureRequiredPath($path);
        if (! empty($path)) {
            if (file_exists($path)) {
                try {
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
                } catch (Exception $e) {
                    ob_end_clean();
                    throw $e;
                }
            }
        }
        return false;
    }
    /**
     * 
     */
    public function card(string $name, array $codes = null, array $defaults = [], bool $list = false, int $listLength = 0, bool $allowEmpty = true)
    {
        if (! isset($this->cacheCards[$name]))
        {
            $this->cacheCards[$name] = (string) FileHelper::loadFile('cards/' . $name);
        }
        echo QueryHelper::deepScanCodes($this->bag($codes, false), $this->cacheCards[$name], $defaults, $list, $listLength, $allowEmpty);
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
        $this->addToBag(config('APP', []));
        foreach ($this->viewData as $view) {
            $this->currentView = $view;
            if (($viewContent = $this->loadFile($view->path)) !== false) {
                $body .= $view->prepend . $viewContent . $view->append;
                $hasView = true;
                if (config('SETTINGS.LOG_STRUCTURE', false)) echo "Loaded View: $view->path<br>\n";
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
            $minify = config('SETTINGS.MIN_SCRIPTS', true);
            if (($layout = $this->loadFile('layouts/' . $this->layout . '.php', [
                'layout' => ('../public/assets/css/' . $this->layout . (($minify) ? '.min.css' : '.css'))
            ])) !== false)
            {
                if (config('SETTINGS.LOG_STRUCTURE', false)) echo "Loaded Layout: layouts/$this->layout.php<br>\n";

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
        if (! config('SETTINGS.LOG_STRUCTURE', false)) ob_end_clean();
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