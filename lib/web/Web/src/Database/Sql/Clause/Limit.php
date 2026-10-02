<?php
/**
 * Database Sql Clause Limit
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Clause
 */

namespace Web\Database\Sql\Clause;


final class Limit
{
    /**
     *
     * @var string 
     */
    private $_limit;
    
    public function __construct()
    { 
        $this->_limit = '';
    }
    
    /**
     * set limit
     * 
     * @param string $limit
     */
    public function set($limit)
    {
        $this->_limit = $limit;
    }
    
    /**
     * add limit
     * 
     * @param string|int $limit
     * @param int $offset
     */
    public function limit($limit, $offset = 0)
    {
        if ($offset > 0) {
            $this->_limit = $offset . ',' . $limit;
        } else {
            $this->_limit = $limit;
        }
    }
    
    /**
     * get limit
     * 
     * @return string
     */
    public function get()
    {
        return $this->_limit;
    }
    
}
