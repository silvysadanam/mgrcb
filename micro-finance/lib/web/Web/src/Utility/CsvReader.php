<?php
/**
 * Web CSV Reader Utility
 * 
 * @category Web
 * @package Utility
 */

namespace Web\Utility;

use Web\Exception;


class CsvReader
{
    const DELIMITER = ',';
    const ENCLOSURE = '"';
    
    /**
     *
     * @var resource 
     */
    private $_filePointer = false;
    
    /**
     *
     * @var array|false 
     */
    private $_row = false;
    
    /**
     *
     * @var int 
     */
    private $_line = 0;
    
    /**
     *
     * @var array 
     */
    private $_title = [];
    
    /**
     *
     * @var int 
     */
    private $_columns = 0;
    
    /**
     *
     * @var array 
     */
    private $_lines = [];
    
    public function __construct($file)
    {
        if (! empty($file)) {
            if (file_exists($file)) {
                if (1 == preg_match('/\.csv$/i', $file)) {
                    $this->_filePointer = fopen($file, 'r');
                    if (false === $this->_filePointer) {
                        throw new Exception('Source file cannot accessible');
                    }
                } else {
                    throw new Exception('Invalid source file type');
                }
            } else {
                throw new Exception('Source file not found');
            }
        } else {
            throw new Exception('Source file missing');
        }
    }
    
    /**
     * read row
     * 
     * @return array|boolean
     */
    public function read()
    {
        $this->_row = false;
        if (false !== $this->_filePointer) {
            if (false !== ($row = fgetcsv($this->_filePointer, 2048, self::DELIMITER, self::ENCLOSURE))) {
                ++$this->_line;
                if (1 == $this->_line) {
                    $this->_title = $row;
                    $this->_columns = count($row);
                }
                $this->_row = $row;
                $this->_lines[] = $row;
            } else {
                flcose($this->_filePointer);
                $this->_filePointer = false;
            }
        }
        return $this->_row;
    }
    
    /**
     * get title row
     * 
     * @return array
     */
    public function getTitleRow()
    {
        if (empty($this->_title)) {
            $this->read();
        }
        
        return $this->_title;
    }
    
    /**
     * get current row
     * 
     * @return array
     */
    public function getRow()
    {
        return $this->_row;
    }
    
    /**
     * check for empty row
     * 
     * @return boolean
     */
    public function isBlank()
    {
        if (! empty($this->_row)) {
            return [null] == $this->_row;
        }
        
        return true;
    }
    
    /**
     * read all lines
     * 
     * @return array
     */
    public function readAll()
    {
        $rows = [];
        if (false !== $this->_filePointer) {
            while($row = fgetcsv($this->_filePointer, 2048, self::DELIMITER, self::ENCLOSURE)) {
                if (empty($rows)) {
                    $this->_title = $row;
                    $this->_columns = count($row);
                }
                $rows[] = $row;
            }
            fclose($this->_filePointer);
            $this->_filePointer = false;
        }
        $this->_line = count($rows);
        $this->_lines = $rows;
        
        return $rows;
    }
    
    /**
     * get row by line number
     * 
     * @param int $line
     * @return array|boolean
     */
    public function getLine($line = 1)
    {
        if (! empty($this->_lines)) {
            if ($line > 0) {
                --$line;
                if (isset($this->_lines[$line])) {
                    return $this->_lines[$line];
                }
            }
        }
        
        return false;
    }

    /**
     * finish reading
     */
    public function finish()
    {
        if (false !== $this->_filePointer) {
            fclose($this->_filePointer);
        }
    }
    
}
