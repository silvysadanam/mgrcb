<?php
/**
 * Lookup View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\LookupStore as LookupBank;


class Lookup extends ViewHelper
{
    /**
     * lookup
     * 
     * @param string $name
     * @param int $id
     * @param string $default
     * @param boolean $escape
     * @return string
     */
    public function lookup($name, $id, $default = null, $escape = true)
    {
        if (null !== ($value = LookupBank::find($name, $id))) {
            if ($escape) {
                return $this->escape($value);
            }
            return $value;
        }
        
        return $default;
    }
    
}
