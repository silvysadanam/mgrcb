<?php
/**
 * Plugin Debug 
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;

use Web\Core\Registry;


class Debug
{
    /**
     *
     * @var array 
     */
    private $_stack = [];
    
    /**
     *
     * @var boolean 
     */
    private $_on = true;
    
    public function __construct()
    {
        $settings = Registry::get('debug');
        if (isset($settings['debug'])) {
            $this->_on = 1 == $settings['debug'];
        }
    }
    
    /**
     * set debug status
     * 
     * @param boolean $on
     */
    public function on($on = true)
    {
        $this->_on = (bool) $on;
        
        return $this->_on;
    }
    
    /**
     * set debug off or check status
     * 
     * @param mixed $status
     */
    public function off($status = null)
    {
        if (null === $status) {
            $this->_on = false;
        }
        
        return $this->_on;
    }
    
    /**
     * get debug stack
     * 
     * @return array
     */
    public function get()
    {
        return $this->_stack;
    }
    
    /**
     * add variable
     * 
     * @param mixed $variable
     * @param string $title
     */
    public function add($variable, $title = null, $file = null, $line = null)
    {
        $this->_stack[] = [$title, $variable, $file, $line];
    }
    
    /**
     * stop execution
     */
    public function stop()
    {
        die('Stopped in ' . __CLASS__);
    }
    
    /**
     * output stacked variables
     * 
     * @param boolean $stop
     * @param boolean $force
     */
    public function output($stop = false, $force = false)
    {
        if (! empty($this->_stack)) {
            if ($this->_on || $force) {
                foreach ($this->_stack as $data) {
                    $this->_show($data);
                }
            }
        }
        
        $stop and $this->stop();
    }
    
    /**
     * clear stack
     */
    public function clear()
    {
        $this->_stack = [];
    }
    
    /**
     * show variable
     * 
     * @param mixed $variable
     * @param string $title
     * @param string $file
     * @param int $line
     * @param boolean $force
     * @param boolean $stop
     */
    public function show($variable, $title = null, $file = null, $line = null, $force = false, $stop = false)
    {
        if ($this->_on || $force) {
            $this->_show([$variable, $title, $file, $line]);
        }
        $stop and $this->stop();
    }
    
    
    /**
     * show variable
     * 
     * @param array $data
     */
    private function _show($data)
    {
        echo '<br>';
        if (! empty($data[1])) {
            echo '== ' . $data[1] . ' == ';
        }
        if (! empty($data[2])) {
            echo '== File: ' . $data[2] . ' == ';
        }
        if (! empty($data[3])) {
            echo '== Line: ' . $data[3];
        }
        echo '<br><pre>';
        var_dump($data[0]);
        echo '</pre>';
    } 
    
}
