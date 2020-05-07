<?php
namespace System\Providers;

use System\Helpers\ArrayHelper;
use Exception;
/**
 * Manage environment variables
 * 
 * set/update/manage environment variables
 * 
 */
final class EnvironmentProvider
{
    const CONFIG_OPERATORS = [
        'separator' => '.',
        'add' => '+',
        'follow' => '~',
    ];
    private static $instance = null;
    public $configs = [];
    public $files = [];

    private function __construct() {}

    public static function instance()
    {
        if (! is_null(self::$instance)) {
            return self::$instance;
        } else {
            return self::$instance = new self();
        }
    }
    /**
     * Setup Environment
     */
    public function setup()
    {
        // Load configurations
        $this->getConfig();
        $this->getEnvironment();
    }
    /**
     * Update Configs
     */
    public function update(array $files = null)
    {
        $configs = [];
        if (is_null($files)) {
            foreach ($this->files as $name => $state) {
                if ($state === 1) {
                    $configs[$name] = $this->loadConfig($name);
                }
            }
            foreach ($configs as $key => $entry) {
                $configs[$key] = $this->add($entry);
            }
        } else {
            foreach ($files as $name) {
                $configs[$name] = $this->loadConfig($name);
            }
            foreach ($configs as $key => $entry) {
                $configs[$key] = $this->add($entry);
            }
        }
        return $configs;
    }
    /**
     * Get configurations from configuration files
     * within config folder
     */
    private function getConfig()
    {
        $this->addConfig([
            'app', 'permissions', 'namespaces',
            'auth', 'database', 'paths',
            'links', 'layout', 'collection'
        ]);
    }
    /**
     * Get configurations from environment files
     * within environment folder.
     * 
     * default.env.php is loaded by default.
     * "APP_ENVIRONMENT".env.php is loaded second if it exist.
     * 
     * Set environment by setting a global apache variable
     * "APP_ENVIRONMENT" to file name of environment file
     */
    private function getEnvironment()
    {
        $this->addEnvironment('default');
        $env = getenv('APP_ENVIRONMENT');
        if (! empty($env)) {
            $this->addEnvironment($env);
        }
    }
    /**
     * Environment configuration settings
     * 
     * @param   string      $key                key name within configuration settings
     * @param   mix         $default            default value when value is not found
     * 
     * @return  mix         return value related to the $key and return $default when value is not found
     */
    public function configurations(string $key = null, $default = false)
    {
        if (is_null($key)) {
            if ($this->configurations('PERMISSIONS.SHOW_CONFIGURATIONS') === true) {
                return $this->configs;
            } else {
                return $default;
            }
        } else {
            if (\strpos($key, self::CONFIG_OPERATORS['add'])
            || strpos($key, self::CONFIG_OPERATORS['follow']))
            {
                return self::searchOperations($this->configs, $key, $default);
            }
            return self::searchConfig($this->configs, $key, $default);
        }
    }
    /**
     * Set configuration settings
     * 
     * @param   string      $key                key name within configuration settings
     * @param   bool        $value              value of the key that will be set
     * 
     * @return  bool     return true when value is set
     */
    public function set(string $key, $value)
    {
        $configs = &$this->configs;
        if (is_array($configs)) {
            $key = trim(strtoupper($key), self::CONFIG_OPERATORS['separator']);
            $keys = explode(self::CONFIG_OPERATORS['separator'], $key);
            
            for ($i = 0; $i < count($keys); $i++) {
                $index = $keys[$i];
                if ($i < count($keys) - 1) {
                    if (isset($configs[$index])) {
                        if (is_array($configs[$index])) {
                            $configs = &$configs[$index];
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                } else {
                    $this->setKey($configs, $index, $value);
                }
            }
            return true;
        }
        return false;
    }
    /**
     * Set array value at key
     * 
     * @param   array       $configs            array with configurations
     * @param   string      $key                key name within array
     * @param   bool        $value              value of the key that will be set
     * 
     * @return  bool     return true when value is set
     */
    private function setKey(array &$configs, string $key, $value)
    {
        if (is_array($configs)) {
            if (isset($configs[$key])) {
                if (is_array($configs[$key])) {
                    if (is_array($value)) {
                        $configs[$key] = array_merge($configs[$key], $value); // merge values
                    } else {
                        // $configs[$key] = $value; // Set value | No push
                        array_push($configs[$key], $value); // Push value
                    }
                } else {
                    $configs[$key] = $value; // Set value
                }
            } else {
                $configs[$key] = $value; // Add value
            }
            return true;
        }
        return false;
    }
    /**
     * Merge configuration settings recursively
     * 
     * @param   array      $configs             configuration settings
     * @param   array      $changes              new configurations that will be merge with the $settings array
     * 
     * @return  array     return new merged array
     */
    private function mergeRecursively(array $configs, array $changes)
    {
        if (is_array($changes) && ! empty($changes)) {
            foreach ($changes as $key => $value) {
                if (isset($configs[$key])) {
                    if (is_array($configs[$key]) && is_array($value)) {
                        $this->setKey($configs, $key, $this->mergeRecursively($configs[$key], $value));
                    } else {
                        $this->setKey($configs, $key, $value);
                    }
                } else {
                    $this->setKey($configs, $key, $value);
                }
            }
        }
        return $configs;
    }
    /**
     * Add configurations to settings
     * 
     * @param   array      $configs              new configurations that will be merge with the environment settings
     * 
     * @return  bool     return true if configurations are added
     */
    public function add(array $configs)
    {
        if (! empty($configs)) {
            $this->configs = $this->mergeRecursively($this->configs, $configs);
            return true;
        }
        return false;
    }
    /**
     * Add configurations from config files
     * within environment folder.
     * 
     * @param   string|array       $environment         name of config file
     * 
     * @return  bool        true if config was added
     */
    public function addConfig($environment)
    {
        if (is_array($environment)) {
            foreach ($environment as $file) {
                $this->add($this->loadConfig($file));
            }
        } elseif (is_string($environment)) {
            return $this->add($this->loadConfig($environment));
        }
    }
    /**
     * Add configurations from environment files
     * within environment folder.
     * 
     * @param   string       $environment         name of environment file
     * 
     * @return  bool        true if environment was added
     */
    public function addEnvironment($environment)
    {
        return $this->add($this->loadEnvironment($environment));
    }
    /**
     * Load configurations from configuration file
     * 
     * @param   string        $name              name of configuration file with extension omit
     * 
     * @return  array        return loaded configurations
     */
    public function loadConfig(string $name)
    {
        if (! empty($name)) {
            $cwd = getcwd();
            $path = $cwd . '/configs/' . $name . '.php';
            if (file_exists($path)) {
                $config = require($path);
                if (is_array($config)) {
                    $this->files[$name] = 1;
                    return $config;
                } else {
                    $this->files[$name] = 0;
                }
            } else {
                throw new Exception('Configuration File Not Found: ' . $path);
            }
        }
        return [];
    }
    /**
     * Load configurations from environment file
     * 
     * @param   string        $name              name of configuration file with extension omit
     * 
     * @return  array        return loaded configurations
     */
    public function loadEnvironment(string $name)
    {
        return $this->loadConfig('environments/' . $name);
    }
    /**
     * Load secrets from file within secret Folder
     * 
     * @param   string        $name              name of secret file with extension omit
     * 
     * @return  array        return loaded secrets
     */
    public function loadSecrets($name, string $key = null, $default = false)
    {
        return \System\Helpers\FileHelper::loadSecrets($name, $key, $default);
    }
    /**
     * DNS
     * 
     * @return  string        return DNS
     */
    private function domain()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $serverName = $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $serverName = $_SERVER['SERVER_NAME'];
        }
        if (! empty($serverName)) {
            return ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $serverName . '/';
        }
        return '';
    }
    /**
     * The type the client use to request resources
     * 
     * @return  string        return client type of requesting a client
     */
    private function clientType()
    {
        if(php_sapi_name() === 'cli'){
            if(isset($_SERVER['TERM'])){
                return 'cli';
            } else{
                return 'cronjob';
            }
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    return 'api';
                } else {
                    http_response_code(406);
                    throw new Exception('Not Acceptable');
                    exit();
                }
            } else {
                return 'webserver';
            }
        }
    }

    /**
     * Search Configurations
     * 
     * @param   array       $config             configuration settings
     * @param   string      $key                key name within configuration settings
     * @param   mix         $default            default value when value is not found
     * 
     * @return  mix         return value related to the $key and return $default when value is not found
     */
    public static function searchConfig(array $configs, string $key, $default = false)
    {
        if (empty($key)) {
            return $default;
        }

        $constant = ArrayHelper::deepSearch($configs, strtoupper($key), self::CONFIG_OPERATORS['separator']);
        if (is_null($constant)) {
            return $default;
        } else {
            return $constant;
        }
    }
    /**
     * Search Operations
     * 
     * @param   array       $config             configuration settings
     * @param   string      $key                key name within configuration settings
     * @param   mix         $default            default value when value is not found
     * 
     * @return  mix         return value related to the $key and return $default when value is not found
     */
    public static function searchOperations(array $configs, string $sequence, $default = false)
    {
        $items = \explode(self::CONFIG_OPERATORS['add'], $sequence);
        $config = null;
        $type = null;
        foreach ($items as $item)
        {
            $blocks = \explode(self::CONFIG_OPERATORS['follow'], $item);
            if (\count($blocks) > 1)
            {
                $base = null;
                $blockType = null;
                foreach ($blocks as $block)
                {
                    if ($pos = \strpos(\strrev($block), self::CONFIG_OPERATORS['separator']))
                    {
                        $base = $base ?? self::searchConfig($configs, \substr($block, 0, -$pos - 1));
                        $name = \substr($block, -$pos);
                    }
                    else
                    {
                        $name = $block;
                    }
                    if (! empty($base))
                    {
                        $blockResult = self::searchConfig($base, $name, $default);
                    }
                    else
                    {
                        $blockResult = self::searchConfig($configs, $name, $default);
                    }
                    $blockType = $blockType ?? \gettype($blockResult);
                    if ($blockType == 'string' || $blockType == 'integer')
                    {
                        $result = $result ?? '';
                        if (is_string($blockResult)
                        || is_numeric($blockResult))
                        {
                            $result .= (string)$blockResult;
                        }
                    }
                    else
                    {
                        $result = $result ?? [];
                        $result[] = $blockResult;
                    }
                }
            }
            else
            {
                $result = self::searchConfig($configs, $item, $default);
            }
            $type = $type ?? \gettype($result);
            if ($type == 'string' || $type == 'integer')
            {
                $config = $config ?? '';
                $config .= (string)$result;
            }
            else
            {
                $config = $config ?? [];
                $config[] = $result;
            }
        }
        return $config;
    }
}