<?php
/**
 * PSR4 Class Autoloader
 */

class PSR4ClassAutoloader
{
    const DS = '/';
    const BACKSLASH = '\\';
    const EXTENSION = '.php';
    
    private $_paths;
    
    public function __construct()
    {
        $this->_paths = [];
    }
    
    /**
     * register spl autoload function
     */
    public function registerAutoload()
    {
        spl_autoload_register([$this, 'load']);
    }
    
    /**
     * register namespace
     * 
     * @param string $name
     * @param string $path
     */
    public function registerNamespace($name, $path)
    {
        $prefix = trim($name, self::BACKSLASH) . self::BACKSLASH;
        $dir = rtrim($path, self::DS) . self::DS;
        if (false === isset($this->_paths[$prefix])) {
            $this->_paths[$prefix] = [];
        }
        $this->_paths[$prefix][] = $dir;
    }
    
    /**
     * register vendor 
     * 
     * @param string $name
     * @param string $source
     */
    public function registerVendor($name, $source)
    {
        if (false !== strpos($name, BS)) {
            $parts = explode(BS, $name);
            $path = VENDOR_DIR . DS . $parts[0] . DS . trim($source, self::DS);
        } else {
            $path = VENDOR_DIR . DS . $name . DS . trim($source, self::DS);
        }
        
        $this->registerNamespace($name, $path);
    }
    
    /**
     * register custom vendor namespace
     * 
     * @param string $namespace
     * @param string $source
     */
    public function registerVendorNamespace($namespace, $source)
    {
        $path = VENDOR_DIR . DS . trim($source, self::DS);
        $this->registerNamespace($namespace, $path);
    }

    /**
     * autoload function 
     * 
     * @param string $name
     */
    public function load($name)
    {
        $prefix = $name;
        while (false !== ($pos = strrpos($prefix, self::BACKSLASH))) {
            // retain the trailing namespace separator in the prefix
            $cut = $pos + 1;
            $prefix = substr($name, 0, $cut);
            // the rest is the relative class name
            $relativeClass = substr($name, $cut);
            // try to load a mapped file for the prefix and relative class
            $found = $this->_find($prefix, $relativeClass);
            if ($found) {
                break;
            }
            // remove the trailing namespace separator for the next iteration
            // of strrpos()
            $prefix = rtrim($prefix, self::BACKSLASH);
        }
    }
    
    /**
     * get registered namespaces with paths
     * 
     * @return array
     */
    public function getRegisteredNamespaces()
    {
        return $this->_paths;
    }
        
    /**
     * find file path
     * 
     * @param string $path
     * @param string $name
     * @return boolean
     */
    private function _find($path, $name)
    {
        $file = false;
        if (false !== isset($this->_paths[$path])) {
            foreach ($this->_paths[$path] as $dir) {
                $filename = $dir . str_replace(self::BACKSLASH, self::DS, $name) . self::EXTENSION;
                if (file_exists($filename)) {
                    $file = true;
                    includeClassFile($filename);
                    break;
                }
            }
        }
        return $file;
    }
    
}

/**
 * include class file in a function
 * 
 * @param string $file
 */
function includeClassFile($file)
{
    include $file;
}
