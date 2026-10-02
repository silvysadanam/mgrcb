<?php
/**
 * Backend Form Photo
 *
 * @category   Backend
 * @package    Backend\Form
 */

namespace Backend\Form;

use Web\Form\FileUpload;


class PhotoForm extends FileUpload
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
        $options['custom2'] = true;
        $options['size'] = 10485760;
        
        return $this->validate($options);
    }

}
