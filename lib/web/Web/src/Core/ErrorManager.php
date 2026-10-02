<?php
/**
 * Web Core Error Manager
 * 
 * Error & Exception Handler
 *
 * @category   Web
 * @package    Web\Core
 */

namespace Web\Core;

use Web\Core\Registry;
use DateTime;


class ErrorManager
{
    const LOG_DIR = 'log';
    
    /**
     *
     * @var boolean 
     */
    private $_displayErrors = true;
    
    /**
     *
     * @var boolean 
     */
    private $_logErrors = true;
    
    /**
     *
     * @var boolean 
     */
    private $_trace = true;
    
    /**
     *
     * @var string 
     */
    private $_logDirectory;
    
    public function __construct()
    {
        $settings = Registry::get('debug');
        if (! empty($settings)) {
            if (isset($settings['display'])) {
                $this->_displayErrors = 1 == $settings['display'];
            }
            if (isset($settings['log'])) {
                $this->_logErrors = 1 == $settings['log'];
            }
            if (isset($settings['trace'])) {
                $this->_trace = 1 == $settings['trace'];
            }
        }
        $this->_logDirectory = STORAGE_DIR . DS . self::LOG_DIR;
        set_error_handler([$this, 'error']);
        set_exception_handler([$this, 'exception']);
    }
    
    /**
     * error handler
     * 
     * @param int $number
     * @param string $message
     * @param string $file
     * @param int $line
     * @return boolean
     */
    public function error($number, $message, $file = null, $line = null)
    {
        if (! (error_reporting() & $number)) {
            if ($this->_logErrors) {
                $log = 'ERROR [' . $number . ']: ' . str_replace(APP_DIR, '', $file) . ' line ' . $line 
                     . ' : ' . $message;
                $this->_log($log);
            }
            return false;
        }
        if ($this->_displayErrors) {
            switch ($number) {
                case E_USER_ERROR:
                    echo '<p><strong>Error [' . $number . ']: </strong></p>';
                    echo '<p>' . $message . '</p>';
                    echo '<p>' . $file . ' Line ' . $line . '</p>';
                    if ($this->_trace) {
                        echo '<p><strong>Backtrace</strong></p><p><pre>';
                        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                        echo '</pre></p>';
                    }
                    exit;
                    break;

                case E_USER_WARNING:
                    echo '<p><strong>Warning [' . $number . ']: </strong></p>';
                    echo '<p>' . $message . '</p>';
                    echo '<p>' . $file . ' Line ' . $line . '</p>';
                    if ($this->_trace) {
                        echo '<p><strong>Backtrace</strong></p><p><pre>';
                        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                        echo '</pre></p>';
                    }
                    break;

                case E_USER_NOTICE:
                    echo '<p><strong>Notice [' . $number . ']: </strong></p>';
                    echo '<p>' . $message . '</p>';
                    echo '<p>' . $file . ' Line ' . $line . '</p>';
                    if ($this->_trace) {
                        echo '<p><strong>Backtrace</strong></p><p><pre>';
                        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                        echo '</pre></p>';
                    }
                    break;

                default:
                    echo '<p><strong>Unknown error [' . $number . ']: </strong></p>';
                    echo '<p>' . $message . '</p>';
                    echo '<p>' . $file . ' Line ' . $line . '</p>';
                    if ($this->_trace) {
                        echo '<p><strong>Backtrace</strong></p><p><pre>';
                        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                        echo '</pre></p>';
                    }
                    break;
            }
        }
        if ($this->_logErrors) {
            $log = 'ERROR [' . $number . ']: ' . str_replace(APP_DIR, '', $file) . ' line ' . $line 
                 . ' : ' . $message;
            $this->_log($log);
        }
        
        return true;
    }
    
    /**
     * exception handler
     * 
     * @param \Exception|Web\Exception $exception
     * @return boolean
     */
    public function exception($exception)
    {
        if (! error_reporting()) {
            if ($this->_logErrors) {
                $log = 'EXCEPTION: ' . str_replace(APP_DIR, '', $exception->getFile()) . ' on line ' 
                     . $exception->getLine() . ' : ' . $exception->getMessage();
                $this->_log($log);
            }
            return false;
        }
        if ($this->_displayErrors) {
            echo '<p><strong>Uncaught exception: </strong></p>';
            echo '<p>' . $exception->getMessage() . '</p>';
            echo '<p>' . $exception->getFile() . ' Line ' . $exception->getLine() . '</p>';
            if ($this->_trace) {
                echo '<p><strong>Backtrace</strong></p><p><pre>';
                echo $exception->getTraceAsString();
                echo '</pre></p>';
            }
        }
        if ($this->_logErrors) {
            $log = 'EXCEPTION: ' . str_replace(APP_DIR, '', $exception->getFile()) . ' on line ' 
                 . $exception->getLine() . ' : ' . $exception->getMessage();
            $this->_log($log);
        }
        
        return true;
    }
    
    /**
     * log messages
     * 
     * @param string $message
     */
    private function _log($message)
    {
        if (is_dir($this->_logDirectory)) {
            if (is_writable($this->_logDirectory)) {
                $time = new DateTime();
                $file = $this->_logDirectory . DS . $time->format('Ymd') . '.log';
                $data = '[' . $time->format('H:i:s T') . '] ' . $message . PHP_EOL;
                file_put_contents($file, $data, FILE_APPEND);
            }
        }
    }
    
}
