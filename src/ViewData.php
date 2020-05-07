<?php
namespace System;

use System\Interfaces\IView;
use System\Interfaces\IViewModel;
use System\Helpers\FileHelper;
/**
 * 
 */
class ViewData implements IView
{
    public $name = null;
    public $path = null;
    public $file = null;
    public $model = null;
    public $bag = [];

    /**
     * 
     */
    public function __construct(string $name, string $path, IViewModel $model = null, array $bag = [])
    {
        $this->name = $name;
        $this->path = $path;
        $this->file = FileHelper::secureRequiredPath($path);
        $this->model = $model;
        $this->bag = $bag;
    }
    /**
     * 
     */
    public function valid()
    {
        if (! is_null($this->name) && ! empty($this->file)) {
            return true;
        } else {
            return false;
        }
    }
}