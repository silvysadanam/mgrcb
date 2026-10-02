<?php
/**
 * Shared Branch Store
 *
 * @category   Shared
 * @package    Shared\Branch
 */

namespace Shared\Branch;

use Model\Branch;
use Model\User;
use Model\Medicine;
use Model\AuctionChit;
use Model\AuctionChitEntry;

use Shared\Constant\AdministratorType;
use Shared\Constant\UserType;

use Web\View\Support\LookupStore;


class Store
{
    /**
     * 
     * @var int
     */
    private $_id;
    
    /**
     * 
     * @var \Model\Branch
     */
    private $_model;
    
    /**
     * 
     * @var \stdClass
     */
    private $_branch;
    
    /**
     * 
     * @var array
     */
    private $_sections = [];
    
    /**
     * 
     * @var string
     */
    private $_where;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var int
     */
    private $_userId = 0;
    
    /**
     * 
     * @var int
     */
    private $_userType;
    
    /**
     * 
     * @var boolean
     */
    private $_admin = true;
    
    /**
     * 
     * @var \Model\User
     */
    private $_userModel;
    
    /**
     * 
     * @var \Model\AuctionChit
     */
    private $_auctionChitModel;
       
    /**
     * 
     * @param int $id
     */
    public function __construct($id, $admin = true)
    {
        $this->_admin = $admin;
        $this->_model = new Branch();
        $this->_id = $id;
        $this->_branch = $this->_model->find($id);
        $this->_sql = $this->_model->getSql();
        $this->_sql->whereId('branch_id', $id);
        $this->_where = $this->_sql->getWhere();
        $this->_sql->clear();
        $this->_userModel = new User();
        $this->_auctionChitModel = new AuctionChit();
    }
    
    public function user($id, $userType)
    {
        $this->_userId = $id;
        $this->_userType = $userType;
        
        return $this;
    }
    
    public function id()
    {
        return $this->_id;
    }
    
    public function branch()
    {
        return $this->_branch;
    }
    
    public function loadAccess()
    {
        $this->_sections['access'] = [
            'edit' => false,
            'delete' => false,
            'staff' => false,
            'member' => false,
            'donor' => false,
        ];
        if ($this->_admin) {
            switch ($this->_userType) {
                case AdministratorType::ADMINISTRATOR:
                    $this->_sections['access']['edit'] = true;
                    $this->_sections['access']['staff'] = true;
                    $this->_sections['access']['member'] = true;
                    $this->_sections['access']['donor'] = true;
                    break;
                
                case AdministratorType::MANAGER:
                    $this->_sections['access']['staff'] = true;
                    $this->_sections['access']['member'] = true;
                    $this->_sections['access']['donor'] = true;
                    break;
                
                default:
                    break;
            }
        }
        
        return $this;
    }
    
    public function loadStaff()
    {
        $this->_sql->clear();
        $this->_sql->where($this->_where)
                   ->whereIn('user_type_id', [UserType::MANAGER, UserType::STAFF]);
        $where = $this->_sql->getWhere();
        if ($this->_userModel->selectTotal($where) > 0) {
            $this->_sections['staff'] = $this->_userModel->select($where, 'user_code');
        }
        
        return $this;
    }
    
    public function loadUserCount()
    {
        $options = [
            UserType::MANAGER => 'manager_count',
            UserType::STAFF => 'staff_count',
            UserType::AUCTIONEER => 'donor_count',
            UserType::MEMBER => 'member_count',
        ];
        
        foreach ($options as $userType => $section) {
            $this->_sql->clear();
            $this->_sql->where($this->_where)
                       ->whereId('user_type_id', $userType);
            $this->_sections[$section] = $this->_userModel->selectTotal($this->_sql->getWhere());
        }
        
        return $this;
    }
    
    public function loadMemberOptions($active = true)
    {
        LookupStore::register('members', $this->_getUserOptions(UserType::MEMBER, $active));
        LookupStore::register('userName', $this->_getUserFieldOptions('name'));
        LookupStore::register('userCode', $this->_getUserFieldOptions('user_code'));
        
        return $this;
    }
    
    public function loadUserOptions($active = true)
    {
        LookupStore::register('userName', $this->_getUserFieldOptions('name', $active));
        LookupStore::register('userCode', $this->_getUserFieldOptions('user_code', $active));
        
        return $this;
    }
    
    public function loadAuctioneerOptions($active = true)
    {
        LookupStore::register('donors', $this->_getUserOptions(UserType::AUCTIONEER, $active));
        
        return $this;
    }
    
    public function loadFishOptions()
    {
        $model = new Medicine();
        LookupStore::register('medicine', $model->selectPairs(['id', 'name'], null, 'name'));
        
        return $this;
    }
    
    public function getAuctioneerIds()
    {
        $this->_sql->clear();
        $this->_sql->where($this->_where)
                   ->whereId('user_type_id', UserType::AUCTIONEER);
        
        return $this->_userModel->selectIds($this->_sql->getWhere());
    }
    
    public function getMemberIds()
    {
        $this->_sql->clear();
        $this->_sql->where($this->_where)
                   ->whereId('user_type_id', UserType::MEMBER);
        
        return $this->_userModel->selectIds($this->_sql->getWhere());
    }
    
    public function access()
    {
        return $this->_section('acess');
    }
    
    public function section($key)
    {
        return $this->_section($key);
    }
    
    public function isUser($id)
    {
        return $this->_id == $this->_userModel->selectColumnById('branch_id', $id);
    }
    
    public function isAuctionChit($id)
    {
        if (! $this->_auctionModel->hasId($id)) {
            return false;
        }
        
        $entry = $this->_auctionModel->find($id);
        $branchId = $this->_userModel->selectColumnById('branch_id', $entry->donor_id);
        if ($branchId != $this->_id) {
            return false;
        }
        
        if ($this->_admin) {
            return true;
        }
        
        if (in_array($this->_userType, [UserType::AUCTIONEER, UserType::MEMBER])) {
            if (UserType::AUCTIONEER == $this->_userType) {
                return $this->_userId == $entry->donor_id;
            }

            return $this->_userId == $entry->member_id;
        }
        
        return true;
    }
    
    private function _section($key)
    {
        if (isset($this->_sections[$key])) {
            return $this->_sections[$key];
        }
    }
    
    private function _getUserOptions($userType, $active = true)
    {
        $options = [];
        $this->_sql->clear();
        $this->_sql->where($this->_where)
                   ->whereId('user_type_id', $userType);
        if ($active) {
            $this->_sql->whereOne('active');
        }
        $where = $this->_sql->getWhere();
        
        if ($this->_userModel->selectTotal($where) > 0) {
            $list = $this->_userModel->select($where, 'name');
            foreach ($list as $item) {
                $options[$item->id] = $item->user_code . ' - ' . $item->combined_name;
            }
        }
        
        return $options;
    }
    
    private function _getUserFieldOptions($field = 'name', $active = true)
    {
        $this->_sql->clear();
        $this->_sql->where($this->_where);
        if ($active) {
            $this->_sql->whereOne('active');
        }
        
        return $this->_userModel->selectPairs(['id', $field], $this->_sql->getWhere(), $field);
        
    }
}
