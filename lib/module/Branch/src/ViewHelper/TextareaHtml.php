<?php
/**
 * TextareaHtml View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

use Web\View\ViewHelper;


class TextareaHtml extends ViewHelper
{
    /**
     * format textarea content to HTML
     * 
     * @param string $string
     * @return string
     */
    public function textareaHtml($string)
    {
        return nl2br($this->escape($string));
    }
    
}
