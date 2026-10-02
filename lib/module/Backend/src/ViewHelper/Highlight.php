<?php
/**
 * Highlight View helper
 * 
 * @category   Backend
 * @package    Backend\ViewHelper
 */

namespace Backend\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\SearchHighlight;


class Highlight extends ViewHelper
{
    /**
     * highlight text
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
