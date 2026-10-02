<?php
/**
 * INPUT $_POST handler
 *
 * @category   Web
 * @package    Web\Core
 */

namespace Web\Core;

use Web\Core\Server;


class Input
{
    const VALUE_DEFAULT = 0;
    const VALUE_STRING = 1;
    const VALUE_INTEGER = 2;
    const VALUE_ARRAY = 3;
    
    /**
     *
     * @var boolean 
     */
    private $_post = false;
    
	public function __construct()
    {
        $this->_post = 'POST' == Server::requestMethod();
    }
    
    /**
     * check if post
     * 
     * @param string $name
     * @return boolean 
     */
    public function isPost($name = null)
    {
        $post = false;
        if (null === $name) {
            $post = $this->_post;
        } elseif ($this->_post) {
            $post = $this->_isPost($name);
        }
        
        return $post;
    }
    
    /**
     * get post value
     * 
     * @param string $name
     * @param boolean $numeric
     * @return string|int|array 
     */
    public function post($name = null, $numeric = false)
    {
        $value = '';
        if ($this->_post) {
            if (null === $name) {
                $value = [];
            } elseif ($this->_isPost($name)) {
                if ($numeric) {
                    $value = $this->_post($name, FILTER_SANITIZE_NUMBER_INT);
                } else {
                    $value = trim($this->_post($name));
                }
            }
        }
        
        return $value;
    }
    
    /**
     * post id value
     * 
     * @param string $name
     * @return int 
     */
    public function postId($name)
    {
        $value = 0;
        if ($this->_post) {
            if ($this->_isPost($name)) {
                $value = (int) $this->_post($name, FILTER_SANITIZE_NUMBER_INT);
            }
        }
        
        return $value;
    }
    
    /**
     * get post variable of array type
     * 
     * @param string $name
     * @return array
     */
    public function postArray($name)
    {
        $value = [];
        if ($this->_post) {
            if ($this->_isPost($name)) {
                $value = filter_input(INPUT_POST, $name, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            }
        }
        
        return $value;
    }
    
    /**
     * get decimal value of post variable
     * 
     * @param string $name
     * @return float
     */
    public function postDecimal($name)
    {
        $value = '';
        if ($this->_post) {
            if ($this->_isPost($name)) {
                $value = $this->_post($name, FILTER_SANITIZE_NUMBER_FLOAT);
            }
        }
        
        return $value;
    }
    
    /**
     * get post values of specified fields
     * 
     * @param array $options
     * @param boolean $fillDefault
     * @return array
     */
    public function postAll($options, $fillDefault = true)
    {
        $filters = [];
        foreach ($options as $item) {
            $filter = FILTER_DEFAULT;
            $flags = false;
            if (is_array($item)) {
                if (count($item) > 1) {
                    $name = $item[0];
                    switch ($item[1]) {
                        case self::VALUE_ARRAY:
                            $flags = FILTER_REQUIRE_ARRAY;
                            break;
                        
                        case self::VALUE_INTEGER:
                            $filter = FILTER_SANITIZE_NUMBER_INT;
                            break;
                        
                        case self::VALUE_STRING:
                            $filter = FILTER_DEFAULT;
                            break;
                        
                        default:
                            break;
                    }
                }
            } else {
                $name = $item;
            }
            if (false !== $flags) {
                $filters[$name] = ['filter' => $filter, 'flags' => $flags];
            } else {
                $filters[$name] = $filter;
            }
        }
        
        return filter_input_array(INPUT_POST, $filters, $fillDefault);
    }
    
    /**
     * check post variable
     * 
     * @param string $name
     * @return boolean
     */
    private function _isPost($name)
    {
        return filter_has_var(INPUT_POST, $name);
        //return null !== filter_input(INPUT_POST, $name);
    }
    
    /**
     * get post variable value
     * 
     * @param string $name
     * @param int $filter
     * @return string
     */
    private function _post($name, $filter = FILTER_DEFAULT)
    {
        return filter_input(INPUT_POST, $name, $filter);
    }
    
}
