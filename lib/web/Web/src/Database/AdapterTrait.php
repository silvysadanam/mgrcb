<?php
/**
 * Database Adapter Trait
 * 
 * @category   Web
 * @package    Web\Database
 */

namespace Web\Database;

use PDO;
use PDOException;


trait AdapterTrait
{
    /**
     * get PDO options
     * 
     * @return array
     */
    protected static function getOptions()
    {
        return [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ];
    }
    
    /**
     * get DSN for PDO
     * 
     * @param string $host
     * @param string $db
     * @return string
     */
    protected static function getDsn($host, $db)
    {
        return sprintf('mysql:host=%s;dbname=%s', $host, $db);
    }
    
    /**
     * connect to database
     * 
     * @param string $dsn
     * @param string $user
     * @param string $passwd
     * @return \PDO
     */
    protected static function connect($dsn, $user, $passwd)
    {
        try {
            $instance = new PDO($dsn, $user, $passwd, self::getOptions());
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        
        return $instance;
    }
    
}
