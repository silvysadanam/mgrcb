<?php
/**
 * Paragraph View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

use Web\View\ViewHelper;


class Paragraph extends ViewHelper
{
    /**
     * convert text to paragraph
     * 
     * @param string $string
     * @return string
     */
    public function paragraph($string)
    {
        return nl2br($this->escape($string));
    }
    
}
