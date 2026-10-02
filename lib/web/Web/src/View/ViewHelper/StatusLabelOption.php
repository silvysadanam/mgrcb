<?php
/**
 * StatusLabel Option View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\StatusLabelHelper;


class StatusLabelOption extends ViewHelper
{
    /**
     * set status label options
     * 
     * @param array $options
     */
    public function statusLabelOption($options)
    {
        StatusLabelHelper::options($options);
    }
    
}
