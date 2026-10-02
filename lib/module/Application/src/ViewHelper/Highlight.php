<?php
/**
 * Highlight View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\SearchHighlight;


class Highlight extends ViewHelper
{
    /**
     * hihlight text
     * 
     * @param string $name
     * @param string $string
     * @return string
     */
    public function highlight($name, $string)
    {
        return SearchHighlight::get($name, $string);
    }
    
}
