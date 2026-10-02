<?php
/**
 * ActiveTab View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class ActiveTab extends ViewHelper
{
    /**
     * get tab active class
     * 
     * @param int $tab
     * @param int $current
     * @return string
     */
    public function activeTab($tab, $current = null)
    {
        if ($tab == $current) {
            return ' active';
        }
        return '';
    }
    
}
