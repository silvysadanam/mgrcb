<?php
/**
 * Database Default Adapter
 * 
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Adapter
 */

namespace Web\Database\Adapter;

use Web\Exception;
use Web\Core\Registry;
use Web\Database\AdapterTrait;


class DefaultAdapter
{
    use AdapterTrait;
    
    /**
     * \PDO instance 
     * 
     * @var \PDO 
     */
    private static $instance = null;
    
    /**
     * constructor 
     */
    protected function __construct()
    {
       
    }
    
    /**
     * get database connection instance 
     * 
     * @return \PDO
     */
    public static function getAdapter()
    {
        if (null === self::$instance) {
            $config = Registry::database();
            if (isset($config['host']) && isset($config['db']) 
                && isset($config['user']) && isset($config['passwd'])) {
                
            } else {
                throw new Exception('Database connection details not found');
            }
            $dsn = self::getDsn($config['host'], $config['db']);
            self::$instance = self::connect($dsn, $config['user'], $config['passwd']);
        }
        
        return self::$instance;
    }
    
}
