<?php
/**
 * Script plugin
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;

use Web\Core\Registry;
use Web\Mvc\Module;


class Script
{
    const SCRIPT_HEAD = 1;
    const SCRIPT_BODY = 2;
    const SCRIPT_INLINE = 3;
    const SCRIPT_EXTERNAL = 4;
    const JS = '.js';
    const SCRIPT = '<script src="%s"></script>';
    const INLINE = '<script>%s</script>';
    const VERSION = '?v=6';
    
    /**
     *
     * @var string 
     */
    private $_scriptUrl;
    
    /**
     *
     * @var string 
     */
    private $_moduleScriptUrl;
    
    /**
     *
     * @var array 
     */
    private $_scripts;
    
    /**
     *
     * @var array 
     */
    private $_moduleScripts;
    
    /**
     *
     * @var array 
     */
    private $_jquery;
    
    /**
     *
     * @var string 
     */
    private $_jqueryUrl;
    
    /**
     *
     * @var array 
     */
    private $_js;
    
    /**
     *
     * @var string 
     */
    private $_vesion = '';
    
    /**
     *
     * @var string 
     */
    private $_theme = '';
    
    /**
     *
     * @var array 
     */
    private $_themeScripts;

    public function __construct()
    {
        $this->_scripts = [
            self::SCRIPT_HEAD => [],
            self::SCRIPT_BODY => [],
            self::SCRIPT_INLINE => [],
            self::SCRIPT_EXTERNAL => [],
        ];
        
        $this->_js = [];
        $this->_jquery = [];
        $this->_moduleScripts = [];
        $this->_themeScripts = [];
        $this->_scriptUrl = Registry::get('mediaUrl') . 'js/';
        $this->_jqueryUrl = $this->_scriptUrl . 'jquery/';
        $module = preg_replace('/([A-Z])/', '-$1', lcfirst(Module::getModuleNamespace()));
        $this->_moduleScriptUrl = sprintf(Registry::get('mediaUrl') . 'modules/%s/js/', strtolower($module));
        $this->_vesion = self::VERSION;
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
     * set script version
     * 
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->_vesion = $version;
    }
    
    /**
     * add script
     * 
     * @param int|string $script
     * @param int $type
     */
    public function add($script, $type = self::SCRIPT_BODY)
    {
        if (null === $type) {
            $type = self::SCRIPT_BODY;
        }
        $this->_scripts[$type][] = $script;
        
        return $this;
    }
    
    /**
     * add <head> script
     * 
     * @param int|string $script
     */
    public function head($script)
    {
        $this->add($script, self::SCRIPT_HEAD);
        
        return $this;
    }
    
    /**
     * add <body> script
     * 
     * @param int|string $script
     */
    public function body($script)
    {
        $this->add($script, self::SCRIPT_BODY);
        
        return $this;
    }
    
    /**
     * add external script
     * 
     * @param string $script
     */
    public function external($script)
    {
        $this->add($script, self::SCRIPT_EXTERNAL);
        
        return $this;
    }
    
    /**
     * add inline script
     * 
     * @param string $script
     */
    public function inline($script)
    {
        $this->add($script, self::SCRIPT_INLINE);
        
        return $this;
    }
    
    /**
     * add module head script
     * 
     * @param string $script
     */
    public function addModuleHead($script)
    {
        if (! isset($this->_moduleScripts[self::SCRIPT_HEAD])) {
            $this->_moduleScripts[self::SCRIPT_HEAD] = [];
        }
        $this->_moduleScripts[self::SCRIPT_HEAD][] = $script;
    }
    
    /**
     * add module body script
     * 
     * @param string $script
     */
    public function addModuleBody($script)
    {
        if (! isset($this->_moduleScripts[self::SCRIPT_BODY])) {
            $this->_moduleScripts[self::SCRIPT_BODY] = [];
        }
        $this->_moduleScripts[self::SCRIPT_BODY][] = $script;
    }
    
    /**
     * add jquery head script
     * 
     * @param string $script
     */
    public function addJqueryHead($script)
    {
        if (! isset($this->_jquery[self::SCRIPT_HEAD])) {
            $this->_jquery[self::SCRIPT_HEAD] = [];
        }
        $this->_jquery[self::SCRIPT_HEAD][] = $script;
    }
    
    /**
     * add jquery body script
     * 
     * @param string $script
     */
    public function addJqueryBody($script)
    {
        if (! isset($this->_jquery[self::SCRIPT_BODY])) {
            $this->_jquery[self::SCRIPT_BODY] = [];
        }
        $this->_jquery[self::SCRIPT_BODY][] = $script;
    }
    
    /**
     * add theme head script
     * 
     * @param string $script
     */
    public function addThemeHead($script)
    {
        if (! isset($this->_themeScripts[self::SCRIPT_HEAD])) {
            $this->_themeScripts[self::SCRIPT_HEAD] = [];
        }
        $this->_themeScripts[self::SCRIPT_HEAD][] = $script;
    }
    
    /**
     * add theme body script
     * 
     * @param string $script
     */
    public function addThemeBody($script)
    {
        if (! isset($this->_themeScripts[self::SCRIPT_BODY])) {
            $this->_themeScripts[self::SCRIPT_BODY] = [];
        }
        $this->_themeScripts[self::SCRIPT_BODY][] = $script;
    }

    /**
     * get scripts html
     * 
     * @param int $type 
     * @return string 
     */
    public function get($type = self::SCRIPT_BODY)
    {
        $scripts = [];
        $scriptUrls = [];
        if (! empty($this->_scripts[$type])) {
            switch ($type) {
                case self::SCRIPT_BODY:
                case self::SCRIPT_HEAD:
                     foreach ($this->_scripts[$type] as $item) {
                        if (is_numeric($item)) {
                            if (isset($this->_js[$item])) {
                                $js = $this->_scriptUrl . $this->_js[$item];
                                if (! in_array($js, $scriptUrls)) {
                                    $scriptUrls[] = $js;
                                    $scripts[] = sprintf(self::SCRIPT, $js . $this->_vesion);
                                }
                            }
                        } else {
                            if ($this->_isExternal($item)) {
                                $scripts[] = $item;
                            } else {
                                $js = ltrim($item, '/');
                                if (0 == preg_match('/\.js/', $item)) {
                                    $js .= self::JS;
                                }
                                if (! in_array($js, $scriptUrls)) {
                                    $scriptUrls[] = $js;
                                    $scripts[] = sprintf(self::SCRIPT, $this->_scriptUrl . $js);
                                }
                            }
                        }
                    }
                    if (! empty($this->_jquery[$type])) {
                        foreach ($this->_jquery[$type] as $item) {
                            $js = ltrim($item, '/');
                            if (0 == preg_match('/\.js/', $item)) {
                                $js .= self::JS;
                            }
                            if (! in_array($js, $scripts)) {
                                $scripts[] = sprintf(self::SCRIPT, $this->_scriptUrl . $js);
                            }
                            $scripts[] = sprintf(self::SCRIPT, $this->_scriptUrl . $js);
                        }
                        
                    }
                    break;
                    
                case self::SCRIPT_EXTERNAL:
                    foreach ($this->_scripts[$type] as $item) {
                        if (! in_array($item, $scriptUrls)) {
                            $scriptUrls[] = $item;
                            $scripts[] = sprintf(self::SCRIPT, $item);
                        }
                    }
                    break;
                    
                case self::SCRIPT_INLINE:
                    foreach ($this->_scripts[$type] as $item) {
                        $scripts[] = sprintf(self::INLINE, PHP_EOL . $item . PHP_EOL);
                    }
                    break;
                    
                default:
                    break;
            }
        }
        if (! empty($this->_jquery[$type])) {
            foreach ($this->_jquery[$type] as $item) {
                $js = ltrim($item, '/');
                if (0 == preg_match('/\.js/', $item)) {
                    $js .= self::JS;
                }
                $url = $this->_jqueryUrl . $js;
                if (! in_array($url, $scriptUrls)) {
                    $scriptUrls[] = $url;
                    $scripts[] = sprintf(self::SCRIPT, $url);
                }
            }
        }
        if (! empty($this->_moduleScripts[$type])) {
            foreach ($this->_moduleScripts[$type] as $item) {
                $js = ltrim($item, '/');
                if (0 == preg_match('/\.js/', $item)) {
                    $js .= self::JS;
                }
                $url = $this->_moduleScriptUrl . $js;
                if (! in_array($url, $scriptUrls)) {
                    $scriptUrls[] = $url;
                    $scripts[] = sprintf(self::SCRIPT, $url . $this->_vesion);
                }
            }
        }
        if (! empty($this->_themeScripts[$type])) {
            $themeUrl = sprintf(Registry::get('mediaUrl') . 'themes/%s/', $this->_theme);
            foreach ($this->_themeScripts[$type] as $item) {
                $js = ltrim($item, '/');
                if (0 == preg_match('/\.js/', $item)) {
                    $js .= self::JS;
                }
                $url = $themeUrl . $js;
                if (! in_array($url, $scriptUrls)) {
                    $scriptUrls[] = $url;
                    $scripts[] = sprintf(self::SCRIPT, $url . $this->_vesion);
                }
            }
        }
        
        return implode(PHP_EOL, $scripts);
    }
    
    /**
     * get scripts list
     * 
     * @return array
     */
    public function getAll()
    {
        $scripts = [];
        $types = array_keys($this->_scripts);
        foreach ($types as $type) {
            $scripts[$type] = $this->get($type);
        }
        
        return $scripts;
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
