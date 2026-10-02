<?php
/** 
 * Lelam Factory
 * 
 * @category Lelam
 */

namespace Lelam;

use Web\Factory\FactoryTrait;


final class Factory
{
    const FORM = 'Form';
    const MODEL = 'Model';
    
    use FactoryTrait;
    
    /**
     * get model instance
     * 
     * @param string $name
     * @return object
     */
    public static function model($name)
    {
        return self::getInstance(self::buildByType(self::MODEL, $name));
    }
    
    /**
     * get form instance
     * 
     * @param string $name
     * @return object
     */
    public static function form($name)
    {
        return self::getInstance(self::buildByType(self::FORM, $name));
    }
    
    /**
     * get instance by type
     * 
     * @param string $name
     * @param string $type
     * @return object
     */
    public static function get($name, $type)
    {
        return self::getInstance(self::buildByType($type, $name, false));
    }
    
    /**
     * build class name
     * 
     * @param string $type
     * @param string $name
     * @param boolean $suffix
     * @return string
     */
    protected static function buildByType($type, $name, $suffix = true)
    {
        $path = [
            __NAMESPACE__,
            $type,
            self::fixSuffix($name, $type, $suffix),
        ];
        return self::build($path);
    }

}
