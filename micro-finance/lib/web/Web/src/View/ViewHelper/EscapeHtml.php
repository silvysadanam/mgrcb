<?php
/**
 * EscapeHtml View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;


class EscapeHtml extends ViewHelper
{
    /**
     * escape HTML
     * 
     * @param string $string
     * @param boolean $doubleEncode
     * @return string
     */
    public function escapeHtml($string, $doubleEncode = false)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', $doubleEncode);
    }
    
}
