<?php
/**
 * Web ViewHelper
 *
 * @category   Web
 * @package    Web\View
 */

namespace Web\View;

use Web\Core\Registry;
use Web\View\ViewModel;


class ViewHelper
{
    /**
     * view 
     * 
     * @var \Web\View\ViewModel 
     */
    protected $view;
    
    /**
     * constructor method 
     */
    public function __construct()
    {
        $this->view = new ViewModel();
    }
    
    /**
     * get registry value 
     * 
     * @param string $name
     * @return string 
     */
    protected function getRegistry($name)
    {
        return Registry::get($name);
    }
    
    /**
     * trim / from string
     * 
     * @param string $string
     * @param string|null $option
     * @return string
     */
    protected function trimSlash($string, $option = null)
    {
        if (null !== $option) {
            if ('r' == $option) {
                return rtrim($string, DS);
            } else {
                return ltrim($string, DS);
            }
        }
        
        return trim($string, DS);
    }
    
    /**
     * escape HTML 
     * 
     * @param string $string
     * @return string
     */
    protected function escape($string)
    {
        return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8', false);
    }
    
    /**
     * print string
     * 
     * @param string $string
     */
    protected function printOut($string)
    {
        echo $string;
    }

}
