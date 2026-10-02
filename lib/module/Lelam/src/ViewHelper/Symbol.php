<?php
/**
 * Symbol View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

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
