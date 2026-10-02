<?php
/**
 * Form Image
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Form\FileUpload;


class Image extends FileUpload
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
        $options['image'] = true;
        
        return $this->validate($options);
    }

}
