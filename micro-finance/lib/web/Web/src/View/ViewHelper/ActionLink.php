<?php
/**
 * ActionLink View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\ActionLinkHelper;


class ActionLink extends ViewHelper
{
    /**
     * get action link
     * 
     * @param string $type
     * @param int $id
     * @return string
     */
    public function actionLink($type = 'cancel', $id = null)
    {
        if (null !== $id) {
            return ActionLinkHelper::getActionLink($type, $id);
        }
        
        return ActionLinkHelper::getCancelLink();
    }
    
}
