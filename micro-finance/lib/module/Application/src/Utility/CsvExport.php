<?php
/**
 * Application CsvExport Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Web\Utility\CsvWriter;


class CsvExport
{
    const BLANK = '';
    const PERCENTAGE = '%';
    
    /**
     * 
     * @var \Web\Utility\CsvWriter
     */
    protected $_writer;
   
    /**
     * 
     * @var array
     */
    protected $_default = [];
    
    /**
     * 
     * @var array
     */
    protected $_direct = [];
    
    /**
     * 
     * @var array
     */
    protected $_row = [];
    
    /**
     * 
     * @var array
     */
    protected $_cells;
    
    /**
     * 
     * @var object
     */
    protected $_column;
    
    /**
     * 
     * @var \Web\View\ViewModel
     */
    protected $view;
    
    /**
     * 
     * @param \Web\View\ViewModel $view
     */
    public function __construct($view)
    {
        $this->view = $view;
    }

    protected function blank()
    {
        return self::BLANK;
    }
    
    protected function percentage()
    {
        return self::PERCENTAGE;
    }
    
    protected function cells($instance)
    {
        $this->_column = $instance;
        $this->_cells = $instance->get();
        $this->_default = array_fill(1, count($this->_cells), '');
    }
    
    protected function loadWriter($title = '')
    {
        $this->_writer = new CsvWriter($title);
    }
    
    /**
     * write row
     * 
     * @param array $row
     * @return null
     */
    protected function _write($row = null)
    {
        if (null !== $row) {
            $this->_writer->row($row);
            return;
        }
        
        if (! empty($this->_row)) {
            $this->_writer->row($this->_row);
            $this->_row = [];
        }
    }
    
    protected function integer($number)
    {
        return round($number, 0);
    }
    
    protected function decimal($number, $decimals = 2)
    {
        $format = '%0.' . $decimals . 'f';
        
        return sprintf($format, $number);
    }
}
