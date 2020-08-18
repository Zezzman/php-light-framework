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
    public static function loadFile(string $path, array $codes = null, array $defaults = [], bool $list = false, int $listLength = 0, bool $allowEmpty = false, bool $required = true)
    {
        $path = ($required)? self::secureRequiredPath($path): self::securePath($path);
        if (! file_exists($path) || ! is_file($path)) return false;

        if (is_null($codes)) return file_get_contents($path);

        $content = file_get_contents($path);
        return QueryHelper::scanCodes($codes, $content, $defaults, $list, $listLength, $allowEmpty);
    }
    /**
     * Encodes and Print Image into img tag
     */
    public static function printImage(string $content, string $type = 'image/png', string $description = '', string $style = '<img src="data:{type};{base},{data}" alt="{description}">')
    {
        $data = base64_encode($content);

        $file = [
            'description' => DataCleanerHelper::cleanValue($description),
            'data' => $data,
            'type' => $type,
            'base' => 'base64',
        ];
        return QueryHelper::scanCodes($file, $style);
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
        if (empty($scripts)) return;
        
        $html = '';
        $style = '<script {src} {async} {defer} {type} {charset}>{code}</script>';
        $minify = config('SETTINGS.MIN_SCRIPTS', true);
        foreach ($scripts as $script) {
            if (is_string($script)) {
                $script = ['src' => $script];
            }
            if (is_array($script)) {
                // Declare variables
                $code = ((isset($script['var']) && ! empty($script['var'])) ?
                    QueryHelper::scanCodes($script['var'], 'var {KEY} = {VALUE};', [], true). "\n" : '');
                // Append File Content
                if (! empty($path = ($script['path'] ?? ''))
                && (self::securePath($path) !== false || self::securePath($path .= (($minify) ? '.min.js': '.js') !== false))
                && ($file = self::loadFile($path, null, [], false, 0, false, false)) !== false
                && ! empty($file))
                {
                    $code = $code. "\n". $file;
                }
                // Append Code
                $code .= ((! empty($script['code'] ?? '') && is_string($script['code'])) ? 
                    $script['code'] : '');

                if (! empty($script['src'] ?? '') && is_string($script['src']))
                {
                    $src = ((substr($script['src'], -3) == '.js') ?
                        $script['src'] : $script['src'] . (($minify) ? '.min.js': '.js'));
                }
                // Required fields
                $params = [
                    'async' => ((in_array('async', $script)) ? 'async' : ''),
                    'defer' => ((in_array('defer', $script)) ? 'defer' : ''),
                    'charset' => ((! empty(($script['charset'] ?? '')) && is_string($script['charset'])) ?
                        ('charset="' . $script['charset'] . '"') : ''),
                    'type' => ((! empty(($script['type'] ?? '')) && is_string($script['type'])) ?
                        ('type="' . $script['type'] . '"') : ''),
                    'code' => $code,
                    'src' => ((! empty($src)) ? ('src="' . $src . '"') : ''),
                ];
                // Create html
                $html .= QueryHelper::scanCodes($params, $style);
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

        if (($path = self::findResource($name)) !== false)
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
        if (empty($name)) return false;

        $root = requireConfig('PATHS.ROOT');
        if (file_exists($path = $root .  $name)) return $path;

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
    /**
     * Check if dir/file path is within resource directories
     * 
     * @param   string      $path               full_dir_path
     * 
     * @return  bool        true if dir/file path is within resources
     */
    public static function withinResources(string $path)
    {
        if (empty($path)) return false;

        $root = requireConfig('PATHS.ROOT');
        if (substr($path, 0, strlen($root)) === $root) return true;

        $resources = (array) requireConfig('PATHS.RESOURCES');
        foreach ($resources as $key => $resource)
        {
            $resourcePath = $root. $resource;
            if (substr($path, 0, strlen($resourcePath)) === $resourcePath) {
                return $key;
            }
        }
        return false;
    }
}