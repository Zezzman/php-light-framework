<?php
namespace System\Helpers;

/**
 * 
 */
final class HTTPHelper
{
    /**
     * 
     */
    public static function isGet(string $param = null)
    {
        if (! is_array($_GET) || empty($_GET)) return false;
        if (is_null($param)) return true;

        return (! (empty($param) && ! is_numeric($param)) && isset($_GET[$param]));
    }
    /**
     * 
     */
    public static function isPost(string $param = null)
    {
        if (! is_array($_POST) || empty($_POST)) return false;
        if (is_null($param)) return true;

        return (! (empty($param) && ! is_numeric($param)) && isset($_POST[$param]));
    }
    /**
     * 
     */
    public static function isFile(string $param = null)
    {
        if (! is_array($_FILES) || empty($_FILES)) return false;
        if (is_null($param)) return true;

        return (! (empty($param) && ! is_numeric($param)) && isset($_FILES[$param]));
    }
    /**
     * 
     */
    public static function get(string $param = null)
    {
        $get = $_GET;
        if (is_null($param)) return $get;
        if (! self::isGet($param)) return null;
        
        return $get[$param];
    }
    /**
     * 
     */
    public static function post(string $param = null)
    {
        $post = $_POST;
        if (is_null($param)) return $post;
        if (! self::isPOST($param)) return null;

        return $post[$param];
    }
    /**
     * 
     */
    public static function file(string $param = null)
    {
        $file = $_FILES;
        if (is_null($param)) return $file;
        if (! self::isFile($param)) return null;

        return $file[$param];
    }
    /**
     * 
     */
    public static function URI()
    {
        $query = DataCleanerHelper::cleanValue($_SERVER['QUERY_STRING']);
        if (empty($query)) return '/';

        return trim($query, '/');
    }
    public static function link(string $uri, array $params = null)
    {
        // construct link relative to web root
        $url = config('LINKS.PUBLIC') . $uri;
        if (is_null($params)) return $url;

        $keys = array_keys($params);
        for ($i = 0; $i < count($params); $i++) {
            $key = $keys[$i];
            if (is_string($params[$key]) || is_numeric($params[$key])) {
                $value = str_replace(' ', '%20', $params[$key]);
            } else {
                $value = json_encode($params[$key]);
            }
            if ($i === 0) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            if ($value === '' || ! is_string($value)) {
                $url .= "$key";
            } else {
                $url .= "$key=$value";
            }
        }
        return $url;
    }
    /**
     * 
     */
    public static function redirect(string $uri, array $params = null, int $responseCode = null)
    {
        $url = self::link($uri, $params);
        if (empty($url)) return false;

        if (! is_null($responseCode) && $responseCode > 0) {
            header("Location: $url", true, $responseCode);
        } else {
            header("Location: $url");
        }
        exit();
    }
}