<?php
/**
 * Shared Option OptionTrait
 * 
 * @category Shared
 * @package  Shared\Option
 */

namespace Shared\Option;

use ReflectionClass;


trait OptionTrait
{

    /**
     * get defined options
     * 
     * @return array
     */
    public static function get()
    {
        $options = [];
        $class = new ReflectionClass(static::class);
        $constants = $class->getConstants();
        if (! empty($constants)) {
            foreach ($constants as $item) {
                $options[$item] = $item;
            }
        }
        
        return $options;
    }
    
}
