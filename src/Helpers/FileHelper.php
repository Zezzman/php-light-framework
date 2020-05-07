<?php
namespace System\Helpers;

use System\Helpers\QueryHelper;
use System\Helpers\ArrayHelper;
use Exception;
/**
 * 
 */
final class FileHelper
{
    /**
     * Loads content of file and scans for codes
     */
    public static function loadFile(string $path, array $codes = null, array $defaults = [], bool $list = false, int $listLength = 0, bool $allowEmpty = false)
    {
        $path = self::secureRequiredPath($path);
        if (file_exists($path) && is_file($path)) {
            if (! is_null($codes)) {
                $content = file_get_contents($path);
                return QueryHelper::scanCodes($codes, $content, $defaults, $list, $listLength, $allowEmpty);
            } else {
                return file_get_contents($path);
            }
        } else {
            return false;
        }
    }
    /**
     * Encodes and Print Image into img tag
     */
    public static function printImage(string $path, string $type = 'image/png', string $description = '', string $style = '<img src="data:{type};{base},{data}" alt="{description}">')
    {
        $path = self::securePath($path);
        if (file_exists($path) && is_file($path)) {
            $file = [
                'path' => $path,
                'description' => DataCleanerHelper::cleanValue($description),
                'data' => base64_encode(file_get_contents($path)),
                'type' => $type,
                'base' => 'base64',
            ];
            return QueryHelper::scanCodes($file, $style);
        } else {
            return '';
        }
    }
    /**
     * Read File of type
     */
    public static function readFile(string $path, string $type, bool $setHeader = true)
    {
        $path = self::securePath($path);
        if (file_exists($path) && is_file($path)) {
            ob_clean();
            if ($setHeader) {
                header('Content-Type: ' . $type);
            }
            readfile($path);
            exit();
        } else {
            return false;
        }
    }
    /**
     * Links tags
     */
    public static function loadLinks(array $links)
    {
        $html = '';
        if (! empty($links)) {
            $style = '<link {href} {type} {rel} {media}>';

            foreach ($links as $link) {
                if (is_array($link)) {
                    // Required fields
                    $link['href'] = $link['href'] ?? '';
                    $link['type'] = $link['type'] ?? '';
                    $link['rel'] = $link['rel'] ?? '';
                    $link['media'] = $link['media'] ?? '';
                    // Format fields
                    if ($link['href'] !== '') {
                        $link['href'] = 'href="' . $link['href'] . '"';
                    }
                    if ($link['type'] !== '') {
                        $link['type'] = 'type="' . $link['type'] . '"';
                    }
                    if ($link['rel'] !== '') {
                        $link['rel'] = 'rel="' . $link['rel'] . '"';
                    }
                    if ($link['media'] !== '') {
                        $link['media'] = 'media="' . $link['media'] . '"';
                    }
                    // Create html
                    $html .= QueryHelper::scanCodes($link, $style);
                }
            }
        }
        return $html;
    }
    /**
     * Script tags
     */
    public static function loadScripts(array $scripts)
    {
        $html = '';
        if (! empty($scripts)) {
            $style = '<script {src} {async} {defer} {type} {charset}>{code}</script>';

            foreach ($scripts as $script) {
                if (is_string($script)) {
                    $script = ['src' => $script];
                }
                if (is_array($script)) {
                    // Required fields
                    $script['async'] = (in_array('async', $script)) ? 'async' : '';
                    $script['defer'] = (in_array('defer', $script)) ? 'defer' : '';
                    $script['charset'] = $script['charset'] ?? '';
                    $script['type'] = $script['type'] ?? '';
                    $script['code'] = $script['code'] ?? '';
                    $script['src'] = $script['src'] ?? '';
                    // Format fields
                    if ($script['src'] !== '') {
                        $script['src'] = 'src="' . $script['src'] . '"';
                    }
                    if ($script['type'] !== '') {
                        $script['type'] = 'type="' . $script['type'] . '"';
                    }
                    if ($script['charset'] !== '') {
                        $script['charset'] = 'charset="' . $script['charset'] . '"';
                    }
                    if (isset($script['var']) && ! empty($script['var'])) {
                        $script['code'] .= QueryHelper::scanCodes($script['var'], 'var {KEY} = {VALUE};', [], true) . "\n";
                    }
                    if (isset($script['path']) && ! empty($script['path'])) {
                        $file = self::loadFile($script['path']);
                        if (! empty($file)) {
                            $script['code'] .= $file;
                        }
                    }
                    // Create html
                    $html .= QueryHelper::scanCodes($script, $style);
                }
            }
        }
        return $html;
    }
    /**
     * Load secret php files
     * 
     * @param   array       $files              php files within a secret folder
     * @param   string      $key                key name within secret array
     * @param   mix         $default            default value when value is not found
     * 
     * @return  mix         return whole array or value of the provided key within secret file
     */
    public static function loadSecrets($files, string $key = null, $default = false)
    {
        $files = (array) $files;
        $secrets = [];
        foreach ($files as $file) {
            $path = self::securePath('secrets/' . $file . '.php');
            if ($path !== false)
            {
                $content = include($path);
                if (is_array($content))
                {
                    $secrets = ArrayHelper::mergeRecursively($secrets, $content);
                }
            }
        }
        if (is_string($key))
        {
            $constant = ArrayHelper::deepSearch($secrets, strtoupper($key), '.');
            if (is_null($constant)) {
                return $default;
            } else {
                return $constant;
            }
        }
        else
        {
            return $secrets;
        }
    }
    public static function securePath(string $name)
    {
        $name = DataCleanerHelper::cleanValue($name);

        $root = config('PATHS.ROOT');
        if (file_exists($path = ($root .  $name)))
        {
            return $path;
        }
        else if (($path = self::findResource($name)) !== false)
        {
            return $path;
        }
        return false;
    }
    public static function secureRequiredPath(string $name)
    {
        if (empty($path = self::securePath($name)))
        {
            throw new Exception('Missing file : ' . $name);
        }
        else
        {
            return $path;
        }
    }
    public static function findResource(string $name)
    {
        $root = requireConfig('PATHS.ROOT');
        $resources = (array) requireConfig('PATHS.RESOURCES');
        foreach ($resources as $resource)
        {
            if (file_exists($path = $root . $resource .  $name))
            {
                return $path;
            }
        }
        return false;
    }
}