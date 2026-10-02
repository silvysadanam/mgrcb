<?php
/**
 * Counter View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\RowCounter;


class Counter extends ViewHelper
{
    /**
     * 
     * @param int|null $offset
     * @return int
     */
    public function counter($offset = null)
    {
        if (null !== $offset) {
            return RowCounter::set($offset);
        }
        
        return RowCounter::get();
    }
    
}
