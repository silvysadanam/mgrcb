<?php
/**
 * Web Message plugin
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;


class Message
{
    const CREATE = 1;
    const CHANGE = 2;
    const REMOVE = 3;
    const NO_CHANGE = 4;
    
    /**
     *
     * @var string 
     */
    private $_title;
    
    /**
     *
     * @var array 
     */
    private $_formats;
    
    /**
     *
     * @var array 
     */
    private $_excluded;
    
    public function __construct()
    {
        $this->_formats = [
            self::CREATE => '%s created',
            self::CHANGE => '%s updated',
            self::REMOVE => '%s deleted',
            self::NO_CHANGE => '%s not changed',
        ];
        $this->_excluded = [];
    }
    
    /**
     * set title
     * 
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }
    
    /**
     * get message
     * 
     * @param int $type
     * @return string
     */
    public function message($type = self::CREATE)
    {
        return $this->_getMessage($type);
    }
    
    /**
     * created message
     * 
     * @return string
     */
    public function created()
    {
        return $this->_getMessage(self::CREATE);
    }
    
    /**
     * changed message
     * 
     * @return string
     */
    public function changed()
    {
        return $this->_getMessage(self::CHANGE);
    }
    
    /**
     * removed message
     * 
     * @return string
     */
    public function removed()
    {
        return $this->_getMessage(self::REMOVE);
    }
    
    /**
     * not changed message
     * 
     * @return string
     */
    public function notChanged()
    {
        return $this->_getMessage(self::NO_CHANGE);
    }
    
    
    /**
     * get message formatted by type
     * 
     * @param int $type
     * @return string
     */
    private function _getMessage($type)
    {
        if (isset($this->_formats[$type])) {
            if (! in_array($type, $this->_excluded)) {
                return sprintf($this->_formats[$type], $this->_title);
            }
            return $this->_formats[$type];
        }
        
        return $type;
    }
    
}
