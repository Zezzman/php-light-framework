<?php
namespace System\Helpers;

use Closure;

/**
 * Helper for cleaning data
 */
final class DataCleanerHelper
{
    /**
     * Clean string
     * 
     * @param	string	$data		unsafe string.
     * 
     * @return	string	clean string
     */
    public static function cleanValue($data)
    {
        $cleanData = '';
        if (is_string($data) || is_numeric($data)) {
            $cleanData = trim($data);
            $cleanData = trim($cleanData, '/');

            $cleanData = htmlspecialchars($cleanData);
            $cleanData = strip_tags($cleanData);
            if (get_magic_quotes_gpc()) {
                $cleanData = stripslashes($cleanData);
            }
        }
        return $cleanData;
    }
    /**
     * Clean array of strings
     * 
     * @param	array	$data		unsafe array of strings.
     * 
     * @return	string	clean array
     */
    public static function cleanArray(array $data)
    {
        return $data;
    }
    /**
     * Clean string
     * 
     * @param	string	$email		unsafe string.
     * 
     * @return	string	return clean string
     */
    public static function cleanEmail($email)
    {
        $tags = [
            'content-type',
            'bcc:',
            'to:',
            'cc:',
            'href',
            'src='
        ];
        $cleanData = '';
        if(!is_array($email) && !is_object($email)){
            $cleanData = trim($email);
            $cleanData = trim($cleanData, '/');
            
            $cleanData = str_replace($tags, '', $cleanData);
            $cleanData = htmlspecialchars($cleanData);
        }
        return $cleanData;
    }
    /**
     * Remove empty spaces
     * 
     * @param	string	$data		    string with spaces.
     * @param	string	$replacer		replacement for spaces.
     * 
     * @return	string	return clean string with replaced spaces
     */
    public static function cleanSpaces($data, $replacer = '%20')
    {
        $cleanData = '';
        if (is_string($data) || is_numeric($data)) {
            $cleanData = trim($data);
            $cleanData = preg_replace('/[ ]/', $replacer, $data);
        }
        return $cleanData;
    }
     /**
     * Map String with callback
     * 
     * start + from index
     * 
     * start - from last index
     * 
     * count + from start
     * 
     * count - count from last item
     * 
     * @param	string	        $data		                        string of data.
     * @param	string	        $separator		                    separator within data.
     * @param	Closure	        $callback($a, $b, $c, $d)           callback function.
     * @param	int	            $count                              number of times callback is called.
     * @param	int	            $start                              index to start at.
     * 
     * @return	string	callback result
     */
    public static function dataMap(string $data, string $separator = ' ', Closure $callback = null, int $count = 0, int $start = 0)
    {
        $result = '';
        if (! empty($data)) {
            $data = trim($data);
            $data = trim($data, $separator);
            $sections = explode($separator, $data);
            if (is_null($callback)) {
                $callback = function ($result, $item, $separator, $index) {
                    return $result . $item . $separator;
                };
            }
            $start = ($start < 0) ? count($sections) + $start : $start;
            $count = ($count < 0)? count($sections) - $start + $count : (($count > 0) ? $count : count($sections) - $start);

            for ($i = $start; $i < count($sections); $i++) {
                $count--;
                if ($count >= 0) {
                    $result = $callback($result, $sections[$i], $separator, $i);
                } else {
                    break;
                }
            }
        }
        return $result;
    }
}