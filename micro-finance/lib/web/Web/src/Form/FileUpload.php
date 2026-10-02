<?php
/**
 * Form File Upload
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Core\Server;
use Web\Exception;


class FileUpload
{
    /**
     *
     * @var string 
     */
    private $_field;
    
    /**
     *
     * @var string 
     */
    private $_title;
    
    /**
     *
     * @var array 
     */
    private $_validations = [];
    
    /**
     *
     * @var string 
     */
    private $_error = '';
    
    /**
     *
     * @var array 
     */
    private $_file;
    
    /**
     *
     * @var boolean 
     */
    private $_upload = false;
    
    /**
     *
     * @var string 
     */
    private $_filename = '';
    
    /**
     *
     * @var string 
     */
    private $_savedFile = '';
    
    /**
     *
     * @var string 
     */
    private $_uploadPath;
    
    /**
     *
     * @var boolean 
     */
    private $_checkImageSize = false;
    
    /**
     *
     * @var array 
     */
    private $_allowedExtensions;
    
    /**
     *
     * @var string 
     */
    private $_fileExtension;
    
    public function __construct($field = null, $title = null)
    {
        $this->_field = $field;
        $this->_title = $title;
        $this->_validations = [
            'required',
            'width',
            'height',
            'maxWidth',
            'maxHeight',
            'minWidth',
            'minHeight',
            'size',
            'image',
            'video',
            'image_pdf',
        ];
        $this->_allowedExtensions = [
            'image' => ['jpg', 'png'],
            'video' => ['mpg', 'mp4', '3gp', 'avi', 'mov'],
            'csv' => ['csv'],
            'pdf' => ['pdf'],
            'doc' => ['doc', 'docx'],
            'excel' => ['.xls', '.xlsx'],
            'document' => ['pdf', 'doc', 'docx', 'zip'],
            'image_pdf' => ['jpg', 'png', 'pdf'],
            'custom1' => ['jpg', 'pdf'],
        ];
    }
    
    /**
     * set image size checking option
     * 
     * @param boolean $enable
     */
    public function setImageSizeChecking($enable = true)
    {
        $this->_checkImageSize = $enable;
    }
    
    /**
     * check if POST
     * 
     * @return boolean
     */
    public function isPost()
    {
        return 'POST' == Server::requestMethod();
    }
    
    /**
     * set upload path
     * 
     * @param string $path
     */
    public function setUploadPath($path)
    {
        $this->_uploadPath = rtrim($path, '/');
    }
    
    /**
     * validate upload
     * 
     * @param array $options
     * @return boolean
     * @throws Exception
     */
    public function validate($options = [])
    {
        $valid = false; 
        if (empty($this->_field)) {
            throw new Exception('File form field name is not defined');
        }
        if ($this->isPost()) {            
            if (! empty($_FILES[$this->_field])) {
                $this->_file = $_FILES[$this->_field];
                switch ($this->_file['error']) {
                    case UPLOAD_ERR_OK:
                        $valid = $this->_validate($options);
                        break;
                    
                    case UPLOAD_ERR_NO_FILE:
                        if (isset($options['required'])) {
                            $this->_addError('No file uploaded');
                        } else {
                            $valid = true;
                        }
                        break;
                    
                    case UPLOAD_ERR_FORM_SIZE:
                    case UPLOAD_ERR_INI_SIZE:
                        $this->_addError('File upload settings error');
                        break;
                    
                    default:
                        $this->_addError('File upload failed');
                        break;
                }
            }
        }
        
        return $valid;
    }
    
    /**
     * check file uploaded
     * 
     * @return boolean
     */
    public function hasFile()
    {
        return $this->_upload;
    }
    
    /**
     * get validation error
     * 
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }
    
    /**
     * upload file to server
     * 
     * @param string $path
     * @param string $filename
     * @param string $prefix
     * @return boolean|string
     */
    public function upload($path, $filename = '', $prefix = '')
    {
        $uploaded = false;
        if ($this->_upload) {
            $uploadDirectory = $this->_uploadPath . '/' . trim($path, '/');
            if (is_dir($uploadDirectory)) {
                if ('' == $filename) {
                    $filename = $this->_file['name'];
                }
                $filename = $this->_getCleanFilename($filename, $prefix);
                $savedFilename =  $uploadDirectory . '/' . $filename;
                if (move_uploaded_file($this->_file['tmp_name'], $savedFilename)) {
                    $uploaded = $filename;
                    $this->_filename = $filename;
                    $this->_savedFile = $savedFilename;
                }
            }
        }
        
        return $uploaded;
    }
    
    /**
     * get raw (original) filename
     * 
     * @return string
     */
    public function getRawFilename()
    {
        $filename = '';
        if (isset($this->_file['name'])) {
            $filename = $this->_file['name'];
        }
        
        return $filename;
    }
    
    /**
     * get uploaded (renamed) filename
     * 
     * @return string
     */
    public function getUploadedFilename()
    {
        return $this->_filename;
    }
    
    /**
     * get uploaded file saved path
     * 
     * @return string
     */
    public function getUploadedFile()
    {
        return $this->_savedFile;
    }
    
    /**
     * get uploaded file size
     * 
     * @return int
     */
    public function getUploadedFileSize()
    {
        return $this->_file['size'];
    }
    
    /**
     * get file tmp name
     * 
     * @return string
     */
    public function getTempFile()
    {
        $file = false;
        if (isset($this->_file['tmp_name'])) {
            if (is_uploaded_file($this->_file['tmp_name'])) {
                $file = $this->_file['tmp_name'];
            }
        }
        
        return $file;
    }
    
    /**
     * get file extension
     * 
     * @return string
     */
    public function getUploadedFileExtension()
    {
        return $this->_fileExtension;
    }


    /**
     * add error message
     * 
     * @param string $message
     */
    protected function _addError($message)
    {
        $this->_error = $message;
        if (! empty($this->_title)) {
            $this->_error .= ' for ' . $this->_title;
        }
    }
    
    
    /**
     * validate for extra options
     * 
     * @param array $options
     * @return boolean
     */
    private function _validate($options)
    {
        $valid = false;
        if (is_uploaded_file($this->_file['tmp_name'])) {
            if (isset($options['size'])) {
                if (filesize($this->_file['tmp_name']) > $options['size']) {
                    $this->_addError('File size exceeded');
                }
            }
            if (empty($this->_error)) {
                $fileExtension = $this->_getExtension($this->_file['name']);
                if (false !== $fileExtension) {
                    $extension = strtolower($fileExtension);
                    $this->_fileExtension = $extension;
                    if (isset($options['image'])) {
                        if (in_array($extension, $this->_allowedExtensions['image'])) {
                            if ($this->_checkImageSize) {
                                // implement image validation options
                                $imageInfo = getimagesize($this->_file['tmp_name']);                       
                                if (isset($imageInfo[2])) {
                                    if (IMAGETYPE_JPEG == $imageInfo[2]) {
                                        $valid = true;
                                        if (! empty($options)) {
                                            $valid = $this->_validateImageSize($imageInfo[0], $imageInfo[1], $options);
                                        }
                                    }
                                } else {
                                    $this->_addError('Invalid image file');
                                }
                            } else {
                                $valid = true;
                            }
                        } else {
                            $this->_addError('Invalid file type');
                        }
                    } elseif (isset($options['video'])) {
                        if (in_array($extension, $this->_allowedExtensions['video'])) {
                            $valid = true;
                        } else {
                            $this->_addError('Invalid file type');
                        }
                    } elseif (isset($options['image_pdf'])) {
                        if (in_array($extension, $this->_allowedExtensions['image_pdf'])) {
                            $valid = true;
                        } else {
                            $this->_addError('Invalid file type');
                        }
                    } else {
                        foreach ($this->_allowedExtensions as $type => $extensions) {
                            if (isset($options[$type])) {
                                if (in_array($extension, $extensions)) {
                                    $valid = true;
                                } else {
                                    $this->_addError('Invalid file type');
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }
        if ($valid) {
            $this->_upload = true;
        }
        
        return $valid;
    }
    
    /**
     * get extension
     * 
     * @param string $filename
     * @return string
     */
    private function _getExtension($filename)
    {
        $extension = false;
        $dotPosition = strrpos($filename, '.');
        if (false !== $dotPosition) {
            if ($dotPosition > 0) {
                if (! preg_match('/\.(php|pl|cgi)\./i', $filename)) {
                    $parts = explode('.', $filename);
                    if (count($parts) > 1) {
                        $last = end($parts);
                        $extension = strtolower($last);
                    }
                }
            }
        }
        
        return $extension;
    }
    
    /**
     * get clean filename
     * 
     * @param string $filename
     * @param string $prefix
     * @return string
     */
    private function _getCleanFilename($filename, $prefix = '')
    {
        $filename = preg_replace('/[^a-z0-9_.-]+/i', '', $filename);
        $filename = preg_replace('/[.]+/', '.', $filename);
        $filename = trim(preg_replace('/[_-]+/', '_', $filename), '_');
        if (strlen($filename) > 50) {
            $filename = substr($filename, -50);
        }
        if (0 === strpos($filename, '.')) {
            $filename = time() . '_' . rand(1, 10000) . $filename;
        }
        if ('' != $prefix) {
            $filename = $prefix . '_' . $filename;
        } else {
            $filename = time() . '_' . rand(1, 10000) . '_' . $filename;
        }
        
        return strtolower($filename);
    }
    
    /**
     * validate image size
     * 
     * @param int $width
     * @param int $height
     * @param array $options
     * @return boolean
     */
    private function _validateImageSize($width, $height, $options)
    {
        $valid = true;
        $excludeOptions = array ('size', 'required');
        if (! empty($options)) {
            foreach ($excludeOptions as $item) {
                if (isset($options[$item])) {
                    unset($options[$item]);
                }
            }
        }
        if (! empty($options)) {
            foreach ($options as $validation => $value) {
                switch ($validation) {
                    case 'width':
                    case 'maxWidth':
                    case 'minWidth':
                        if ('maxWidth' == $validation) {
                            $valid = $width <= $value;
                        } elseif ('minWidth' == $validation) {
                            $valid = $width >= $value;
                        } else {
                            $valid = $width == $value;
                        }
                        if (! $valid) {
                            $this->_addError('Invalid width');
                        }
                        break;

                    case 'height':
                    case 'maxHeight':
                    case 'minHeight':
                        if ('maxHeight' == $validation) {
                            $valid = $height <= $value;
                        } elseif ('minHeight' == $validation) {
                            $valid = $height >= $value;
                        } else {
                            $valid = $height == $value;
                        }
                        if (! $valid) {
                            $this->_addError('Invalid height');
                        }
                        break;
                        
                     default:
                        break;
                }
                if (! $valid) {
                    break;
                }
            }
        }
        
        return $valid;
    }
    
}
