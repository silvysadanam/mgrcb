<?php
/**
 * MediaUrl View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;


class MediaUrl extends ViewHelper
{
    /**
     * get media URL
     * 
     * @param string $resource
     * @return string
     */
    public function mediaUrl($resource = null)
    {
        $mediaUrl = $this->getRegistry('mediaUrl');
        if (null !== $resource) {
            $mediaUrl .= ltrim($resource, DS);
        }
        
        return $mediaUrl;
    }
    
}
