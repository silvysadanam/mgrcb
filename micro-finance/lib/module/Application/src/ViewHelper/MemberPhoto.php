<?php
/**
 * Photo View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class MemberPhoto extends ViewHelper
{
    const MAX_LENGTH = 30;
    
    /**
     * get photo image
     * 
     * @param \stdClass $record
     * @param string $base
     * @return string
     */
    public function memberPhoto($record, $base = 'documents')
    {
        $url = $this->getRegistry('member_assets') . $base . '/'
             . $record->file_path . '/' . $record->filename;
        
        return '<img src="' . $url . '" alt="Photo">';
    }
    
}
