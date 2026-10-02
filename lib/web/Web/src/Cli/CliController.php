<?php
/**
 * Cli Controller
 * 
 * @category Web
 * @package Web\Cli
 */

namespace Web\Cli;

use Web\Cli\Logger;
use Web\Core\Registry;


class CliController
{
    /**
     * logger instance
     * 
     * @var \Web\Cli\Logger 
     */
    protected $_logger;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_logger = new Logger();
    }
    
    /**
     * start script
     * 
     * @param string $message
     */
    protected function start($message = '')
    {
        $this->_logger->message('Starting ' . $message);
        $this->_logger->now('Y-m-d H:i:s T');
    }
    
    /**
     * send output message
     * 
     * @param mixed $message
     */
    protected function out($message)
    {
        if (is_scalar($message)) {
            $this->_logger->message($message);
        } else {
            $this->_logger->dump($message);
        }
    }
    
    /**
     * enable logger
     */
    protected function enableLogger()
    {
        $this->_logger->enable();
    }
    
    /**
     * disable logger
     */
    protected function disableLogger()
    {
        $this->_logger->disable();
    }
    
    /**
     * finish script
     */
    protected function finish()
    {
        $this->_logger->message('Queries: ' . $this->_logger->query(true));
        $this->_logger->now();
        $this->_logger->message('Finished' . PHP_EOL);
    }
    
    /**
     * get logger
     * 
     * @return \Web\Cli\Logger
     */
    protected function getLogger()
    {
        return $this->_logger;
    }
    
    /**
     * print current time
     * 
     * @param string $format
     */
    protected function now($format = null)
    {
        $this->_logger->now($format);
    }
    
    /**
     * get registry value
     * 
     * @param string $name
     * @return mixed
     */
    protected function registry($name)
    {
        return Registry::get($name);
    }
    
    /**
     * check environment is production
     */
    protected function isProduction()
    {
        return 'production' == $this->registry('environment');
    }
    
    /**
     * get url param
     * 
     * @param string $name
     * @param boolena $numeric
     * @return string|int
     */
    protected function getParam($name = 'id', $numeric = false)
    {
        $value = '';
        $params = $this->registry('params');
        if (isset($params[$name])) {
            $value = $params[$name];
        }
        if ($numeric) {
            return (int) $value;
        }
        
        return $value;
    }
    
}
