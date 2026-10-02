<?php
/**
 * Shared Form Document
 *
 * @category   Shared
 * @package    Shared\Form
 */

namespace Shared\Form;

use Web\Form\FileUpload;


class DocumentForm extends FileUpload
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
        $options['pdf'] = true;
        $options['size'] = 10485760;
        
        return $this->validate($options);
    }

}
