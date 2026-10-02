<?php
/**
 * Form Document
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Form\FileUpload;


class Document extends FileUpload
{    
    public function __construct($field = null, $title = null)
    {
        parent::__construct($field, $title);
    }
    
    /**
     * validate upload
     * 
     * @param array $options
     * @return boolean
     */
    public function isValid($options = [])
    {
        $options['document'] = true;
        
        return $this->validate($options);
    }

}
