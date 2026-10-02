<?php
/**
 * Reset Counter View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\RowCounter;


class ResetCounter extends ViewHelper
{
    /**
     * reset counter
     */
    public function resetCounter()
    {
        RowCounter::reset();
    }
    
}
