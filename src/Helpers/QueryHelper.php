<?php
namespace System\Helpers;

use System\Helpers\ArrayHelper;
use Closure;

/**
 * 
 */
final class QueryHelper
{
    public static function arrayToStatements(array $array, string $field, string $operator, string $separator)
    {
        $statement = '';
        $index = 0;

        foreach ($array as $key => $value) {
            $index++;
            $statement .= "$field $operator :$key $separator ";
        }
        $statement = trim($statement);
        $statement = trim($statement, $separator);
        $statement = trim($statement);

        return $statement;
    }
    public static function insertCodes($codes, string $style = "{message}\n", bool $list = false, int $listLength = 0, bool $allowEmpty = false)
    {
        $message = '';
        $codes = (array)$codes;
        if (is_array($codes)) {
            if (! $list) {
                $item = $style;
                foreach ($codes as $code => $content) {
                    if (is_string($content) || is_numeric($content)) {
                        $item = str_replace(('{' . $code . '}'), $content, $item);
                    } else {
                        $item = str_replace(('{' . $code . '}'), '', $item);
                    }
                }
                if ($allowEmpty || $item !== $style) {
                    $message = $item;
                }
            } else {
                $count = count($codes);
                $keys = array_keys($codes);
                for ($i = 0; $i < $count; $i++) {
                    if ($listLength == 0 || $listLength > 0 && $listLength > $i 
                    || $listLength < 0 && ($count + $listLength) == $i) {
                        $key = $keys[$i];
                        $commands = (is_object($codes[$key]))? (array) $codes[$key]: $codes[$key];
                        $item = $style;
                        if (is_array($commands)) {
                            $commands['KEY'] = $key;
                            foreach ($commands as $code => $content) {
                                if (is_string($content) || is_numeric($content)) {
                                    $item = str_replace(('{' . $code . '}'), $content, $item);
                                } else {
                                    $item = str_replace(('{' . $code . '}'), '', $item);
                                }
                            }
                        } else {
                            if (is_string($commands) || is_numeric($commands)) {
                                $item = str_replace(['{KEY}', '{VALUE}'], [$key, $commands], $item);
                            }
                        }
                    }
                    if ($allowEmpty || $item !== $style) {
                        $message .= $item;
                    }
                }
            }
        }
        return $message;
    }
    public static function scanCodes($codes, string $subject, array $defaults = [], bool $list = false, int $listLength = 0, bool $allowEmpty = false)
    {
        if (is_string($codes) || is_numeric($codes)) {
            $codes = ['content' => $codes];
        } else {
            $codes = (array)$codes;
        }
        
        $counter = 0;
        $pattern = "/({(.*?)})/";

        $callback = function ($match) use (&$codes, $defaults, &$counter) {
            $key = $match[2];
            $item = $codes[$key] ?? null;
            if (is_string($item) && ! empty($item)
            || is_numeric($item)) {
                $counter++;
                return $item;
            } else {
                $item = $defaults[$key] ?? null;
                if (is_string($item) && ! empty($item)
                || is_numeric($item)) {
                    return $item;
                }
            }
        };
        
        if ($list) {
            $message = self::mapCodesList($codes, $subject, $pattern, $callback, $listLength, $allowEmpty, $counter);
        } else {
            $counter = 0;
            $message = preg_replace_callback($pattern, $callback, $subject);
            if ($allowEmpty === true || $counter === 0) {
                $message = '';
            }
        }
        return $message;
    }
    public static function deepScanCodes($codes, string $subject, array $defaults = [], bool $list = false, int $listLength = 0, bool $allowEmpty = false)
    {
        if (is_string($codes) || is_numeric($codes)) {
            $codes = ['content' => $codes];
        } else {
            $codes = (array)$codes;
        }
        
        $counter = 0;
        $pattern = "/({(.*?)})/";

        $callback = function ($match) use (&$codes, $defaults, &$counter) {
            $key = $match[2];
            $item = ArrayHelper::deepSearch($codes, $key, '.');
            if (is_string($item) && ! empty($item)
            || is_numeric($item)) {
                $counter++;
                return $item;
            } else {
                $item = ArrayHelper::deepSearch($defaults, $key, '.');
                if (is_string($item) && ! empty($item)
                || is_numeric($item)) {
                    return $item;
                }
            }
        };
        
        if ($list) {
            $message = self::mapCodesList($codes, $subject, $pattern, $callback, $listLength, $allowEmpty, $counter);
        } else {
            $counter = 0;
            $message = preg_replace_callback($pattern, $callback, $subject);
            if ($allowEmpty === true || $counter === 0) {
                $message = '';
            }
        }
        return $message;
    }
    public static function mapCodesList(array &$codes, string $subject, string $pattern, Closure $callback, int $listLength = 0, bool $allowEmpty = false, int &$counter = 0)
    {
        $mapCodes = function () use ($pattern, $callback, $subject, $allowEmpty, &$counter) {
            $message = preg_replace_callback($pattern, $callback, $subject);
            if ($allowEmpty === true || $counter !== 0) {
                return $message;
            }
        };
        
        $message = '';
        $commands = $codes;
        $commandCount = count($commands);
        $keys = array_keys($commands);
        for ($i = 0; $i < $commandCount; $i++) {
            if ($listLength == 0 || $listLength > 0 && $listLength > $i 
            || $listLength < 0 && ($commandCount + $listLength) == $i) {
                $key = $keys[$i];
                $codes = (is_object($commands[$key]))? (array) $commands[$key]: $commands[$key];
                if (is_array($codes)) {
                    $codes['KEY'] = $key;
                    $counter = 0;
                    $message .= $mapCodes();
                } else {
                    if (is_string($codes) || is_numeric($codes)) {
                        $codes = [
                            'KEY' => $key,
                            'VALUE' => $codes
                        ];
                        $counter = 0;
                        $message .= $mapCodes();
                    }
                }
            }
        }
        return $message;
    }
}