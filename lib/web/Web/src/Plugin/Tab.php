<?php
/**
 * Tabs Plugin
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;


class Tab
{
    /**
     *
     * @var int 
     */
    private $_current = 0;
    
    /**
     *
     * @var string 
     */
    private $_subTab = '';
    
    public function __construct()
    {
        
    }
    
    /**
     * set tab
     * 
     * @param int $tab
     */
    public function set($tab)
    {
        $this->_current = $tab;
    }
    
    /**
     * get tab
     * 
     * @return int
     */
    public function get()
    {
        return $this->_current;
    }
    
    /**
     * set sub tab
     * 
     * @param string $tab
     */
    public function setSubTab($tab)
    {
        $this->_subTab = $tab;
    }
    
    /**
     * get sub tab
     * 
     * @return string
     */
    public function getSubTab()
    {
        return $this->_subTab;
    }
    
}
