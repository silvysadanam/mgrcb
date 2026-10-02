<?php
/**
 * AssetUrl View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;


class AssetUrl extends ViewHelper
{
    /**
     * get asset URL
     * 
     * @param string $resource
     * @return string
     */
    public function assetUrl($resource = null)
    {
        $assetUrl = $this->getRegistry('uploadUrl');
        if (null !== $resource) {
            $assetUrl .= ltrim($resource, DS);
        }
        
        return $assetUrl;
    }
    
}
