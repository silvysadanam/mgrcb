<?php
/**
 * Application Helper TimelineLogger
 * 
 * @category Application
 * @package  Application\Helper
 */

namespace Application\Helper;

use Model\LoanTimeline;

use Web\Utility\DateHelper;


class TimelineLogger
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
     * @var \Model\LoanTimeline
     */
    private $_model;
    
    public function __construct($administratorId)
    {
        $this->_administratorId = $administratorId;
        $this->_model = new LoanTimeline();
    }
    
    public function id($id)
    {
        $this->_id = $id;
        
        return $this;
    }
    
    public function save($title, $description)
    {
        $record = [
            'loan_id' => $this->_id,
            'administrator_id' => $this->_administratorId,
            'title' => $title,
            'description' => $description,
            'created_at' => DateHelper::timestamp(),
        ];
        
        return $this->_model->insert($record);
    }
    
}
