<?php
/**
 * LookupIndentation View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\Form\FormHelper;


class LookupIndentation extends ViewHelper
{
    /**
     * set lookup tag indentation tabs
     * 
     * @param int $tabs
     */
    public function lookupIndentantion($tabs = 0)
    {
        FormHelper::tabs($tabs);
    }
    
}
