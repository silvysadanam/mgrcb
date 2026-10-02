<?php
/**
 * Upload Directory handler
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Exception;
use DateTime;


class UploadDirectory
{
    const WINNING_NEWS = 1;
    const TENDER = 2;
    const DATE_FORMAT = 'Y/Ym';
    
    private $_types;
    private $_uploadPath;
    
    public function __construct($uploadPath = null)
    {
        $this->_types = [
            self::WINNING_NEWS => [
                'base' => 'winning-news',
                'folders' => [
                    'original',
                    'slider',
                    'medium',
                    'thumbnail',
                ],
            ],
            self::TENDER => [
                'base' => 'tenders',
                'folders' => false,
            ],
        ];
        if (null !== $uploadPath) {
            $this->_uploadPath = $uploadPath;
        } elseif (defined('UPLOAD_DIR')) {
            $this->_uploadPath = UPLOAD_DIR;
        }
    }
    
    /**
     * create directory
     * 
     * @param int $type
     * @return string
     * @throws Exception
     */
    public function run($type)
    {
        $this->_checkSettings($type);
        $date = new DateTime();
        $format = $date->format(self::DATE_FORMAT);
        $this->_createDateDirectories($type, $format);
        return $format;
    }
    
    /**
     * check old paths
     * 
     * @param int $type
     * @param int $year
     * @param int $month
     * @return string
     */
    public function check($type, $year, $month = 0)
    {
        $this->_checkSettings($type);
        $date = new DateTime();
        $currentYear = $date->format('Y');
        if ($month < 1 || $month > 12) {
            if ($year == $currentYear) {
                $month = $date->format('n');
            } else {
                $month = 1;
            }
        }
        $date->setDate($year, $month, 1);
        $format = $date->format(self::DATE_FORMAT);
        $this->_createDateDirectories($type, $format);
        return $format;
    }
    
    /**
     * create date directories
     * 
     * @param int $type
     * @param string $format
     */
    private function _createDateDirectories($type, $format)
    {
        $directories = explode('/', $format);
        $settings = $this->_types[$type];
        $base = $this->_uploadPath . DS . $settings['base'];
        if (! empty($settings['folders'])) {
            foreach ($settings['folders'] as $folder) {
                $folderPath = $base . DS . $folder;
                $this->_makeDirectory($folderPath);
                foreach ($directories as $subPath) {
                    $folderPath .= DS . $subPath;
                    $this->_makeDirectory($folderPath);
                }
            }
        } else {
            $folderPath = $base;
            foreach ($directories as $subPath) {
                $folderPath .= DS . $subPath;
                $this->_makeDirectory($folderPath);
            }
        }
    }
    
    /**
     * check directory settings
     * 
     * @param int $type
     * @throws Exception
     */
    private function _checkSettings($type)
    {
        if (empty($this->_uploadPath)) {
            throw new Exception('Upload directory not set');
        }
        if (! is_dir($this->_uploadPath)) {
            throw new Exception('Upload directory not found');
        }
        if (! isset($this->_types[$type])) {
            throw new Exception('Undefined directory type');
        }
        $settings = $this->_types[$type];
        $base = $this->_uploadPath . DS . $settings['base'];
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
