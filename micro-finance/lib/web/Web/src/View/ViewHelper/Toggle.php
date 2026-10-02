<?php
/**
 * Toggle View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\RowToggle;


class Toggle extends ViewHelper
{
    /**
     * get toggle string
     * 
     * @return string
     */
    public function toggle()
    {
        return RowToggle::get();
    }
    
}
