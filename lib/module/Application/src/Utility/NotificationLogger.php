<?php
/**
 * Application NotificationLogger Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Model\{Notification, Administrator};

use Application\Constant\ManagerType;

use Web\Utility\DateHelper;
use Web\Core\Registry;


class NotificationLogger
{
    /**
     * 
     * @var \Model\Administrator
     */
    private $_administratorModel;
    
    /**
     *
     * @var int 
     */
    private $_userId = 0;
    
    /**
     *
     * @var int 
     */
    private $_fmcId = 0;
    
    /**
     *
     * @var string 
     */
    private $_subject;
    
    /**
     *
     * @var string 
     */
    private $_message;
    
    /**
     *
     * @var int 
     */
    private $_referenceId = 0;
    
    /**
     *
     * @var int 
     */
    private $_referenceType = 0;
    
    /**
     * 
     * @var array
     */
    private $_recipients = [];
    
    /**
     * 
     * @var array
     */
    private $_targets = [];
    
    /**
     * 
     * @var boolean
     */
    private $_copySelf = true;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;

    /**
     * constructor
     * 
     * @param int $id user id
     */
    public function __construct($id)
    {
        $this->_userId = $id;
        $this->_administratorModel = new Administrator();
        $creator = $this->_administratorModel->selectFieldsById(['manager_type', 'fmc_id'], $id);
        if (ManagerType::BRANCH == $creator->manager_type) {
            $this->_fmcId = $creator->fmc_id;
        }
        
        $this->_sql = $this->_administratorModel->getSql();
    }
    
    /**
     * set fmc id
     * 
     * @param int $id
     * @return $this
     */
    public function fmcId($id)
    {
        $this->_fmcId = $id;
        return $this;
    }
    
    /**
     * set subject
     * 
     * @param string $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->_subject = $subject;
        return $this;
    }
    
    /**
     * set message
     * 
     * @param string $message
     * @return $this
     */
    public function message($message)
    {
        $this->_message = $message;
        return $this;
    }
    
    /**
     * set reference
     * 
     * @param int $id
     * @param int $type
     * @return $this
     */
    public function reference($id, $type = 1)
    {
        $this->_referenceId = $id;
        $this->_referenceType = $type;
        
        return $this;
    }
    
    /**
     * set recipients
     * 
     * @param array $list
     * @return $this
     */
    public function recipients($list)
    {
        $this->_recipients = $list;
        return $this;
    }
    
    /**
     * set targets
     * 
     * @param array $list
     * @return $this
     */
    public function targets($list)
    {
        $this->_targets = $list;
        return $this;
    }
    
    /**
     * set copy self option
     * 
     * @param boolean $copy
     * @return $this
     */
    public function copySelf($copy)
    {
        $this->_copySelf = $copy;
        return $this;
    }
    
    /**
     * send notification
     */
    public function send()
    {
        if (empty($this->_recipients)) {
            if (! empty($this->_targets)) {
                foreach ($this->_targets as $target) {
                    $this->_loadTarget($target);
                }
            } else {
                $targets = [ManagerType::CEO, ManagerType::FINANCE, ManagerType::AUDITOR];
                foreach ($targets as $target) {
                    $this->_loadTarget($target);
                }
            }
        }
        
        if ($this->_fmcId > 0) {
            $this->_loadTarget(ManagerType::BRANCH);
        }     
    }
    
    /**
     * send notification to auditors
     */
    public function auditor()
    {
        $this->_loadTarget(ManagerType::AUDITOR);
        $this->_send();
    }
    
    /**
     * send notification to ceo
     */
    public function ceo()
    {
        $this->_loadTarget(ManagerType::CEO);
        $this->_send();
    }
    
    /**
     * send notification to finance manager
     */
    public function finance()
    {
        $this->_loadTarget(ManagerType::FINANCE);
        $this->_send();
    }
    
    /**
     * send notification to fmc managers
     */
    public function fmc()
    {
        if ($this->_fmcId > 0) {
            $this->_loadTarget(ManagerType::BRANCH);
            $this->_send();
        }
    }
    
    
    /**
     * load target role users
     * 
     * @param int $type
     * @return null
     */
    private function _loadTarget($type)
    {
        $this->_sql->clear();
        $this->_sql->whereId('manager_type', $type);
        if (ManagerType::BRANCH == $type) {
            if (0 == $this->_fmcId) {
                return;
            }
            
            $this->_sql->whereId('fmc_id', $this->_fmcId);
        }
        
        $where = $this->_sql->getWhere();
        if (0 == $this->_administratorModel->selectTotal($where)) {
            return;
        }
        
        $list = $this->_administratorModel->selectIds($where);
        foreach ($list as $id) {
            if (! in_array($id, $this->_recipients)) {
                $this->_recipients[] = $id;
            }
        }
    }
    
    /**
     * add notification
     * 
     * @return null
     */
    private function _send()
    {
        if ($this->_copySelf) {
            if (! in_array($this->_userId, $this->_recipients)) {
                $this->_recipients[] = $this->_userId;
            }
        }
        
        if (empty($this->_recipients)) {
            return;
        }
        
        $notification = [
            'administrator_id' => 0,
            'subject' => $this->_subject,
            'message' => $this->_message,
            'created_at' => DateHelper::timestamp(),
            'created_by' => $this->_userId,
            'site_id' => Registry::get('site_id'),
        ];
        
        if ($this->_fmcId > 0) {
            $notification['fmc_id'] = $this->_fmcId;
        }
        
        if ($this->_referenceId > 0) {
            $notification['reference_id'] = $this->_referenceId;
            $notification['reference_type'] = $this->_referenceType;
        }
        
        $notificationModel = new Notification();
        $insert = $notificationModel->getInsertPreparedStatement(array_keys($notification));
        $to = array_unique($this->_recipients);
        foreach ($to as $administratorId) {
            $notification['administrator_id'] = $administratorId;
            $insert->execute(array_values($notification));
        }
    }
    
}
