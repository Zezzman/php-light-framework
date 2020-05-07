<?php
namespace System\Factories;

use System\ViewData;
use System\Interfaces\IViewModel;
use System\Helpers\FileHelper;
use Exception;
/**
 * 
 */
class ViewFactory
{
    /**
     * 
     */
    public static function createView(string $name, string $type, IViewModel $model = null, array $bag = null)
    {
        if ($type == 'view')
        {
            return new ViewData($name, 'views/' . $name . '.php', $model, $bag);
        }
        else if ($type == 'response')
        {
            return new ViewData($name, 'responses/' . $name . '.php', $model, $bag);
        }
        else
        {
            throw new Exception('Invalid View Type:' . $type);
        }
    }
}