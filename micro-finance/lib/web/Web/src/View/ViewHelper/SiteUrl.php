<?php
/**
 * SiteUrl View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;


class SiteUrl extends ViewHelper
{
    /**
     * get site url
     * 
     * @param string $url target URL
     * @return string
     */
    public function siteUrl($url = null)
    {
        $siteUrl = $this->getRegistry('siteUrl');
        if (null !== $url) {
            if ('/' != $url) {
                $siteUrl .= ltrim($url, DS);
            }
        }
        
        return $siteUrl;
    }
    
}
