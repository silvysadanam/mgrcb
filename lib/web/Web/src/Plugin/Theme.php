<?php
/**
 * Theme plugin
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;

use Web\Core\Registry;


class Theme
{
    const CHAMELEON = 'chameleon';
    const EQUIDEX = 'equidex';

    /**
     *
     * @var string 
     */
    private $_theme;
    
    /**
     *
     * @var string 
     */
    private $_themeUrl;

    public function __construct()
    {
        $this->_theme = self::CHAMELEON;
        $this->_themeUrl = Registry::get('mediaUrl') . 'themes/%s/';
    }
    
    /**
     * set theme
     * 
     * @param string $theme
     */
    public function set($theme)
    {
        $this->_theme = $theme;
    }
    
    /**
     * get theme url
     * 
     * @return string
     */
    public function getUrl()
    {
        return sprintf($this->_themeUrl, $this->_theme);
    }

}
