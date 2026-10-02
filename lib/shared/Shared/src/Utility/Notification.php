<?php
/**
 * Shared Notification Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{UserNotification, AdminNotification, Administrator};

use Shared\Constant\UserType;

use Web\Utility\DateHelper;


class Notification
{
    /**
     *
     * @var int 
     */
    private $_propertyId = 0;
    
    /**
     * constructor
     * 
     * @param int $id user id
     */
    public function __construct($id = 0)
    {
        $this->_propertyId = $id;
       
    }
    
    /**
     * admin notification
     * 
     * @param int|array $to
     * @param string $subject
     * @param string $message
     * @return boolean
     */
    public function admin($to, $subject, $message)
    {
        $notification = [
            'administrator_id' => 0,
            'subject' => $subject,
            'message' => $message,
            'created_on' => DateHelper::timestamp(),
            'property_id' => $this->_propertyId,
        ];
        
        $model = new AdminNotification();
        if (is_array($to)) {
            $insert = $model->getInsertPreparedStatement(array_keys($notification));
            foreach ($to as $administratorId) {
                $notification['administrator_id'] = $administratorId;
                $insert->execute(array_values($notification));
            }
        } else {
            $notification['administrator_id'] = $to;
            $model->insert($notification);
        }
        
        return true;
    }
    
    /**
     * manager notification
     * 
     * @param string $subject
     * @param string $message
     * @return boolean
     */
    public function manager($subject, $message)
    {
        $model = new Administrator();
        $sql = $model->getSql();
        $sql->whereId('administrator_type_id', UserType::MANAGER)
            ->whereOne('active');
        $where = $sql->getWhere();
        if (0 == $model->selectTotal($where)) {
            return false;
        }
        
        return $this->admin($model->selectIds($where), $subject, $message);
    }
    
    /**
     * add user notification
     * 
     * @param int $userId
     * @param string $subject
     * @param string $message
     * @return boolean
     */
    public function user($userId, $subject, $message)
    {
        $notification = [
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $message,
            'created_on' => DateHelper::timestamp(),
            'property_id' => $this->_propertyId,
        ];
        
        $model = new UserNotification();
        $model->insert($notification);
        
        return true;
    }
    
    /**
     * set property id
     * 
     * @param int $id
     */
    public function setPropertyId($id = 0)
    {
        $this->_propertyId = $id;
    }
    
}
