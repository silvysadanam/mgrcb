<?php
/**
 * Checked View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Checked extends ViewHelper
{
    /**
     * get checked attribute
     * 
     * @param boolean|int $checked
     * @return string
     */
    public function checked($checked = false)
    {
        if (is_bool($checked)) {
            if (true === $checked) {
                return ' checked="checked"';
            }
        } elseif ($checked > 0) {
            return ' checked="checked"';
        }
        
        return '';
    }
    
}
