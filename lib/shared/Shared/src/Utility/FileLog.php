<?php
/**
 * Shared FileLog Utility
 *
 * @category   Shared
 * @package    Shared\Utility
 */

namespace Shared\Utility;

use Model\UploadRegister;

use Web\Utility\DateHelper;


class FileLog
{
    /**
     *
     * @var \Model\UploadRegister 
     */
    private $_model;
    
    /**
     * 
     * @var int
     */
    private $_userId;
    
    /**
     * 
     * @var int
     */
    private $_admin = 0;
    
    public function __construct($userId, $admin = false)
    {
        $this->_userId = $userId;
        if ($admin) {
            $this->_admin = 1;
        }
        $this->_model = new UploadRegister();
    }
    
    /**
     * register upload
     * 
     * @param string $filename
     * @param string $path
     * @return int
     */
    public function register($filename, $path)
    {
        $insert = [
            'filename' => $filename,
            'file_path' => $path,
            'created_by' => $this->_userId,
            'created_by_admin' => $this->_admin,
            'created_at' => DateHelper::timestamp(),
        ];
        
        return $this->_model->insert($insert);
    }
    
    /**
     * set used status
     * 
     * @param array|int $list
     */
    public function used($list)
    {
        $update = ['used' => 1];
        if (is_array($list)) {
            $sql = $this->_model->getSql();
            $sql->whereIn('id', $list);
            $this->_model->update($update, $sql->getWhere(), count($list));
        } else {
            $this->_model->updateById($update, $list);
        }
    }
    
    public function mark($path, $filename)
    {
        $sql = $this->_model->getSql();
        $sql->whereEqual('file_path', $path)
            ->whereEqual('filename', $filename)
            ->whereZero('used');
        $where = $sql->getWhere();
        if ($this->_model->selectTotal($where) > 0) {
            $this->_model->update(['used' => 1], $where, 1);
        }
    }
    
    public function removed($path, $filename)
    {
        $sql = $this->_model->getSql();
        $sql->whereEqual('file_path', $path)
            ->whereEqual('filename', $filename)
            ->whereOne('used');
        $where = $sql->getWhere();
        if ($this->_model->selectTotal($where) > 0) {
            $this->_model->update(['used' => 0], $where, 1);
        }
    }
    
}
