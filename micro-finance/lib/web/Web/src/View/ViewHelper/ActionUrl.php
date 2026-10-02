<?php
/**
 * ActionUrl View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;


class ActionUrl extends ViewHelper
{
    /**
     * get action url
     * 
     * @param string $url target URL
     * @return string
     */
    public function actionUrl($url = null)
    {
        $actionUrl = $this->getRegistry('actionUrl');
        if (null !== $url) {
            if ('/' != $url) {
                $route = $this->getRegistry('route');
                if ('' != $route['module']) {
                    $actionUrl .= $route['module'] . DS;
                }
                $actionUrl .= ltrim($url, DS);
            }
        }
        
        return $actionUrl;
    }
    
}
