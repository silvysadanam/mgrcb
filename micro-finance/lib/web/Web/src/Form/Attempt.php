<?php
/**
 * Form Attempt handler
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Core\Session;


class Attempt
{
    /**
     *
     * @var int 
     */
    private $_attempts;
    
    /**
     *
     * @var string 
     */
    private $_session;
    
    /**
     *
     * @var int 
     */
    private $_limit;
    
    public function __construct($session = 'FormAttempt', $limit = 5)
    {
        $this->_session = $session;
        $this->_limit = $limit;
        $this->_attempts = Session::get($this->_session, 0);
    }
    
    /**
     * check blocked
     * 
     * @return boolean
     */
    public function isBlocked()
    {
        return $this->_attempts > $this->_limit;
    }
    
    /**
     * check valid request
     * 
     * @return boolean
     */
    public function isValid()
    {
        return ! $this->isBlocked();
    }
    
    /**
     * register new attempt
     */
    public function attempted()
    {
        ++$this->_attempts;
        Session::set($this->_session, $this->_attempts);
    }
    
    /**
     * get attempt count
     * 
     * @return int
     */
    public function getAttempts()
    {
        return $this->_attempts;
    }
    
    /**
     * stop
     * 
     * @param string $message
     */
    public function stop($message = '')
    {
        die($message);
    }
    
    /**
     * stop script if blocked
     * 
     * @param string $message
     */
    public function stopIfBlocked($message = '')
    {
        if ($this->isBlocked()) {
            $this->stop($message);
        }
    }
    
}
