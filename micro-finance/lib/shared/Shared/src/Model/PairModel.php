<?php
/**
 * Shared Pair Model
 *
 * @category   Shared
 * @package    Shared\Model
 */

namespace Shared\Model;

use Web\Database\Model;
use Web\Database\Table\Db;
use Model\Member;

class PairModel extends Model
{
    const NAME = 'name';
    const TITLE = 'title';
    const TYPE = 'type';
    
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
    
    /**
     *
     * @var array 
     */
    private $_orderByTitle = [];
    private $_memberModel;
    public function __construct()
    {
        parent::__construct();
        
        $this->_titles[self::NAME] = [
            Db::MEMBER, Db::ADMINISTRATOR, Db::FMC
        ];
        $this->_titles[self::TITLE] = [];
        $this->_titles[self::TYPE] = [];
        $this->_orderByTitle = [Db::FMC, Db::MEMBER, Db::LOAN_SOURCE, Db::LOAN_TYPE];
        $this->_memberModel = new Member();
    }
    
    /**
     * loan type
     * 
     * @return array
     */
    public function loanType()
    {
        return $this->_get(Db::LOAN_TYPE);
    }
    
    /**
     * administrator
     * 
     * @return array
     */
    public function administrator()
    {
        return $this->_get(Db::ADMINISTRATOR);
    }
    
    /**
     * member
     * 
     * @return array
     */
    public function member()
    {
        return $this->_get(Db::MEMBER);
    }

     public function panchayath()
    {
        return $this->_get(Db::PANCHAYATH_BRAND);
    }
     /**
     * auctioneer
     * 
     * @return array
     */
    public function auctioneer()
    {
       return $this->_memberLookup(2, true);
    }

    /**
     * fmc
     * 
     * @return array
     */
    public function fmc()
    {
        return $this->_get(Db::FMC);
    }
    
    /**
     * member code
     * 
     * @return array
     */
    public function memberCode()
    {
        return $this->get(Db::MEMBER, 'code', 'code');
    }

    /**
     * fmc code
     * 
     * @return array
     */
    public function fmcCode()
    {
        return $this->get(Db::FMC, 'code', 'code');
    }
    
    /**
     * loan source
     * 
     * @return array
     */
    public function loanSource()
    {
        return $this->_get(Db::LOAN_SOURCE);
    }
    
    /**
     * loan status
     * 
     * @return array
     */
    public function loanStatus()
    {
        return $this->_get(Db::LOAN_STATUS);
    }
    
     /**
     * document type
     * 
     * @return array
     */
    public function documentType()
    {
        return $this->_get(Db::DOCUMENT_TYPE);
    }
    
     public function rationCardType()
    {
        return $this->_get(Db::RATION_CARD);
    }
    /**
     * panchayath brand
     * 
     * @return array
     */
    public function panchayathBrand()
    {
        return $this->_get(Db::PANCHAYATH_BRAND);
    }
    
    public function diseaseTypes()
    {
        return $this->_get(Db::DISEASE);
    }
    /**
     * card type
     * 
     * @return array
     */
    public function cardType()
    {
        return $this->_get(Db::CARD_TYPE);
    }
    
    /**
     * account type
     * 
     * @return array
     */
    public function accountType()
    {
        return $this->_get(Db::ACCOUNT_TYPE);
    }
    
    /**
     * get pairs
     * 
     * @param string $table
     * @param string $column
     * @param string $orderBy
     * @return array
     */
    public function get($table, $column, $orderBy = null)
    {
        return $this->selectPairs($table, ['id', $column], null, $orderBy);
    }
    
    /**
     * get custom pairs
     * 
     * @param string $table
     * @param array $columns
     * @param string $where
     * @param string $orderBy
     * @return array
     */
    public function custom($table, $columns, $where = null, $orderBy = null)
    {
        return $this->selectPairs($table, $columns, $where, $orderBy);
    }
    
    /**
     * call pairs by table name
     * 
     * @param string $name
     * @param array $arguments
     * @return array
     */
    public function __call($name, $arguments)
    {
        $table = $name;
        if (1 == preg_match('/[A-Z]/', $name)) {
            $table = strtolower(preg_replace('/([A-Z])/', '_\\1', $name));
        }
        $orderBy = null;
        if (isset($arguments[0])) {
            $column = $arguments[0];
            if (isset($arguments[1])) {
                $orderBy = $arguments[1];
            } else {
                $orderBy = $this->_getOrderBy($table);
            }
        } else {
            $column = $this->_getColumn($table);
        }
        
        return $this->get($table, $column, $orderBy);
    }
    
    
    /**
     * get pairs by table
     * 
     * @param string $table
     * @return array
     */
    private function _get($table)
    {
        $column = $this->_getColumn($table);
        
        return $this->selectPairs($table, ['id', $column], $this->_getWhere($table), $this->_getOrderBy($table));
    }
    
    /**
     * get order by clause
     * 
     * @param string $table
     * @return string
     */
    private function _getOrderBy($table)
    {
        if (isset($this->_options[$table])) {
            if (isset($this->_options[$table]['order'])) {
                return $this->_options[$table]['order'];
            }
        }
        
        if (in_array($table, $this->_orderByTitle)) {
            return $this->_getColumn($table);
        }
        
        return null;
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
        $found = false;
        if (isset($this->_options[$table])) {
            if (isset($this->_options[$table]['column'])) {
                $column = $this->_options[$table]['column'];
                $found = true;
            }
        }
        if (! $found) {
            foreach ($this->_titles as $title => $tables) {
                if (! empty($tables)) {
                    if (in_array($table, $tables)) {
                        $column = $title;
                        break;
                    }
                }
            }
        }
        
        return $column;
    }
    
    /**
     * get where clause
     * 
     * @param string $table
     * @return string
     */
    private function _getWhere($table)
    {
        if (isset($this->_options[$table])) {
            if (isset($this->_options[$table]['where'])) {
                return $this->_options[$table]['where'];
            }
        }
        
        return null;
    }

       
    /**
     * generate member lookup
     * 
     * @param int $type
     * @param boolean $active
     * @return array
     */
    private function _memberLookup($type, $active = false)
    {
       // if (0 == $this->_memberTotal) {
       //   return [];
      //  }
        
        $sql = $this->_memberModel->getSql();
        //$sql->whereId('fmc_id', $this->_fmc->id)
        //    ->whereId('member_type_id', $type);
        $sql->whereId('member_type_id', $type);
        if ($active) {
            $sql->whereZero('disabled');
        }
        
        $where = $sql->getWhere();
        if (0 == $this->_memberModel->selectTotal($where)) {
            return [];
        }
        
        $list = $this->_memberModel->selectFields(['id', 'code', 'name'], $where, 'code');
        $options = [];
        foreach ($list as $item) {
            $options[$item->id] = $item->code . ' ' . $item->name;
        }
        
        return $options;
    }
}
