<?php
/**
 * BaseUrl View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;


class BaseUrl extends ViewHelper
{
    /**
     * get base url
     * 
     * @param string $url
     * @return string
     */
    public function baseUrl($url = null)
    {
        $baseUrl = $this->getRegistry('baseUrl');
        if (null !== $url) {
            $baseUrl .= ltrim($url, DS);
        }
        
        return $baseUrl;
    }
    
}
