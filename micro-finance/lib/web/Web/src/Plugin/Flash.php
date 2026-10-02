<?php
/**
 * Web Flash plugin
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;

use Web\Core\Session;


class Flash
{
    const FLASH_SESSION = 'flash';
    const SUCCESS = 'success';
    const ERROR = 'error';
    const WARNING = 'warning';
    
    /**
     *
     * @var array 
     */
    private $_flash;
    
    public function __construct()
    {
        $this->_flash = [
            'hasFlash' => false,
            'message' => '',
            'type' => self::SUCCESS,
        ];
        if (Session::is(self::FLASH_SESSION)) {
            $this->_flash['hasFlash'] = true;
            $flash = unserialize(Session::get(self::FLASH_SESSION));
            $this->_flash['message'] = $flash['message'];
            Session::clear(self::FLASH_SESSION);
            if (isset($flash['type'])) {
                $this->_flash['type'] = $flash['type'];
            }
        }
    }
    
    /**
     * set flash message
     * 
     * @param string $message
     * @param string $type
     */
    public function set($message, $type = self::SUCCESS)
    {
        $flash = ['message' => $message, 'type' => $type];
        Session::set(self::FLASH_SESSION, serialize($flash));
    }
    
    /**
     * get flash
     * 
     * @return array
     */
    public function get()
    {
        return $this->_flash;
    }
    
    /**
     * check flash
     * 
     * @return boolean
     */
    public function hasFlash()
    {
        return $this->_flash['hasFlash'];
    }
    
    /**
     * get message
     * 
     * @return string
     */
    public function getMessage()
    {
        return $this->_flash['message'];
    }
    
    /**
     * get flash type
     * 
     * @return string
     */
    public function getType()
    {
        return $this->_flash['type'];
    }
    
}
