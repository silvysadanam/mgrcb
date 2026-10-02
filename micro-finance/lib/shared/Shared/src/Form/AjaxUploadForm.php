<?php
/**
 * Shared AjaxUpload Form
 * 
 * @category   Shared
 * @package    Shared\Form
 */

namespace Shared\Form;

use Web\Form\Form;

use Shared\Form\DocumentForm;
use Shared\Utility\DocumentDirectory;
use Shared\Utility\FileLog;


class AjaxUploadForm extends Form
{
    /**
     *
     * @var \Application\Form\DocumentForm 
     */
    private $_document;
    
    /**
     *
     * @var string 
     */
    private $_uploadBasePath = 'documents';
    
    public function __construct()
    {
        parent::__construct();
        $this->addField('title');
        
    }
    
    /**
     * set upload base path
     * 
     * @param string $base
     */
    public function setBase($base)
    {
        $this->_uploadBasePath = $base;
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $title = $this->post('title');
        if ('' == $title) {
            $title = 'Document';
        }

        $this->_document = new DocumentForm('document', $title);
        if (! $this->_document->isValid(['required' => true])) {
            $this->error->add($this->_document->getError());
        }
        
        return $this->noError();
    }
    
    /**
     * upload file
     * 
     * @param int $userId
     * @param boolean $admin
     * @return array
     */
    public function upload($userId, $admin = false)
    {
        $documentDirectory = new DocumentDirectory();
        $documentDirectory->setBase($this->_uploadBasePath);
        $documentPath = $documentDirectory->run();
        $uploadPath = $this->_uploadBasePath . '/' . $documentPath;

        $file = [];
        if ($this->_document->hasFile()) {
            $this->_document->setUploadPath(UPLOAD_DIR);
            if ($this->_document->upload($uploadPath)) {
                $file = [
                    'filename' => $this->_document->getUploadedFilename(),
                    'original_filename' => $this->_document->getRawFilename(),
                    'file_size' => $this->_document->getUploadedFileSize(),
                    'file_path' => $documentPath,
                ];
                
                $fileLog = new FileLog($userId, $admin);
                $file['register_id'] = $fileLog->register($file['filename'], $file['file_path']);
            }
        }
        
        return $file;
    }

}
