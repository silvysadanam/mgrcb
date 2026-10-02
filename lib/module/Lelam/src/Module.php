<?php
/**
 * Lelam Module
 * 
 * @category Lelam
 */

namespace Lelam;


class Module
{    
    public function __construct() {}
    
    /**
     * get module config
     * 
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.php';
    }
    
}
