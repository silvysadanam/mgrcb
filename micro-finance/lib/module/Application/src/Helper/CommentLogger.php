<?php
/**
 * Application Helper CommentLogger
 * 
 * @category Application
 * @package  Application\Helper
 */

namespace Application\Helper;

use Model\LoanComment;

use Web\Utility\DateHelper;


class CommentLogger
{
    /**
     * 
     * @var int
     */
    private $_id;
    
    /**
     * 
     * @var int
     */
    private $_administratorId;
    
    /**
     * 
     * @var \Model\LoanComment
     */
    private $_model;
    
    public function __construct($administratorId)
    {
        $this->_administratorId = $administratorId;
        $this->_model = new LoanComment();
    }
    
    public function id($id)
    {
        $this->_id = $id;
        
        return $this;
    }
    
    public function save($comment)
    {
        $record = [
            'loan_id' => $this->_id,
            'administrator_id' => $this->_administratorId,
            'description' => $comment,
            'created_at' => DateHelper::timestamp(),
        ];
        
        return $this->_model->insert($record);
    }
    
}
