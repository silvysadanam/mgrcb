<?php
/**
 * Style plugin
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;

use Web\Core\Registry;
use Web\Mvc\Module;


class Style
{
    const CSS = '.css';
    const LINK = '<link href="%s" rel="stylesheet">';
    const STYLE = '<style>%s</style>';
    const VERSION = '';
    
    /**
     *
     * @var array 
     */
    private $_styleUrl;
    
    /**
     *
     * @var array 
     */
    private $_styles;
    
    /**
     *
     * @var array 
     */
    private $_moduleStylesheets;
    
    /**
     *
     * @var array 
     */
    private $_stylesheets;
    
    /**
     *
     * @var string 
     */
    private $_moduleStyleUrl;
    
    /**
     *
     * @var string 
     */
    private $_theme;
    
    /**
     *
     * @var array 
     */
    private $_themeStylesheets;
    
    public function __construct()
    {
        $this->_styles = [];
        $this->_stylesheets = [];
        $this->_moduleStylesheets = [];
        $this->_themeStylesheets = [];
        $this->_styleUrl = Registry::get('mediaUrl') . 'css/';
        $module = preg_replace('/([A-Z])/', '-$1', lcfirst(Module::getModuleNamespace()));
        $this->_moduleStyleUrl = sprintf(Registry::get('mediaUrl') . 'modules/%s/', strtolower($module));
    }
    
    /**
     * set theme path
     * 
     * @param string $theme
     */
    public function setTheme($theme)
    {
        $this->_theme = $theme;
    }
    
    /**
     * add inline style
     * 
     * @param string $style
     * @return $this
     */
    public function addStyle($style)
    {
        $this->_styles[] = $style;
        
        return $this;
    }
    
    /**
     * add stylesheet
     * 
     * @param string $stylesheet
     * @return $this
     */
    public function addStylesheet($stylesheet, $module = false)
    {
        if ($module) {
            $this->addModuleStylesheet($stylesheet);
        } else {
            $this->_stylesheets[] = $stylesheet;
        }
        
        return $this;
    }
    
    /**
     * add module stylesheet
     * 
     * @param string $stylesheet
     * @return $this
     */
    public function addModuleStylesheet($stylesheet)
    {
        $this->_moduleStylesheets[] = $stylesheet;
        
        return $this;
    }
    
    /**
     * add theme stylesheet
     * 
     * @param string $stylesheet
     * @return $this
     */
    public function addThemeStylesheet($stylesheet)
    {
        $this->_themeStylesheets[] = $stylesheet;
        
        return $this;
    }

    /**
     * get styles html

     * @return string 
     */
    public function get()
    {
        $styles = [];
        $styleFiles = [];
        if (! empty($this->_stylesheets)) {
            foreach ($this->_stylesheets as $item) {
                if ($this->_isExternal($item)) {
                    $styles[] = $item;
                } else {
                    $css = ltrim($item, '/');
                    if (0 == preg_match('/\.css/', $item)) {
                        $css .= self::CSS;
                    }
                    $link = $this->_styleUrl . $css . self::VERSION;
                    if (! in_array($link, $styleFiles)) {
                        $styleFiles[] = $link;
                        $styles[] = sprintf(self::LINK, $link);
                    }
                }
            }
        }
        if (! empty($this->_moduleStylesheets)) {
            foreach ($this->_moduleStylesheets as $item) {
                $css = ltrim($item, '/');
                if (0 == preg_match('/\.css/', $item)) {
                    $css .= self::CSS;
                }
                $link = $this->_moduleStyleUrl . $css . self::VERSION;
                if (! in_array($link, $styleFiles)) {
                    $styleFiles[] = $link;
                    $styles[] = sprintf(self::LINK, $link);
                }
            }
        }
        if (! empty($this->_themeStylesheets)) {
            $themeUrl = sprintf(Registry::get('mediaUrl') . 'themes/%s/', $this->_theme);
            foreach ($this->_themeStylesheets as $item) {
                $css = ltrim($item, '/');
                if (0 == preg_match('/\.css/', $item)) {
                    $css .= self::CSS;
                }
                $link = $themeUrl . $css . self::VERSION;
                if (! in_array($link, $styleFiles)) {
                    $styleFiles[] = $link;
                    $styles[] = sprintf(self::LINK, $link);
                }
            }
        }
        if (! empty($this->_styles)) {
            $styles[] = sprintf(self::STYLE, implode(PHP_EOL, $this->_styles));
        }
        
        return implode(PHP_EOL, $styles);
    }
 
    
    /**
     * Check external url 
     * 
     * @param string $url
     * @return boolean 
     */
    private function _isExternal($url)
    {
        $external = false;
        if (1 == preg_match('#^http(s?)://#', $url)) {
            $external = true;
        } elseif (1 == preg_match('#^//#', $url)) {
            $external = true;
        }
        
        return $external;
    }
    
}
