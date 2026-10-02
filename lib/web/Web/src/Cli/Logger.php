<?php
/**
 * Cli logger
 * 
 * @category Web
 * @package Web\Cli
 */

namespace Web\Cli;

use Web\Utility\DateHelper;


class Logger
{
    /**
     *
     * @var boolean 
     */
    private $_output = true;
    
    /**
     *
     * @var int 
     */
    private $_queries = 0;
    
    public function __construct()
    {
        
    }
    
    /**
     * enable logger
     */
    public function enable()
    {
        $this->_output = true;
    }
    
    /**
     * disable logger
     */
    public function disable()
    {
        $this->_output = false;
    }
    
    /**
     * print message
     * 
     * @param string $message
     */
    public function message($message)
    {
        if ($this->_output) {
            echo PHP_EOL . $message;
        }
    }
    
    /**
     * print variable
     * 
     * @param mixed $variable
     */
    public function dump($variable)
    {
        if ($this->_output) {
            echo PHP_EOL;
            print_r($variable);
        }
    }
    
    /**
     * print current time
     * 
     * @param mixed $format
     */
    public function now($format = null)
    {
        if (null !== $format) {
            if (false === $format) {
                $now = DateHelper::timestamp();
            } else {
                if (empty($format)) {
                    $now = DateHelper::format(DateHelper::MYSQL_DATETIME);
                } elseif (true === $format) {
                    $now = DateHelper::format(DateHelper::MYSQL_TIME);
                } else {
                    $now = DateHelper::format($format);
                }
            }
        } else {
            $now = DateHelper::format(DateHelper::MYSQL_DATETIME);
        }
        $this->message($now);
    }
    
    /**
     * add query count
     * 
     * @param int $count
     * @return int
     */
    public function query($count = 1)
    {
        if (true !== $count) {
            $this->_queries += $count;
        }
        
        return $this->_queries;
    }
    
}
