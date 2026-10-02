<?php
/**
 * Web Mvc Event Manager
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\Exception;


class EventManager
{
    /**
     *
     * @var array 
     */
    private $_events;
    
    public function __construct()
    {
        $this->_events = [
            'bootstrap' => [],
            'dispatch' => [],
        ];
    }
    
    /**
     * register on bootstrap events
     * 
     * @param object $call callback
     */
    public function onBootstrap($call)
    {
        $this->_enqueue('bootstrap', $call);
    }
    
    /**
     * register on dispatch events
     * 
     * @param object $call callback
     */
    public function onDispatch($call)
    {
        $this->_enqueue('dispatch', $call);
    }
    
    /**
     * trigger callbacks
     * 
     * @param string $type
     */
    public function trigger($type)
    {
        if (isset($this->_events[$type])) {
            if (! empty($this->_events[$type])) {
                $this->_trigger($this->_events[$type]);
            }
            $this->_events[$type] = [];
        }
    }
    
    /**
     * shutdown script execution
     */
    public function shutdown()
    {
        exit;
    }
    
    
    /**
     * enqueue callback
     * 
     * @param string $type
     * @param object $target
     */
    private function _enqueue($type, $target)
    {
        $this->_events[$type][] = $target;
    }
    
    /**
     * trigger list of events
     * 
     * @param array $events
     */
    private function _trigger($events)
    {
        try {
            foreach ($events as $callback) {
                if (is_callable($callback)) {
                    call_user_func($callback);
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
}
