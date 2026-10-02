<?php
/**
 * Application Constant FlashMessage
 * 
 * @category Application
 * @package  Application\Constant
 */

namespace Application\Constant;


class FlashMessage
{
    const CREATE = 'create';
    const CHANGE = 'change';
    const REMOVE = 'remove';
    const NO_CHANGE = 'nochange';
    const INVALID_REQUEST = 'Invalid request';
    
    public function __construct() { }
    
}
