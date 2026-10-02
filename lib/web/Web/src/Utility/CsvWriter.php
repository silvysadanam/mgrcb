<?php
/**
 * Web CSV Writer Utility
 * 
 * @category Web
 * @package Utility
 */

namespace Web\Utility;


class CsvWriter
{
    const DELIMITER = ',';
    const ENCLOSURE = '"';
    
    /**
     *
     * @var resource 
     */
    private $_filePointer;
    
    /**
     *
     * @var boolean 
     */
    private $_started = false;
    
    /**
     *
     * @var string 
     */
    private $_filename;
    
    /**
     *
     * @var array 
     */
    private $_cells = [];
    
    public function __construct($filename = 'report.csv')
    {
        $this->_filename = $filename;
    }
    
    /**
     * write row
     * 
     * @param array $data
     * @return $this
     */
    public function row($data)
    {
        if (! empty($this->_cells)) {
            $this->_writeLine($this->_cells);
            $this->_cells = [];
        }
        $this->_writeLine($data);
        
        return $this;
    }
    
    /**
     * write multiple rows
     * 
     * @param array $data
     * @return $this
     */
    public function rows($data)
    {
        if (! empty($this->_cells)) {
            $this->_writeLine($this->_cells);
            $this->_cells = [];
        }
        foreach ($data as $row) {
            $this->_writeLine($row);
        }
        
        return $this;
    }
    
    /**
     * add/write cell
     * 
     * @param string $value
     * @param boolean $eol
     * @return $this
     */
    public function cell($value, $eol = false)
    {
        $this->_cells[] = $value;
        if ($eol) {
            $this->_writeLine($this->_cells);
            $this->_cells = [];
        }
        
        return $this;
    }
    
    /**
     * write column
     * 
     * @param string $column
     * @param string $value
     * @param boolean $eol
     * @return $this
     */
    public function column($column, $value = '', $eol = false)
    {
        if (empty($this->_cells)) {
            if ($column > 0) {
                $this->fill('', $column);
            }
            $this->_cells[$column] = $value;
        } else {
            $last = count($this->_cells);
            if ($column >= $last) {
                for ($count = $last; $count < $column; $count++) {
                    $this->_cells[] = '';
                }
            }
            $this->_cells[$column] = $value;
        }
        if ($eol) {
            $this->_writeLine($this->_cells);
            $this->_cells = [];
        }
        
        return $this;
    }
    
    /**
     * fill cells
     * 
     * @param string $value
     * @param int $count
     * @param boolean $eol
     * @return $this
     */
    public function fill($value = '', $count = 1, $eol = false)
    {
        $temp = array_fill(0, $count, $value);
        if (! empty($this->_cells)) {
            $this->_cells += $temp;
        } else {
            $this->_cells = $temp;
        }
        if ($eol) {
            $this->_writeLine($this->_cells);
            $this->_cells = [];
        }
        
        return $this;
    }
    
    /**
     * finish writing
     */
    public function finish($exit = true)
    {
        if ($this->_started) {
            fclose($this->_filePointer);
        }
        
        $exit and exit;
    }
    
    /**
     * encode string
     * 
     * @param string $string
     * @return string
     */
    public function encode($string)
    {
        if (false !== strpos($string, '\\')) {
            $string = stripslashes($string);
        }
        
        return $string;
    }
    
    /**
     * remove extra spaces including CRLF
     * 
     * @param string $string
     * @return string
     */
    public function removeSpaces($string)
    {
        return preg_replace('/\s+/', ' ', $string);
    }
    
    
    /**
     * send header
     */
    private function _sendHeader()
    {
        // clear output buffering
        if (ob_get_level() > 0) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        // enable auto flush output
        ob_implicit_flush(true);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $this->_getFilename());
        $this->_filePointer = fopen('php://output', 'w');
        $this->_started = true;
    }
    
    /**
     * write csv line
     * 
     * @param array $data
     */
    private function _writeLine($data)
    {
        if (! $this->_started) {
            $this->_sendHeader();
        }
        fputcsv($this->_filePointer, $data, self::DELIMITER, self::ENCLOSURE);
    }
    
    /**
     * get filename for download
     * 
     * @return string
     */
    private function _getFilename()
    {
       if ('' == $this->_filename) {
           $this->_filename = 'report.csv';
       }
       $filename = preg_replace('/[^a-z0-9_.-]+/i', '_', $this->_filename);
       if (0 == preg_match('/\.csv$/i', $filename)) {
           $filename .= '.csv';
       }
       
       return $filename;
    }
    
}
