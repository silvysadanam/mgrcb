<?php
/**
 * NotApplicable View helper
 * 
 * @category   Backend
 * @package    Backend\ViewHelper
 */

namespace Backend\ViewHelper;

use Web\View\ViewHelper;


class NotApplicable extends ViewHelper
{
   /**
    * get not applicable text
    * 
    * @param string $default
    * @return string
    */
    public function notApplicable($default = null)
    {
        if (null !== $default) {
            return $default;
        }
        
        return 'Not applicable';
    }
    
}
