<?php
namespace System\Helpers;

/**
 * Helper for CURL commands
 */
final class CURLHelper
{
    public static function curl(string $url, array $options = [])
    {
        $defaults = array(
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_TIMEOUT => 1,
            // CURLOPT_POSTFIELDS => http_build_query($post)
        );
        $options = $options + $defaults;
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        if( ! $result = curl_exec($curl))
        {
            trigger_error(curl_error($curl));
        }
        curl_close($curl);
        return (array) json_decode($result, true);
    }
}