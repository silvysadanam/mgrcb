<?php
/**
 * Web Link plugin
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;

use Web\Core\Session;


class Link
{
    const LINK_SESSION = 'link';
    
    /**
     *
     * @var string 
     */
    private $_link;
    
    public function __construct()
    {
        if (Session::is(self::LINK_SESSION)) {
            $this->_link = Session::get(self::LINK_SESSION);
            Session::clear(self::LINK_SESSION);
        }
    }
    
    /**
     * set link
     * 
     * @param string $link
     */
    public function set($link)
    {
        Session::set(self::LINK_SESSION, $link);
    }
    
    /**
     * get flash
     * 
     * @return array
     */
    public function get()
    {
        return $this->_link;
    }
    
    /**
     * check
     * 
     * @return boolean
     */
    public function has()
    {
        return ! empty($this->_link);
    }
    
}
