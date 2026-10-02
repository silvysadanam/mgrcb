<?php
/**
 * Symbol View helper
 * 
 * @category   Backend
 * @package    Backend\ViewHelper
 */

namespace Backend\ViewHelper;

use Web\View\ViewHelper;
use Shared\Utility\HtmlSymbol;


class Symbol extends ViewHelper
{
    /**
     * get symbol
     * 
     * @param string $key
     * @return string
     */
    public function symbol($key)
    {
        return HtmlSymbol::get($key);
    }
    
}
