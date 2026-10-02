<?php
/**
 * Checked View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

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
