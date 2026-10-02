<?php
/**
 * Redirect plugin
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;

use Web\Core\Registry;
use Web\Core\Server;
use Web\Mvc\Module;


class Redirect
{
    const LOCATION = 'Location:';
    
    /**
     *
     * @var string 
     */
    private $_actionUrl;
    
    /**
     *
     * @var module 
     */
    private $_module;
    
    public function __construct()
    {
        $this->_actionUrl = Registry::get('actionUrl');
        $this->_module = Module::getModuleName();
    }
    
    /**
     * go to url
     * 
     * @param string $url
     */
    public function go($url)
    {
        $this->_redirect($url);
    }
    
    /**
     * redirect to home
     */
    public function home()
    {
        $this->_redirect('');
    }
    
    /**
     * go to action
     * 
     * @param string $url
     */
    public function action($url = '')
    {
        if ('' != $this->_module) {
            $this->module($this->_module, $url);
        }
        $this->_redirect($url);
    }
    
    /**
     * go to module url
     * 
     * @param string $module
     * @param string $url
     */
    public function module($module, $url = '')
    {
        if ('' != $url) {
            $this->_redirect($module . DS . ltrim($url, DS));
        }
        $this->_redirect($module);
    }
    
    /**
     * reload page
     */
    public function reload()
    {
         header(self::LOCATION . $this->_cleanUrl(Server::requestUri()));
         exit;
    }
    
    /**
     * permanent redirect
     * 
     * @param string $url
     */
    public function permanent($url)
    {
        $this->_redirect($url, true);
    }
    
    /**
     * redirect by full url
     * 
     * @param string $url
     */
    public function full($url = '')
    {
        $this->_redirect(Registry::get('siteUrl') . ltrim($url, DS));
    }
    
    
    /**
     * header redirect
     * 
     * @param string $url
     * @param boolean $permanent
     */
    private function _redirect($url = '', $permanent = false)
    {
        if ('' != $url) {
            $url = ltrim($url, DS);
            if (0 == preg_match('#^http(s?)://#', $url)) {
                $url = $this->_actionUrl . $url;
            }
        } else {
            $url = $this->_actionUrl;
        }
        if ($permanent) {
            header(self::LOCATION . $this->_cleanUrl($url), true, 301);
        } else {
            header(self::LOCATION . $this->_cleanUrl($url));
        }
        
        exit;
    }
    
    /**
     * clean redirect url
     * 
     * @param string $url
     * @return string
     */
    private function _cleanUrl($url)
    {
        return str_replace(['&amp;', '"', '\''], ['&', '', ''], $url);
    }
    
}
