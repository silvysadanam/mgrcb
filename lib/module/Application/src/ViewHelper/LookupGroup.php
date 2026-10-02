<?php
/**
 * LookupGroup helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;
use Web\View\Support\LookupStore;


class LookupGroup extends ViewHelper
{       
    /**
     * generate lookup group
     * 
     * @param string $key
     * @param array $ids
     * @param string $glue
     * @return string
     */
    public function lookupGroup($key, $ids, $glue = ', ')
    {
        $list = [];
        if (! empty($ids)) {
            foreach ($ids as $id) {
                $list[] = $this->escape(LookupStore::find($key, $id));
            }
        }
        
        return implode($glue, $list);
    }
    
}
