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


class CustomAdapter
{
    use AdapterTrait;
    
    const DATABASE_KEY = 'database_custom';
    
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
            if (null !== ($config = Registry::application(self::DATABASE_KEY))) {
                if (isset($config['host']) && isset($config['db']) 
                    && isset($config['user']) && isset($config['passwd'])) {
                    $dsn = self::getDsn($config['host'], $config['db']);
                    self::$instance = self::connect($dsn, $config['user'], $config['passwd']);
                }                
            }
        }
        if (null === self::$instance) {
            throw new Exception('Database connection details for "' . self::DATABASE_KEY . '" not found');
        }
        
        return self::$instance;
    }
    
}
