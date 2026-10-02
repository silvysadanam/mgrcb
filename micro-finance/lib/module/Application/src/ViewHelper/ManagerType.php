<?php
/**
 * ManagerType View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;
use Application\Utility\LoanHelper;


class ManagerType extends ViewHelper
{
   /**
    * get manager type
    * 
    * @param int $id
    * @return string
    */
    public function managerType($id)
    {
        return LoanHelper::managerType($id);
    }
    
}
