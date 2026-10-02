<?php
/**
 * Application DcbViewer Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\DcbStatement;


class DcbViewer extends DcbStatement
{
    const BLANK = '<td>&nbsp;</td>';
    
    /**
     * 
     * @var array
     */
    private $_columnBackgrounds;
    
    /**
     * 
     * @var array
     */
    private $_noBackground;
    
    /**
     * 
     * @param \Web\View\ViewModel $view
     */
    public function __construct($view)
    {
        parent::__construct($view);
        $this->_columnBackgrounds = [
            'demand' => [8, 9, 10, 11],
            'collection' => [13, 14, 15, 16, 17, 18, 19, 20, 21],
            'ageing' => [27, 28],
        ];
        $this->_noBackground = [
            1, 2, 3, 4, 5, 6, 7, 12, 22, 23, 24, 25, 26,
        ];
    }
    
    /**
     * get no summary row
     * 
     * @return string
     */
    public function noSummary()
    {
        return $this->blankColumns(28, true);
    }
    
    /**
     * get blank columns
     * 
     * @param int $columns
     * @param boolean $header
     * @return string
     */
    public function blankColumns($columns, $header = false)
    {
        if ($header) {
            $blank = str_replace('d', 'h', self::BLANK);
        } else {
            $blank = self::BLANK;
        }
        
        return str_repeat($blank, $columns);
    }
    
    /**
     * get column class
     * 
     * @param int $column
     * @return string
     */
    public function columnClass($column)
    {
        $class = '';
        if (in_array ($column, $this->_noBackground)) {
            return $class;
        }
        
        foreach ($this->_columnBackgrounds as $background => $columns) {
            if (in_array($column, $columns)) {
                $class = $background;
                break;
            }
        }
        
        if ('' != $class) {
            $class = ' ' . $class;
        }
        
        return $class;
    }
    
}
