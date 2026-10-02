<?php
/**
 * Web Core View Template
 * 
 * @category Web
 * @package Web\View
 */

namespace Web\View;

use Web\Mvc\Module;


class Template
{
    const LAYOUTS = '/layouts/';
    const SCRIPTS = '/scripts/';
    const BLOCKS = '/blocks/';
    const PORTLETS = '/portlets/';
    const EXTENSION = '.phtml';
    
    /**
     *
     * @var string 
     */
    private $_viewPath;
    
    public function __construct()
    {
        $this->_viewPath = Module::getViewPath();
    }
    
    /**
     * get layout file
     * 
     * @param string $name
     * @return string
     */
    public function layout($name)
    {
        return $this->_getFile(self::LAYOUTS, $name);
    }
    
    /**
     * get script file
     * 
     * @param string $name
     * @return string
     */
    public function script($name)
    {
        return $this->_getFile(self::SCRIPTS, $name);
    }
    
    /**
     * get block file
     * 
     * @param string $name
     * @return string
     */
    public function block($name)
    {
        return $this->_getFile(self::BLOCKS, $name);
    }
    
    /**
     * get portlet file
     * 
     * @param string $name
     * @return string
     */
    public function portlet($name)
    {
        return $this->_getFile(self::PORTLETS, $name);
    }
    
    /**
     * get template filename
     * 
     * @param string $type
     * @param string $name
     * @return boolean|string
     */
    private function _getFile($type, $name)
    {
        if ('' != $name) {
            $file = $this->_viewPath . $type 
                  . preg_replace('/' . preg_quote(self::EXTENSION) . '$/', '', trim($name, DS))
                  . self::EXTENSION;
            if (file_exists($file)) {
                return $file;
            }
        }
        
        return false;
    }
    
}
