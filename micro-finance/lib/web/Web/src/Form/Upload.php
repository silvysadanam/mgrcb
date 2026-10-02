<?php
/**
 * Upload handler
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Core\Server;
use Web\Exception;


class Upload
{
    const IMAGE = 'image';
    const CSV = 'csv';
    const JPG = 'jpg';
    const GIF = 'gif';
    const PNG = 'png';
    
    /**
     *
     * @var string 
     */
    protected $_field;
    
    /**
     *
     * @var string 
     */
    protected $_title;
    
    /**
     *
     * @var string 
     */
    protected $_uploadPath;
    
    /**
     *
     * @var array 
     */
    private $_validations;
    
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
     * @var boolean 
     */
    private $_post;
    
    /**
     *
     * @var array 
     */
    private $_extensions = [];
    
    /**
     *
     * @var array 
     */
    private $_imageValidations;
    
    /**
     *
     * @var string 
     */
    private $_uploadedFile = '';
    
    /**
     * constructor method
     * 
     * @param string $field
     * @param string $title
     */
    public function __construct($field = null, $title = null)
    {
        $this->_post = 'POST' == Server::requestMethod();
        $this->_field = $field;
        $this->_title = $title;
        $this->_validations = array (
            'required',
            'width',
            'height',
            'maxWidth',
            'maxHeight',
            'minWidth',
            'minHeight',
            'size',
            'type',
            'extension',
        );
        $this->_imageValidations = array (
            'width',
            'height',
            'maxWidth',
            'maxHeight',
            'minWidth',
            'minHeight',
        );
        
        $this->_uploadPath = APP_PATH;
        $this->_extensions = [
            self::IMAGE => ['jpg', 'png', 'gif'],
            self::JPG => ['jpg'],
            self::PNG => ['png'],
            self::GIF => ['gif'],
            self::CSV => ['csv'],
        ];
    }
    
    /**
     * set file field name
     * 
     * @param string $field
     */
    public function setField($field)
    {
        $this->_field = $field;
    }
    
    /**
     * set field tittle
     * 
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }
    
    /**
     * check if post method
     * 
     * @return boolean 
     */
    public function isPost()
    {
        return $this->_post;
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
     * validate file upload
     * 
     * @param array $options
     * @return boolean
     * @throws Exception
     */
    public function isValid($options = [])
    {
        $valid = false; 
        if (empty($this->_field)) {
            throw new Exception('File form field name is not defined');
        }
        if ($this->_post) {            
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
        } else {
            throw new Exception('Called in GET method');
        }
        
        return $valid;
    }
    
    /**
     * check if file selected
     * 
     * @return boolean 
     */
    public function hasFile()
    {
        return $this->_upload;
    }
    
    /**
     * get form validation error
     * 
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }
    
    /**
     * upload file to destination 
     * 
     * @param string $path
     * @param string $filename
     * @param string $prefix
     * @return boolean is uploaded 
     */
    public function upload($path = '', $filename = '', $prefix = '')
    {
        $uploaded = false;
        if ($this->_upload) {
            if ('' != $path) {
                $path = rtrim($path, DS);
                if (! is_dir($path)) {
                    $path = $this->_uploadPath . DS . trim($path, DS);
                }
            } else {
                $path = $this->_uploadPath;
            }
            if (is_dir($path)) {
                if ('' == $filename) {
                    $filename = $this->_file['name'];
                }
                $filename = $this->_getCleanFilename($filename, $prefix);
                
                if (move_uploaded_file($this->_file['tmp_name'], $path . DS . $filename)) {
                    $this->_uploadedFile = $path . DS . $filename;
                    $uploaded = $filename;
                    $this->_filename = $filename;
                    $this->_savedFile = substr($path, strlen($this->_uploadPath)) . DS . $filename;
                }
            }
        }
        
        return $uploaded;
    }
    
    /**
     * get original filename 
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
     * get filename part of uploaded file
     * @return string
     */
    public function getUploadedFilename()
    {
        return $this->_filename;
    }
    
    /**
     * get relative path of uploade filename
     * 
     * @return string
     */
    public function getSavedFile()
    {
        return $this->_savedFile;
    }
    
    /**
     * get full path of uploaded file
     * 
     * @return string
     */
    public function getUploadedFile()
    {
        return $this->_uploadedFile;
    }
    
    /**
     * get temporary upload file
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
     * add error
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
     * validate file upload
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
                $extension = $this->_getExtension($this->_file['name']);
                if (false !== $extension) {
                    $type = null;
                    if (isset($options['type'])) {
                        $type = $options['type'];
                    } elseif (isset($options['extension'])) {
                        $ext = strtolower(trim($options['extension'], '.'));
                        foreach ($this->_extensions as $key => $item) {
                            if (in_array($ext, $item)) {
                                $type = $key;
                                break;
                            }
                        }
                    } else {
                        $type = self::JPG;
                    }
                    if (null !== $type) {
                        if (in_array($extension, $this->_extensions[$type])) {
                            switch ($type) {
                                case self::IMAGE:
                                case self::JPG:
                                case self::PNG:
                                case self::GIF:
                                    $imageOptions = [];
                                    if (! empty($options)) {
                                        foreach ($options as $option => $value) {
                                            if (in_array($option, $this->_imageValidations)) {
                                                $imageOptions[$option] = $value;
                                            }
                                        }
                                    }
                                    
                                    $imageInfo = getimagesize($this->_file['tmp_name']);                       
                                    if (isset($imageInfo[2])) {
                                        if (self::JPG == $type) {
                                            $valid = IMAGETYPE_JPEG == $imageInfo[2];
                                        } elseif (self::PNG == $type) {
                                             $valid = IMAGETYPE_PNG == $imageInfo[2];
                                        } elseif (self::GIF == $type) {
                                             $valid = IMAGETYPE_GIF == $imageInfo[2];
                                        } else {
                                            $valid = in_array($imageInfo[2], IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
                                        }
                                        if ($valid && !empty($imageOptions)) {
                                            $valid = $this->_validateImageSize($imageInfo[0], $imageInfo[1], $imageOptions);
                                        }
                                    } else {
                                        $this->_addError('Invalid image file');
                                    }
                                    break;
                                    
                                case self::CSV:
                                    $valid = true;
                                    break;
                                
                                default:
                                    $this->_addError('Invalid file type');
                                    break;
                            }
                        } else {
                            $this->_addError('Invalid file type');
                        }
                    } else {
                        $this->_addError('Invalid file type');
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
     * get extension from filename
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
     * generate file name for saving to file system
     * 
     * @param string $filename
     * @param string $prefix
     * @return string
     */
    private function _getCleanFilename($filename, $prefix = '', $separator = '_')
    {
        $filename = preg_replace('/[^a-z0-9_.-]+/i', '', $filename);
        $filename = preg_replace('/[.]+/', '.', $filename);
        $filename = preg_replace('/[_-]+/', '_', $filename);
        $filename = time() . '_' . rand(1, 10000) . '_' . $filename;
        if ('' != $prefix) {
            return $prefix . $separator . $filename;
        }
        
        return $filename;
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
