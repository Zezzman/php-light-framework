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
    public static function setupSession(bool $newToken = false, string $keyword = '')
    {
        if (self::hasSession()) {
            $instance = &self::$instance;
            if (! $newToken && isset($_SESSION['CSRF_token'])) {
                $instance->token = $_SESSION['CSRF_token'];
            }  else {
                $instance->generateToken($keyword);
            }
        }
    }
    /**
     * Clear Session
     */
    public static function closeSession()
    {
        if (isset(self::$instance)) {
            self::$instance = null;
        }
        if (self::hasSession()) {
            session_unset();
            return true;
        }
        return false;
    }
    /**
     * Destroy Session
     */
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
    public static function resetSession(int $days = null, string $keyword = '')
    {
        // self::startSession();
        self::closeSession();
        self::startSession($days);
        session_regenerate_id();
        self::setupSession(false, $keyword);
    }
    private function generateToken(string $keyword = '')
    {
        if (empty($keyword))
        {
            $this->token = self::generateKey();
        }
        else
        {
            $this->token = self::generateKeyword($keyword);
        }
        $_SESSION['CSRF_token'] = $this->token;
    }
    public static function generateKey()
    {
        return bin2hex(random_bytes(64));
    }
    public static function generateKeyword(string $keyword = '')
    {
        $time = new \DateTime();
        $key = ($time->format("Y-m-d H-i-s")). (trim($keyword));
        return bin2hex($key);
    }
    private function sessionLife(int $days)
    {
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
    /**
     * 
     */
    public static function isGet(string $key = null)
    {
        self::startSession();
        if (! self::hasSession() || empty($_SESSION)) return false;
        if (is_null($key)) return true;

        return (! (empty($key) && ! is_numeric($key)) && isset($_SESSION[$key]));
    }
    public static function get(string $key = null)
    {
        if (is_null($key)) return $_SESSION;
        if (! self::isGet($key)) return null;
        
        return $_SESSION[$key];
    }
    public static function set(string $key, $value)
    {
        self::startSession();
        if (! self::hasSession()) return false;

        $_SESSION[$key] = $value;
        return true;
    }
    public static function unset()
    {
        $keys = func_get_args();
        if (! is_array($keys) || empty($keys)) return false;
        self::startSession();
        if (! self::hasSession()) return false;

        foreach ($keys as $key) {
            if (is_string($key) && ! empty($key)
            && isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
        }
        return true;
    }
    public static function compareToken($token)
    {
        if (empty($token) || is_array($token) || is_object($token)) return false;

        if (! is_null(self::token())) return hash_equals(self::token(), $token);
        return false;
    }
    public static function comparePost()
    {
        return self::compareToken(HTTPHelper::post('csrf_token'));
    }
}