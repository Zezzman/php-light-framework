<?php
namespace System\Factories;

use System\ViewData;
use System\Interfaces\IViewModel;
use System\Helpers\DataCleanerHelper;
use Exception;
/**
 * 
 */
class ViewFactory
{
    /**
     * 
     */
    public static function createView(string $name, IViewModel $model = null, array $bag = null)
    {
        return new ViewData($name, self::securePath('views/' . $name), $model, $bag);
    }
    public static function createResponse(string $name, IViewModel $model = null, array $bag = null)
    {
        return new ViewData($name, self::securePath('responses/' . $name), $model, $bag);
    }

    public static function securePath(string $name)
    {
        $name = DataCleanerHelper::cleanValue($name);
        
        $root = config('PATHS.ROOT');
        if (file_exists($path = ($root .  "{$name}.php")))
        {
            return $path;
        }
        elseif (file_exists($path = config('CLOSURES.RESOURCE')("{$name}.php")))
        {
            return $path;
        }
        
        throw new Exception('Missing file : ' . $name);
        exit();
    }
}