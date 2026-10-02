<?php
/**
 * ControlCenter Custom Model
 * 
 * @category ControlCenter
 * @package ControlCenter\Model
 */

namespace ControlCenter\Model;

use Web\Database\Model;


class CustomModel extends Model
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('custom');
    }
    
    /**
     * truncate table|tables
     * 
     * @param string|array $table
     * @return int
     */
    public function truncate($table)
    {
        if (empty($table)) {
            return false;
        }
        if (! is_array($table)) {
            $table = [ $table ];
        }
        $quotedTables = [];
        foreach ($table as $item) {
            $quotedTables[] = $this->quoteIdentifier($item);
        }
        $statement = 'TRUNCATE TABLE ' . $this->combine($quotedTables);
        
        return $this->execute($statement);
    }
    
    /**
     * combine list with glue
     * 
     * @param array $list
     * @param string $glue
     * @return string
     */
    public function combine($list, $glue = ', ')
    {
        return implode($glue, $list);
    }
    
}
