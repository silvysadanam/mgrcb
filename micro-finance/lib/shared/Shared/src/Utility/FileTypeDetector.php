<?php
/**
 * Shared FileType Detector Utility
 *
 * @category   Shared
 * @package    Shared\Utility
 */

namespace Shared\Utility;

use Shared\Constant\FileType;


class FileTypeDetector
{
    /**
     *
     * @var array 
     */
    private $_fileTypes;
    
    public function __construct()
    {
        $this->_fileTypes = [
            'jpg' => FileType::JPG,
            'pdf' => FileType::PDF,
            'doc' => FileType::DOC,
            'docx' => FileType::DOCX,
        ];
    }
    
    /**
     * get file type from filename
     * 
     * @param string $filename
     * @return int
     */
    public function getFromFilename($filename)
    {
        $extensiton = $this->_getExtension($filename);
        if ('' != $extensiton) {
            if (isset($this->_fileTypes[$extensiton])) {
                return $this->_fileTypes[$extensiton];
            }
        }
        
        return 0;
    }
    
    /**
     * get extension
     * 
     * @param string $filename
     * @return string
     */
    private function _getExtension($filename)
    {
        if (false === strpos($filename, '.')) {
            return '';
        }
        
        $parts = explode('.', trim($filename, '.'));
        $last = end($parts);
        
        return strtolower($last);
    }
    
}
