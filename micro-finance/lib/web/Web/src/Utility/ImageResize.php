<?php
/**
 * Web Image Resize Utility
 * 
 * @category Web
 * @package Utility
 */

namespace Web\Utility;

use Web\Exception;


class ImageResize
{
    const EXTENSION_GD = 'gd';
    const PARAM_PATH = 'path';
    const PARAM_NAME = 'name';
    const PARAM_WIDTH = 'width';
    const PARAM_HEIGHT = 'height';
    const PARAM_TOP = 'top';
    const PARAM_LEFT = 'left';
    const PARAM_BOTTOM = 'bottom';
    const PARAM_RIGHT = 'right';
    const PARAM_CROP_WIDTH = 'cropWidth';
    const PARAM_CROP_HEIGHT = 'cropHeight';
    
    /**
     *
     * @var string 
     */
    private $_source;
    
    /**
     *
     * @var string 
     */
    private $_destination;
    
    /**
     *
     * @var string 
     */
    private $_basePath;
    
    /**
     *
     * @var array 
     */
    private $_resizeOptions = [];
    
    /**
     *
     * @var string 
     */
    private $_sourceFile;
    
    /**
     *
     * @var string 
     */
    private $_sourceFileName;
    
    /**
     *
     * @var array 
     */
    private $_allowedExtensions;
    
    /**
     *
     * @var string 
     */
    private $_extension;
    
    /**
     *
     * @var array 
     */
    private $_imageSize;
    
    /**
     *
     * @var array 
     */
    private $_writableDirectories = [];
    /**
     *
     * @var array 
     */
    private $_resizeParams;
    
    /**
     *
     * @var float 
     */
    private $_sizeRatio;
    
    /**
     *
     * @var resource 
     */
    private $_imageResource;
    
    /**
     *
     * @var bpplean 
     */
    private $_output = false;
    
    public function __construct($source = null)
    {
        $this->_source = $source;
        $this->_allowedExtensions  = ['jpg', 'gif', 'png'];
    }
    
    /**
     * set source file
     * 
     * @param string $source
     */
    public function setSource($source)
    {
        $this->_source = $source;
    }
    
    /**
     * set base path for relative path usage
     * 
     * @param string $path
     */
    public function setBasePath($path)
    {
        $this->_basePath = rtrim($path, DS);
    }
    
    /**
     * add resize option
     * 
     * @param string $path
     * @param array $size
     */
    public function addResize($path, $size)
    {
        $options = $size;
        $options[self::PARAM_PATH] = $path;
        $this->_resizeOptions[] = $options;
    }
    
    /**
     * set resize options
     * 
     * @param array $options
     */
    public function setResizeOptions($options)
    {
        $this->_resizeOptions = $options;
    }
    
    /**
     * move image file
     * 
     * @param string $target
     * @return boolean
     * @throws Exception
     */
    public function move($target = null)
    {
        $moved = false;
        $this->_loadSource();
        if (empty($target)) {
            $target = $this->_destination;
        }
        $destination = $this->_getDestination($target);
        $directory = dirname($destination);
        if ($this->_checkDirectory($directory)) {
            $moved = true === rename($this->_sourceFile, $destination);
        } else {
            throw new Exception('Directory "' . $directory . '" not found or not writable');
        }
        return $moved;
    }
    
    /**
     * copy image file
     * 
     * @param string $target
     * @return boolean
     * @throws Exception
     */
    public function copy($target = null)
    {
        $copied = false;
        $this->_loadSource();
        if (empty($target)) {
            $target = $this->_destination;
        }
        $destination = $this->_getDestination($target);
        $directory = dirname($destination);
        if ($this->_checkDirectory($directory)) {
            $copied = true === copy($this->_sourceFile, $destination);
        } else {
            throw new Exception('Directory "' . $directory . '" not found or not writable');
        }
        return $copied;
    }
    
    /**
     * set destination for copy/move
     * 
     * @param string $destination
     */
    public function setDestination($destination)
    {
        $this->_destination = $destination;
    }
    
    /**
     * save resized images
     * 
     * @param array $options optional
     * @return boolean
     */
    public function save($options = null)
    {
        if (! empty($options)) {
            $this->_resizeOptions = $options;
        }
        if (empty($this->_resizeOptions)) {
            return false;
        }
        $this->_checkResizeSupport();
        $this->_loadSource();
        switch ($this->_imageSize[2]) {
            case IMAGETYPE_GIF:
                $this->_imageResource = imagecreatefromgif($this->_sourceFile);
                break;
            
            case IMAGETYPE_JPEG:
                $this->_imageResource = imagecreatefromjpeg($this->_sourceFile);
                break;
            
            case IMAGETYPE_PNG:
                $this->_imageResource = imagecreatefrompng($this->_sourceFile);
                break;
            
            default:
                break;
        }
        if (is_resource($this->_imageResource)) {
            foreach ($this->_resizeOptions as $item) {
                $this->_resizeImage($item);
            }
            imagedestroy($this->_imageResource);
        }
    }
    
    /**
     * show image as script output
     */
    public function output()
    {
        $this->_loadSource();
        if (empty($this->_resizeOptions)) {
            $this->_outputSource();
        } else {
            $this->_output = true;
            $this->_resizeImage($this->_resizeOptions);
            if (true == $this->_resizeParams['copy']) {
                $this->_outputSource();
            } else {
                switch ($this->_imageSize[2]) {
                    case IMAGETYPE_GIF:
                        $this->_imageResource = imagecreatefromgif($this->_sourceFile);
                        break;

                    case IMAGETYPE_JPEG:
                        $this->_imageResource = imagecreatefromjpeg($this->_sourceFile);
                        break;

                    case IMAGETYPE_PNG:
                        $this->_imageResource = imagecreatefrompng($this->_sourceFile);
                        break;

                    default:
                        break;
                }
                if (is_resource($this->_imageResource)) {
                    $this->_resize();
                }
            }
        }
    }
    
    /**
     * save tmp file to application directory
     * 
     * @param string $tempFile tmp_name from $_FILES
     * @param string $target
     */
    public function upload($tempFile, $target = null)
    {
        $uploaded = false;
        if (empty($target)) {
            $target = $this->_destination;
        }
        if (0 === preg_match('#^/#', $target)) {
            $this->_sourceFile = $target;
        } elseif (! empty($this->_basePath)) {
            $this->_sourceFile = $this->_basePath . DS . $target;
        }
        $this->_source = $this->_sourceFile;
        $this->_sourceFileName = basename($this->_sourceFile);
        
        //$this->_sourceFileName
        $extension = strtolower($this->_getExtension());
        if (! in_array($extension, $this->_allowedExtensions)) {
            throw new Exception('Invalid image file extension');
        }
        $destination = $this->_getDestination($this->_sourceFile);
        if (! is_file($tempFile)) {
            throw new Exception('Non-existing temp file');
        } elseif (is_uploaded_file($tempFile)) {
            if (false !== ($image = getimagesize($tempFile))) {
                if (in_array($image[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                    $uploaded = move_uploaded_file($tempFile, $destination);
                } else {
                    throw new Exception('Unsupported image type');
                }
            } else {
                throw new Exception('Supplied file is not an image');
            }
        } else {
            throw new Exception('Not an uploaded file');
        }
        return $uploaded;
    }
    
    /**
     * reset options
     */
    public function reset()
    {
        $this->_source = null;
        $this->_destination = null;
        $this->_basePath = null;
        $this->_resizeOptions = [];
        $this->_sourceFile = null;
        $this->_sourceFileName = null;
        $this->_extension = null;
        $this->_imageSize = null;
        $this->_resizeParams = null;
        $this->_sizeRatio = null;
        $this->_imageResource = null;
        $this->_output = false;
    }
    
    
    /**
     * load source file
     * 
     * @throws Exception
     */
    private function _loadSource()
    {
        if (empty($this->_source)) {
            throw new Exception('Source not specified');
        }
        if (file_exists($this->_source)) {
            $this->_sourceFile = $this->_source;
        } else {
            if (! empty($this->_basePath)) {
                $file = $this->_basePath . DS . ltrim($this->_source, DS);
                if (! file_exists($file)) {
                    throw new Exception('Source not found');
                }
                $this->_sourceFile = $file;
            }
        }
        $this->_sourceFileName = basename($this->_sourceFile);
        $this->_extension = strtolower($this->_getExtension());
        if (! in_array($this->_extension, $this->_allowedExtensions)) {
            throw new Exception('Invalid image file extension');
        }
        if (false !== ($imagesize = getimagesize($this->_sourceFile))) {
            if ($imagesize[0] > 0) {
                $this->_imageSize = $imagesize;
                $this->_sizeRatio = round($imagesize[0] / $imagesize[1], 2);
            } else {
                throw new Exception('Unsupported image size');
            }
        } else {
            throw new Exception('Unsupported image type');
        }
    }
    
    /**
     * get file extension
     * 
     * @return string
     */
    private function _getExtension()
    {
        $filenameParts = explode('.', $this->_sourceFileName);
        return end($filenameParts);
    }
    
    /**
     * get destination
     * 
     * @param string $destination
     * @return string
     * @throws Exception
     */
    private function _getDestination($destination)
    {
        if (empty($destination)) {
            throw new Exception('Invalid destination');
        }
        if (0 == preg_match('/\.([jpgifn]+)$/i', $destination)) {
            $destination = rtrim($destination, DS);
            if (! is_dir($destination)) {
                if (! empty($this->_basePath)) {
                    $destination = $this->_basePath . DS . ltrim($destination, DS);
                }
            }
            $destination .= DS . $this->_sourceFileName;
        } else {
            $filename = basename($destination);
            $path = dirname($destination);
            if (! is_dir($path)) {
                if (! empty($this->_basePath)) {
                    $path = $this->_basePath . DS . trim($path, DS);
                    if (! is_dir($path)) {
                        throw new Exception('Directory "' . $path . '" not found');
                    }
                }
            }
            $destination = $path . DS . $filename;
        }
        return $destination;
    }
    
    /**
     * check directory status - exists / writable
     * 
     * @param string $path
     * @return boolean
     */
    private function _checkDirectory($path)
    {
        if ($this->_isFile($path)) {
            $directory = dirname($path);
            if (in_array($directory, $this->_writableDirectories)) {
                return true;
            }
            if (is_writable($directory)) {
                $this->_writableDirectories[] = $directory;
                return true;
            }
            return false;
        }
        if (is_dir($path)) {
            if (is_writable($path)) {
                $this->_writableDirectories[] = $path;
                return true;
            }
        }
        return false;
    }
    
    /**
     * check filename pattern
     * 
     * @param string $file
     * @return boolean
     */
    private function _isFile($file)
    {
        return 1 == preg_match('/\.([jpgifn]+)$/i', $file);
    }
    
    /**
     * ouput source directly
     */
    private function _outputSource()
    {
        $this->_sendMimeHeader();
        header('Content-Length: ' . filesize($this->_sourceFile));
        readfile($this->_sourceFile);
        exit;
    }
    
    /**
     * check GD support
     * 
     * @throws Exception
     */
    private function _checkResizeSupport()
    {
        if (! extension_loaded(self::EXTENSION_GD)) {
            throw new Exception('Extension "' . self::EXTENSION_GD . '" is required');
        }
    }
    
    /**
     * resize image with given options
     * 
     * @param array $options
     * @return boolean
     */
    private function _resizeImage($options)
    {
        $this->_resizeParams = [
            'directory' => '',
            'filename' => '',
            'width' => false,
            'height' => false,
            'crop' => false,
            'cropWidth' => 0,
            'cropHeight' => 0,
            'cropTop' => 0,
            'cropLeft' => 0,
            'copy' => false,
        ];
        $this->_loadResizeParams($options);
        if (! $this->_output) {
            if (empty($options[self::PARAM_PATH])) {
                return false;
            }
            $this->_loadResizePath($options[self::PARAM_PATH]);
            if (empty($this->_resizeParams['directory'])) {
                return false;
            }
            if (! $this->_checkDirectory($this->_resizeParams['directory'])) {
                return false;
            }
            if (true == $this->_resizeParams['copy']) {
                $this->copy($this->_resizeParams['directory'] . DS . $this->_resizeParams['filename']);
                return true;
            }
            $this->_resize();
        }
        return true;
    }
    
    /**
     * load resize path
     * 
     * @param string $path
     */
    private function _loadResizePath($path)
    {
        $directory = null;
        $this->_resizeParams['filename'] = $this->_sourceFileName;
        if ($this->_isFile($path)) {
            $directory = dirname($path);
            $this->_resizeParams['filename'] = basename($path);
            if (! $this->_checkDirectory($directory)) {
                if (! empty ($this->_basePath)) {
                    $directory = $this->_basePath . DS . trim($directory, DS);
                }
            }
        } else {
            if ($this->_checkDirectory($path)) {
                if (! empty ($this->_basePath)) {
                    $directory = $this->_basePath . DS . trim($path, DS);
                }
            } elseif (! empty ($this->_basePath)) {
                $directory = $this->_basePath . DS . trim($path, DS);
            }
        }
        $this->_resizeParams['directory'] = $directory;
    }
    
    /**
     * load resize params
     * 
     * @param array $options
     * @return boolean
     */
    private function _loadResizeParams($options)
    {
        if (isset($options[self::PARAM_WIDTH])) {
            if (false !== $options[self::PARAM_WIDTH]) {
                if ($options[self::PARAM_WIDTH] > 0 
                    && $options[self::PARAM_WIDTH] <= $this->_imageSize[0]) {
                    $this->_resizeParams[self::PARAM_WIDTH] = (int) $options[self::PARAM_WIDTH];
                }
            }
        }
        if (isset($options[self::PARAM_HEIGHT])) {
            if (false !== $options[self::PARAM_HEIGHT]) {
                if ($options[self::PARAM_HEIGHT] > 0 
                    && $options[self::PARAM_HEIGHT] <= $this->_imageSize[1]) {
                    $this->_resizeParams['height'] = (int) $options[self::PARAM_HEIGHT];
                }
            }
        }
        if (isset($options[self::PARAM_LEFT]) 
                || isset($options[self::PARAM_TOP]) || isset($options[self::PARAM_RIGHT])
                || isset($options[self::PARAM_BOTTOM]) || isset($options[self::PARAM_CROP_WIDTH])
                || isset($options[self::PARAM_CROP_HEIGHT])) {
            $this->_resizeParams['crop'] = true;
            $this->_loadCropParams($options);
        } else {
            if (false === $this->_resizeParams['width'] 
                && false === $this->_resizeParams['height']) {
                $this->_resizeParams['copy'] = true;
                return true;
            }
            if (false === $this->_resizeParams['height'] || false === $this->_resizeParams['width']) {
                $size = $this->_getProportionalSize($this->_resizeParams['width'], $this->_resizeParams['height']);
                $this->_resizeParams['width'] = $size['width'];
                $this->_resizeParams['height'] = $size['height'];
                $this->_resizeParams['cropLeft'] = $this->_getOffset($this->_imageSize[0], $size['width']);
                $this->_resizeParams['cropWidth'] = $size['width'];
                $this->_resizeParams['cropHeight'] = $size['height'];
                $this->_resizeParams['cropTop'] = $this->_getOffset($this->_imageSize[1], $size['height']);
                $this->_resizeParams['crop'] = true;
            } else {
                $this->_resizeParams['crop'] = true;
                $ratio = round($this->_resizeParams['width'] / $this->_resizeParams['height'], 2);
                if ($ratio >= $this->_sizeRatio) {
                    $this->_resizeParams['cropLeft'] = 0;
                    $this->_resizeParams['cropWidth'] = $this->_imageSize[0];
                    $this->_resizeParams['cropHeight'] = ceil($this->_resizeParams['cropWidth'] / $ratio);
                    $this->_resizeParams['cropTop'] = $this->_getOffset($this->_imageSize[1], $this->_resizeParams['cropHeight']);
                } else {
                    $this->_resizeParams['cropTop'] = 0;
                    $this->_resizeParams['cropHeight'] = $this->_imageSize[1];
                    $this->_resizeParams['cropWidth'] = ceil($this->_resizeParams['cropHeight'] * $ratio);
                    $this->_resizeParams['cropLeft'] = $this->_getOffset($this->_imageSize[1], $this->_resizeParams['cropHeight']);
                }
            }
        }
    }
    
    /**
     * get proprtional size for resizing
     * 
     * @param int $width
     * @param int $height
     * @return array
     */
    private function _getProportionalSize($width, $height)
    {
        $size = ['width' => $this->_imageSize[0], 'height' => $this->_imageSize[1]];
        if (false !== $width || false !== $height) {
            if (false !== $width) {
                $size['height'] = ceil($width / $this->_sizeRatio);
                $size['width'] = $width;
            } else {
                $size['width'] = ceil($height * $this->_sizeRatio);
                $size['height'] = $height;
            }
        }
        
        return $size;
    }
    
    /**
     * load crop params from options
     * 
     * @param array $options
     */
    private function _loadCropParams($options)
    {
        if (isset($options[self::PARAM_LEFT])) {
            $this->_resizeParams['cropLeft'] = (int) $options[self::PARAM_LEFT];
            if (isset($options[self::PARAM_CROP_WIDTH])) {
                $width = $this->_resizeParams['cropLeft'] + $options[self::PARAM_CROP_WIDTH];
                if ($width <= $this->_imageSize[0]) {
                    $this->_resizeParams['cropWidth'] = $options[self::PARAM_CROP_WIDTH];
                } else {
                    $this->_resizeParams['cropWidth'] = $this->_imageSize[0] - $this->_resizeParams['cropLeft'];
                }
            } elseif (isset($options[self::PARAM_RIGHT])) {
                $width = $this->_resizeParams['cropLeft'] + $options[self::PARAM_RIGHT];
                if ($width < $this->_imageSize[0]) {
                    $this->_resizeParams['cropWidth'] = $this->_imageSize[0] - $width;
                } else {
                    $this->_resizeParams['cropWidth'] = $this->_imageSize[0] - $this->_resizeParams['cropLeft'];
                }
            }
        } elseif (isset($options[self::PARAM_CROP_WIDTH])) {
            if ($options[self::PARAM_CROP_WIDTH] >= $this->_imageSize[0]) {
                $this->_resizeParams['cropWidth'] = $this->_imageSize[0];
                $this->_resizeParams['cropLeft'] = 0;
            } elseif (isset($options[self::PARAM_RIGHT])) {
                $width = $options[self::PARAM_CROP_WIDTH] + $options[self::PARAM_RIGHT];
                if ($width <= $this->_imageSize[0]) {
                    $this->_resizeParams['cropWidth'] = $options[self::PARAM_CROP_WIDTH];
                    $this->_resizeParams['cropLeft'] = $this->_imageSize[0] - $this->_resizeParams['cropWidth'];
                } else {
                    $this->_resizeParams['cropWidth'] = $this->_imageSize[0] - $options[self::PARAM_RIGHT];
                    $this->_resizeParams['cropLeft'] = 0;
                }
            }
        } elseif (isset($options[self::PARAM_RIGHT])) {
            $this->_resizeParams['cropLeft'] = 0;
            if ($options[self::PARAM_RIGHT] < $this->_imageSize[0]) {
                $this->_resizeParams['cropWidth'] = $this->_imageSize[0] - $options[self::PARAM_RIGHT];
            } else {
                $this->_resizeParams['cropWidth'] = $this->_imageSize[0];
                $this->_resizeParams['cropLeft'] = 0;
            }
        }
        if (isset($options[self::PARAM_TOP])) {
            $this->_resizeParams['cropTop'] = (int) $options[self::PARAM_TOP];
            if (isset($options[self::PARAM_CROP_HEIGHT])) {
                $height = $this->_resizeParams['cropTop'] + $options[self::PARAM_CROP_HEIGHT];
                if ($height <= $this->_imageSize[1]) {
                    $this->_resizeParams['cropHeight'] = $options[self::PARAM_CROP_HEIGHT];
                } else {
                    $this->_resizeParams['cropHeight'] = $this->_imageSize[1] - $this->_resizeParams['cropTop'];
                }
            } elseif (isset($options[self::PARAM_BOTTOM])) {
                $height = $this->_resizeParams['cropTop'] + $options[self::PARAM_BOTTOM];
                if ($height < $this->_imageSize[1]) {
                    $this->_resizeParams['cropHeight'] = $this->_imageSize[1] - $height;
                } else {
                    $this->_resizeParams['cropHeight'] = $this->_imageSize[1] - $this->_resizeParams['cropTop'];
                }
            }
        } elseif (isset($options[self::PARAM_CROP_HEIGHT])) {
            if ($options[self::PARAM_CROP_HEIGHT] >= $this->_imageSize[1]) {
                $this->_resizeParams['cropHeight'] = $this->_imageSize[1];
                $this->_resizeParams['cropTop'] = 0;
            } elseif (isset($options[self::PARAM_BOTTOM])) {
                $width = $options[self::PARAM_CROP_WIDTH] + $options[self::PARAM_BOTTOM];
                if ($width <= $this->_imageSize[1]) {
                    $this->_resizeParams['cropHeight'] = $options[self::PARAM_CROP_HEIGHT];
                    $this->_resizeParams['cropTop'] = $this->_imageSize[1] - $this->_resizeParams['cropHeight'];
                } else {
                    $this->_resizeParams['cropHeight'] = $this->_imageSize[1] - $options[self::PARAM_BOTTOM];
                    $this->_resizeParams['cropTop'] = 0;
                }
            }
        } elseif (isset($options[self::PARAM_BOTTOM])) {
            $this->_resizeParams['cropTop'] = 0;
            if ($options[self::PARAM_BOTTOM] < $this->_imageSize[1]) {
                $this->_resizeParams['cropHeight'] = $this->_imageSize[1] - $options[self::PARAM_BOTTOM];
            } else {
                $this->_resizeParams['cropHeight'] = $this->_imageSize[1];
            }
        }
    }
    
    /**
     * get offset
     * 
     * @param int $outer
     * @param int $inner
     * @return int
     */
    private function _getOffset($outer, $inner)
    {
        return ceil(($outer - $inner) / 2);
    }
    
    /**
     * execute resize
     * 
     * @return boolean
     */
    private function _resize()
    {
        switch ($this->_imageSize[2]) {
            case IMAGETYPE_GIF:
                $this->_resizeGif();
                break;
            
            case IMAGETYPE_JPEG:
                $this->_resizeJpeg();
                break;
            
            case IMAGETYPE_PNG:
                $this->_resizePng();
                break;
            
            default:
                break;
        }
        
        return true;
    }
    
    /**
     * resize GIF image
     * 
     * @return string
     */
    private function _resizeGif()
    {
        $image = imagecreatetruecolor($this->_resizeParams['width'], $this->_resizeParams['height']);
        imagecopyresampled($image, $this->_imageResource, 0, 0, $this->_resizeParams['cropLeft'], $this->_resizeParams['cropTop'], $this->_resizeParams['width'], $this->_resizeParams['height'], $this->_resizeParams['cropWidth'], $this->_resizeParams['cropHeight']);
        if ($this->_output) {
            $this->_sendMimeHeader();
            imagegif($image);
            imagedestroy($image);
            imagedestroy($this->_imageResource);
            exit;
        }
        $filename = $this->_resizeParams['directory'] . DS . $this->_resizeParams['filename'];
        imagegif($image, $filename);
        imagedestroy($image);
        
        return $filename;
    }
    
    /**
     * resize JPEG image
     * 
     * @return string
     */
    private function _resizeJpeg()
    {
        $image = imagecreatetruecolor($this->_resizeParams['width'], $this->_resizeParams['height']);
        imagecopyresampled($image, $this->_imageResource, 0, 0, $this->_resizeParams['cropLeft'], $this->_resizeParams['cropTop'], $this->_resizeParams['width'], $this->_resizeParams['height'], $this->_resizeParams['cropWidth'], $this->_resizeParams['cropHeight']);
        if ($this->_output) {
            $this->_sendMimeHeader();
            imagejpeg($image);
            imagedestroy($image);
            imagedestroy($this->_imageResource);
            exit;
        }
        $filename = $this->_resizeParams['directory'] . DS . $this->_resizeParams['filename'];
        imagejpeg($image, $filename);
        imagedestroy($image);
        
        return $filename;
    }
    
    /**
     * resize PNG image
     * 
     * @return string
     */
    private function _resizePng()
    {
        $image = imagecreatetruecolor($this->_resizeParams['width'], $this->_resizeParams['height']);
        imagecopyresampled($image, $this->_imageResource, 0, 0, $this->_resizeParams['cropLeft'], $this->_resizeParams['cropTop'], $this->_resizeParams['width'], $this->_resizeParams['height'], $this->_resizeParams['cropWidth'], $this->_resizeParams['cropHeight']);
        if ($this->_output) {
            $this->_sendMimeHeader();
            imagepng($image);
            imagedestroy($image);
            imagedestroy($this->_imageResource);
            exit;
        }
        $filename = $this->_resizeParams['directory'] . DS . $this->_resizeParams['filename'];
        imagepng($image, $filename);
        imagedestroy($image);
        
        return $filename;
    }
    
    /**
     * send mime header
     */
    private function _sendMimeHeader()
    {
        ob_end_clean();
        header('Content-Type: ' . $this->_imageSize['mime']);
    }
    
}
