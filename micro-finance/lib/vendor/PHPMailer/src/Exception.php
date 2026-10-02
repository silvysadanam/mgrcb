<?php
/**
 * PHPMailer exception handler
 * @package PHPMailer
 */

namespace PHPMailer;


class Exception extends \Exception
{
    /**
     * Prettify error message output
     * @return string
     */
    public function errorMessage()
    {
        return '<strong>' . htmlspecialchars($this->getMessage()) . '</strong><br>' . PHP_EOL;
    }
    
}
