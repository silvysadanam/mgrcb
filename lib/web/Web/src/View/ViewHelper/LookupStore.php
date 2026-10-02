<?php
/**
 * LookupStore View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\LookupStore as LookupStoreBank;


class LookupStore extends ViewHelper
{
    /**
     * get lookup list by name
     * 
     * @param string $name
     * @return array
     */
    public function lookupStore($name)
    {
        return LookupStoreBank::get($name);
    }
    
}
