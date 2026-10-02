<?php
/**
 * Shared custom Model
 *
 * @category   Shared
 * @package    Shared\Model
 */

namespace Shared\Model;

use Web\Database\Model;
use Web\Database\Table\Db;


class CustomModel extends Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * get min date - MIN(DATE(`date_field`))
     * 
     * @param string $table
     * @param string $field
     * @param string $where
     * @return string
     */
    public function minDate($table, $field, $where = null)
    {
        $helper = $this->getHelper();
        $query = 'SELECT MIN(' . $helper->quoteIdentifier($field) . ') '
               . 'FROM ' . $helper->quoteIdentifier($table);
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' LIMIT 1';
        
        $result = $this->query($query);
        
        return $result->fetchColumn();
    }
    
    /**
     * get min year - MIN(YEAR(`date_field`))
     * 
     * @param string $table
     * @param string $field
     * @param string $where
     * @return int
     */
    public function minYear($table, $field, $where = null)
    {
        $helper = $this->getHelper();
        $query = 'SELECT MIN(YEAR(' . $helper->quoteIdentifier($field) . '))'
               . 'FROM ' . $helper->quoteIdentifier($table);
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' LIMIT 1';
        
        $result = $this->query($query);
        
        return (int) $result->fetchColumn();
    }
    
    /**
     * get max year - MAX(YEAR(`date_field`))
     * 
     * @param string $table
     * @param string $field
     * @param string $where
     * @return int
     */
    public function maxYear($table, $field, $where = null)
    {
        $helper = $this->getHelper();
        $query = 'SELECT MAX(YEAR(' . $helper->quoteIdentifier($field) . '))'
               . 'FROM ' . $helper->quoteIdentifier($table);
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' LIMIT 1';
        
        $result = $this->query($query);
        
        return (int) $result->fetchColumn();
    }
    
    /**
     * get last id from table
     * 
     * @param string $table
     * @return int
     */
    public function lastId($table)
    {
        return $this->getTableGateway($table)->lastId();
    }
    
    /**
     * get max date - MAX(DATE(`date_field`))
     * 
     * @param string $table
     * @param string $field
     * @param string $where
     * @return string
     */
    public function maxDate($table, $field, $where = null)
    {
        $helper = $this->getHelper();
        $query = 'SELECT MAX(' . $helper->quoteIdentifier($field) . ') '
               . 'FROM ' . $helper->quoteIdentifier($table);
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' LIMIT 1';
        
        $result = $this->query($query);
        
        return $result->fetchColumn();
    }

}
