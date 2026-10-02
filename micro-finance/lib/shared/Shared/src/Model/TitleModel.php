<?php
/**
 * Shared Title Model
 *
 * @category   Shared
 * @package    Shared\Model
 */

namespace Shared\Model;

use Web\Database\Model;
use Web\Database\Table\Db;


class TitleModel extends Model
{
    const TITLE = 'title';
    const TYPE = 'type';
    const NAME = 'name';
    
    /**
     *
     * @var array 
     */
    private $_options = [];
    
    /**
     *
     * @var array 
     */
    private $_titles = [];
    
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_titles[self::TITLE] = [];
        $this->_titles[self::TYPE] = [];
        $this->_titles[self::NAME] = [Db::ADMINISTRATOR, Db::MEMBER, Db::FMC];
        
        //$this->_options[Db::DOCTOR] = 'name';
    }
    
    /**
     * administrator
     * 
     * @param int $id
     * @return string
     */
    public function administrator($id)
    {
        return $this->_get(Db::ADMINISTRATOR, $id);
    }
    
    /**
     * member
     * 
     * @param int $id
     * @return string
     */
    public function member($id)
    {
        return $this->_get(Db::MEMBER, $id);
    }
    
    /**
     * fmc
     * 
     * @param int $id
     * @return string
     */
    public function fmc($id)
    {
        return $this->_get(Db::FMC, $id);
    }
    
    /**
     * loan type
     * 
     * @param int $id
     * @return string
     */
    public function loanType($id)
    {
        return $this->_get(Db::LOAN_TYPE, $id);
    }
    
    /**
     * loan source
     * 
     * @param int $id
     * @return string
     */
    public function loanSource($id)
    {
        return $this->_get(Db::LOAN_SOURCE, $id);
    }
   
    /**
     * member
     * 
     * @param int $id
     * @return string
     */
    public function memberCode($id)
    {
        return $this->get(Db::MEMBER, $id, 'code');
    }
    
    /**
     * fmc code
     * 
     * @param int $id
     * @return string
     */
    public function fmcCode($id)
    {
        return $this->get(Db::FMC, $id, 'code');
    }
    
    /**
     * get title by table name and id
     * 
     * @param string $table
     * @param int $id
     * @param string $column
     * @return string
     */
    public function get($table, $id, $column = null)
    {
        if (null === $column) {
            return $this->_get($table, $id);
        }
        
        return $this->selectColumnById($table, $column, $id);
    }
    
    /**
     * call title by table name
     * 
     * @param string $name
     * @param array $arguments
     * @return string
     */
    public function __call($name, $arguments)
    {
        $column = null;
        $id = 0;
        if (isset($arguments[0])) {
            $id = $arguments[0];
            if (isset($arguments[1])) {
                $column = $arguments[1];
            }
        }
        $table = $name;
        if (1 == preg_match('/[A-Z]/', $name)) {
            $table = strtolower(preg_replace('/([A-Z])/', '_\\1', $name));
        }
        
        if ($id > 0) {
            return $this->get($table, $id, $column);
        }
    }
    
    
    /**
     * get title by table
     * 
     * @param string $table
     * @param int $id
     * @return string
     */
    private function _get($table, $id)
    {
        return $this->selectColumnById($table, $this->_getColumn($table), $id);
    }
    
    /**
     * get title column
     * 
     * @param string $table
     * @return string
     */
    private function _getColumn($table)
    {
        $column = $table;
        if (isset($this->_options[$table])) {
            $column = $this->_options[$table];
        } else {
            foreach ($this->_titles as $title => $tables) {
                if (in_array($table, $tables)) {
                    $column = $title;
                    break;
                }
            }
        }
        
        return $column;
    }

}
