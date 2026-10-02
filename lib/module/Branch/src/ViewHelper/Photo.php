<?php
/**
 * Photo View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

use Web\View\ViewHelper;


class Photo extends ViewHelper
{
    const MAX_LENGTH = 30;
    
    /**
     * get photo image
     * 
     * @param \stdClass $record
     * @param string $base
     * @return string
     */
    public function photo($record, $base = 'documents')
    {
        $url =  $this->getRegistry('baseUrl') . 'assets/' . $base . '/'
             . $record->file_path . '/' . $record->filename;
        return '<img src="' . $url . '" alt="Photo">';
    }
    
}
