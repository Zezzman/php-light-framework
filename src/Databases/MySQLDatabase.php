<?php
namespace System\Databases;

use System\Interfaces\IDatabase;
use System\Controller;
use PDO;
use PDOException;
use System\Models\SecretModel;
use System\Exceptions\RespondException;
/**
 *
 * @author  Francois Le Roux <francoisleroux97@gmail.com>
 */
class MySQLDatabase implements IDatabase
{
    public const TYPE = 'MySQL';
    private static $instance = null;
    private static $db = null;

    private function __construct() {}
    public static function instance()
    {
        if (isset(self::$instance)) {
            return self::$instance;
        } else {
            return new self();
        }
    }
    /**
     * 
     */
    public static function connect()
    {
        if (! is_null(self::$db)) {
            return true;
        }
        $config = config('DATABASE.MYSQL');

        // Try to connect to database
        if (! empty($config)
        && isset($config['HOST'])
        && isset($config['DATABASE_NAME'])
        && isset($config['USERNAME'])
        && isset($config['PASSWORD'])) {
            $db = null;

            $host = $config['HOST'];
            $dbName = $config['DATABASE_NAME'];
            $username = $config['USERNAME'];
            $password = (($config['PASSWORD'] instanceof SecretModel) ?
                $config['PASSWORD']->index(0): $config['PASSWORD']);
            
            // Connect to database
            $db = new PDO("mysql:host=$host;dbname=$dbName", $username, $password);

            // set the PDO error mode to exception
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, config('DATABASE.EMULATE_PREPARES', false));
            $db->setAttribute(PDO::ATTR_ERRMODE, config('DATABASE.ERROR_MODE', PDO::ERRMODE_EXCEPTION));

            // Capture dataConnection instance
            if (! is_null($db)) {
                self::$db = $db;
                return true;
            }
        } else {
            throw new RespondException(503, 'Empty database configurations');
        }
    }
    /**
     * 
     */
    public static function DB()
    {
        return self::$db;
    }
    /**
     * 
     */
    public static function close()
    {
        self::$db = null;
    }
    /**
     * 
     */
    public static function prepare($query, array $params = null, int $fetch = null, bool $group = false)
    {
        if (! is_null(self::$db)) {
            $statement = self::$db->prepare($query);
            if (! is_null($params)) {
                $executed = $statement->execute($params);
            } else {
                $executed = $statement->execute();
            }
            if (! is_null($fetch)) {
                if ($executed === true) {
                    if ($group) {
                        $result = $statement->fetchAll($fetch);
                    } else {
                        $result = $statement->fetch($fetch);
                    }
                    return $result;
                } else {
                    return false;
                }
            } else {
                return $statement;
            }
        } else {
            return false;
        }
    }
    /**
     * 
     */
    public static function lastID()
    {
        if(! is_null(self::$db)){
            return self::$db->lastInsertId();
        } else {
            return -1;
        }
    }
    /**
     * 
     */
    public static function logError(int $userID, string $message, $ip = null)
    {
        $fields = array(
            'user_id' => $userID,
            'ip' => $ip,
            'message' => $message,
            'page' => $_SERVER['REQUEST_URI']
        );
        $statement = self::prepare("INSERT INTO `data_errors` (`user_id`, `ip`, `message`, `page`) VALUES (:user_id, :ip, :message, :page)");
        $statement->execute($fields);
    }
}