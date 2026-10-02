<?php
/**
 * Document Upload Directory handler
 *
 * @category   Shared
 * @package    Shared\Utility
 */

namespace Shared\Utility;

use Web\Exception;
use DateTime;


class DocumentDirectory
{
    const DATE_FORMAT = 'Y';
    
    /**
     *
     * @var string 
     */
    private $_base;
    
    /**
     *
     * @var string 
     */
    private $_uploadPath;
    
    public function __construct($uploadPath = null)
    {
        if (null !== $uploadPath) {
            $this->_uploadPath = $uploadPath;
        } elseif (defined('UPLOAD_DIR')) {
            $this->_uploadPath = UPLOAD_DIR;
        }
        $this->_base = 'documents';
    }
    
    /**
     * set base directory
     * 
     * @param string $base
     */
    public function setBase($base)
    {
        $this->_base = $base;
    }
    
    /**
     * create directory
     * 
     * @param int $id
     * @return string
     * @throws Exception
     */
    public function run($id = null)
    {
        $this->_checkSettings();
        $date = new DateTime();
        $format = $date->format(self::DATE_FORMAT);
        $this->_createDirectory($format);
        if (null !== $id) {
            $format .= '/' . $id;
            $this->_createDirectory($format);
        }
        
        return $format;
    }
    
    /**
     * check old paths
     * 
     * @param int $year
     * @param int $id
     * @return string
     */
    public function check($year, $id = null)
    {
        $this->_checkSettings();
        $date = new DateTime();
        $date->setDate($year, 2, 1);
        $format = $date->format(self::DATE_FORMAT);
        $this->_createDirectory($format);
        if (null !== $id) {
            $format .= '/' . $id;
            $this->_createDirectory($format);
        }
        
        return $format;
    }
    
    /**
     * create directory
     * 
     * @param string $format
     */
    private function _createDirectory($format)
    {
        $folderPath = $this->_uploadPath . DS . $this->_base . DS . $format;
        $this->_makeDirectory($folderPath);
    }

    /**
     * check directory settings
     * 
     * @throws Exception
     */
    private function _checkSettings()
    {
        if (empty($this->_uploadPath)) {
            throw new Exception('Upload directory not set');
        }
        if (! is_dir($this->_uploadPath)) {
            throw new Exception('Upload directory not found');
        }
        $base = $this->_uploadPath . DS . $this->_base;
        if (! is_dir($base)) {
            throw new Exception('Upload base directory "' . $base . '" not found');
        }
        if (! is_writable($base)) {
            throw new Exception('Upload base directory "' . $base . '" not writable');
        }
    }
    
    /**
     * make directory if not exists
     * @param string $directory
     */
    private function _makeDirectory($directory)
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0775);
        }
    }
    
}
