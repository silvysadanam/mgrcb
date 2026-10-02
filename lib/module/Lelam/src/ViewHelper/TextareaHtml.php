<?php
/**
 * TextareaHtml View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

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
