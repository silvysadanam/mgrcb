<?php
/**
 * Factory Trait
 * 
 * @category Web
 */

namespace Web\Factory;


trait FactoryTrait
{
    /**
     * build class name
     * 
     * @param array $list
     * @return string
     */
    protected static function build($list)
    {
        if (is_array($list)) {
            return implode(BS, $list);
        }
        
        return $list;
    }
    
    /**
     * fix suffix
     * 
     * @param string $name
     * @param string $suffix
     * @param boolean $required
     * @return string
     */
    protected static function fixSuffix($name, $suffix, $required = true)
    {
        $suffixLength = strlen($suffix);
        $offset = -1 * $suffixLength;
        $end = substr($name, $offset);
        if ($end == $suffix) {
            if (! $required) {
                $prefixStart = strlen($name) - $suffixLength;
                $name = substr($name, 0, $prefixStart);
            }
        } elseif ($required) {
            $name .= $suffix;
        }
        
        return $name;
    }
    
    /**
     * get class instance
     * 
     * @param string $class
     * @return boolean|\Web\Factory\class
     * @throws Exception
     */
    protected static function getInstance($class)
    {
        try {
            $reflection = new \ReflectionClass($class);
            if ($reflection->isInstantiable()) {
                return new $class;
            } else {
                throw new Exception('"' . $class . '" is not supported in Factory method');
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
        
        return false;
    }
    
}
