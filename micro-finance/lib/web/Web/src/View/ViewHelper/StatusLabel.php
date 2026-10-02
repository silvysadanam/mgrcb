<?php
/**
 * StatusLabel View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\StatusLabelHelper;


class StatusLabel extends ViewHelper
{
    /**
     * get status label
     * 
     * @param int $key
     * @return string
     */
    public function statusLabel($key)
    {
        return StatusLabelHelper::get($key);
    }
    
}
