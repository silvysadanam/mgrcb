<?php
/**
 * Checked View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

use Web\View\ViewHelper;


class Checked extends ViewHelper
{
    /**
     * get checked attribute
     * 
     * @param boolean $checked
     * @return string
     */
    public function checked($checked = false)
    {
        if ($checked) {
            return ' checked="checked"';
        }
        
        return '';
    }
    
}
