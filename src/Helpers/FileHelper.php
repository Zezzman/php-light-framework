<?php
namespace System\Helpers;

use System\Helpers\QueryHelper;
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
        if (file_exists($path) && is_file($path)) {
            $file = [
                'path' => $path,
                'description' => DataCleanerHelper::cleanValue($description),
                'data' => base64_encode(file_get_contents($path)),
                'type' => $type,
                'base' => 'base64',
            ];
            return QueryHelper::insertCodes($file, $style);
        } else {
            return '';
        }
    }
    /**
     * Read File of type
     */
    public static function readFile(string $path, string $type, bool $setHeader = true)
    {
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
                        $file = FileHelper::loadFile($script['path']);
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
     * Store to File
     */
    public static function storeFile()
    {

    }
}