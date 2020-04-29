<?php
namespace System\Providers;

use System\Helpers\HTTPHelper;
/**
 * Manage Client Session
 * 
 * Start/destroy client session
 * and manage tokens.
 */
final class SessionProvider
{
    private static $instance = null;
    private $token = null;

    private function __construct(){}
    
    public static function startSession(int $days = null)
    {
        if (! isset(self::$instance) || is_null(self::$instance)) {
            $instance = new self();

            if (! self::hasSession()) {
                $instance->sessionLife($days ?? 1);
                session_start();
            }

            self::$instance = $instance;
        }
    }
    public static function setupSession(bool $newToken = false)
    {
        if (self::hasSession()) {
            $instance = &self::$instance;
            if($newToken) {
                $instance->generateToken();
            } elseif (isset($_SESSION['CSRF_token'])) {
                $instance->token = $_SESSION['CSRF_token'];
            }  else {
                $instance->generateToken();
            }
        }
    }
    public static function closeSession()
    {
        if (isset(self::$instance)) {
            self::$instance = null;
        }
        if (self::hasSession()) {
            session_unset();
            // session_destroy();
            return true;
        }
        return false;
    }
    public static function destroySession()
    {
        if (isset(self::$instance)) {
            self::$instance = null;
        }
        if (! self::hasSession()) {
            session_start();
        }
        session_destroy();
    }
    public static function resetSession(int $days = null)
    {
        self::startSession();
        self::closeSession();
        self::startSession($days);
        session_regenerate_id();
        self::setupSession();
    }
    private function generateToken()
    {
        $this->token = $this->generateKey();
        $_SESSION['CSRF_token'] = $this->token;
    }
    private function generateKey(){
        return bin2hex(random_bytes(64));
    }
    private function sessionLife(int $days){
        if (! self::hasSession()) {
            $seconds = (60 * 60 * 24 * $days);
            ini_set('session.cookie_lifetime', $seconds);
            return true;
        }
        return false;
    }
    public static function token()
    {
        return self::$instance->token ?? null;
    }
    public static function hasSession()
    {
        return isset($_SESSION) && is_array($_SESSION);
    }
    public static function getSession()
    {
        self::startSession();
        return $_SESSION;
    }
    public static function get(string $key = null)
    {
        self::startSession();
        if (self::hasSession()
        && ! empty($_SESSION)) {
            if (! is_null($key)) {
                if (((is_numeric($key) && $key >= 0)
                || ! empty($key))
                && isset($_SESSION[$key])) {
                    return $_SESSION[$key];
                } else {
                    return null;
                }
            } else {
                return $_SESSION;
            }
        }
        return null;
    }
    public static function set(string $key, $value)
    {
        self::startSession();
        if (self::hasSession()) {
            $_SESSION[$key] = $value;
            return true;
        }
        return false;
    }
    public static function unset()
    {
        $keys = func_get_args();
        if (is_array($keys) && ! empty($keys)) {
            self::startSession();
            if (self::hasSession()) {
                foreach ($keys as $key) {
                    if (is_string($key) && ! empty($key)
                    && isset($_SESSION[$key])) {
                        unset($_SESSION[$key]);
                    }
                }
            }
        }
        
        
        return false;
    }
    public static function compareToken($token)
    {
        if (! is_null($token)
        && ! is_array($token)
        && ! is_object($token)) {
            if (! is_null(self::token())) {
                return hash_equals(self::token(), $token);
            }
        }
        return false;
    }
    public static function comparePost()
    {
        $token = HTTPHelper::post('csrf_token');
        if (! is_null($token)
        && ! is_array($token)
        && ! is_object($token)) {
            if (! is_null(self::token())) {
                return hash_equals(self::token(), $token);
            }
        }
        return false;
    }
}