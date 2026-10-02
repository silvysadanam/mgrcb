<?php
/**
 * Paragraph View helper
 * 
 * @category   Backend
 * @package    Backend\ViewHelper
 */

namespace Backend\ViewHelper;

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
