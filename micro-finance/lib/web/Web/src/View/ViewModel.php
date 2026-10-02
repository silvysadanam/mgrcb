<?php
/**
 * Web View ViewModel
 *
 * @category   Web
 * @package    Web\View
 */

namespace Web\View;

use Web\Mvc\Module;
use Web\Form\FormHelper;


class ViewModel
{
    /**
     * View variables 
     * 
     * @var array 
     */
    private $_variables = [];
    
    /**
     * registered view helpers 
     * 
     * @var array 
     */
    private $_helpers = [];
    
    /**
     * renderer
     * 
     * @var int 
     */
    private $_renderer = null;
    
    /**
     * response body
     * 
     * @var string 
     */
    private $_response = null;
    
    private $_helperPaths;
    
    private $_nonResolvable = [];
    
    /**
     * constructor method 
     * 
     * @param array $variables
     */
    public function __construct($renderer = null, $variables = null)
    {
        $this->_renderer = $renderer;
        if (null === $variables) {
            $variables = [];
        }

        // Initializing the variables container
        $this->setVariables($variables, true);
        $this->_helperPaths = [
            BS . Module::getModuleNamespace() . BS . 'ViewHelper' . BS,
            BS . 'Web' . BS . 'View' . BS . 'ViewHelper' . BS,
        ];
    }

    /**
    * Property overloading: set variable value
    *
    * @param string $name
    * @param mixed $value
    * @return void
    */
    public function __set($name, $value)
    {
        $this->setVariable($name, $value);
    }

    /**
    * Property overloading: get variable value
    *
    * @param string $name
    * @return mixed
    */
    public function __get($name)
    {
        if (!$this->__isset($name)) {
            return null;
        }
        return $this->_variables[$name];
    }

    /**
    * Property overloading: do we have the requested variable value?
    *
    * @param string $name
    * @return boolean
    */
    public function __isset($name)
    {
        return isset($this->_variables[$name]);
    }

    /**
    * Property overloading: unset the requested variable
    *
    * @param string $name
    * @return void
    */
    public function __unset($name)
    {
        if (!$this->__isset($name)) {
            return null;
        }
        unset($this->_variables[$name]);
    }

    /**
    * Get a single view variable
    *
    * @param string $name
    * @param mixed|null $default (optional) default value if the variable is not present.
    * @return mixed
    */
    public function getVariable($name, $default = null)
    {
        if (array_key_exists($name, $this->_variables)) {
            return $this->_variables[$name];
        }
        return $default;
    }

    /**
    * Set view variable
    *
    * @param string $name
    * @param mixed $value
    * @return \Web\View\ViewModel
    */
    public function setVariable($name, $value)
    {
        $this->_variables[$name] = $value;
        return $this;
    }

    /**
    * Set view variables en masse
    *
    * Should be an array.
    *
    * @param array $variables
    * @param boolean $overwrite Whether or not to overwrite 
    *        the internal container with $variables
    * @return \Web\View\ViewModel
    */
    public function setVariables($variables, $overwrite = false)
    {
        if (is_array($variables)) {
            if ($overwrite) {
                $this->_variables = $variables;
            } else {
                foreach ($variables as $key => $value) {
                    $this->setVariable($key, $value);
                }
            }
        }
        return $this;
    }

    /**
    * Get view variables
    *
    * @return array|ArrayAccess|Traversable
    */
    public function getVariables()
    {
        return $this->_variables;
    }

    /**
    * Clear all variables
    *
    * Resets the internal variable container to an empty container.
    *
    * @return \Web\View\ViewModel
    */
    public function clearVariables()
    {
        $this->_variables = [];
        return $this;
    }
    
    /**
     * check if variable is set
     * 
     * @param string $name
     * @return boolean
     */
    public function isVariable($name)
    {
        return isset($this->_variables[$name]);
    }
    
    /**
     * set response body
     * 
     * @param string $response
     * @return \Web\View\ViewModel
     */
    public function setResponse($response)
    {
        $this->_response = $response;
        return $this;
    }
    
    /**
     * get response body
     * 
     * @return string
     */
    public function getResponse()
    {
        return $this->_response;
    }
    
    /**
     * check if response added
     * 
     * @return boolean
     */
    public function hasResponse()
    {
        return ! empty($this->_response);
    }
    
    /**
     * clear response
     * 
     * @return \Web\View\ViewModel
     */
    public function clearResponse()
    {
        $this->_response = null;
        return $this;
    }
    
    /**
     * set renderer
     * 
     * @param int $renderer
     * @return \Web\View\ViewModel
     */
    public function setRenderer($renderer)
    {
        $this->_renderer = (int) $renderer;
        return $this;
    }
    
    /**
     * get renderer
     * 
     * @return int
     */
    public function getRenderer()
    {
        return $this->_renderer;
    }
    
    /**
     * form helper
     */
    public function formHelper()
    {
        $arguments = func_get_args();
        $helper = array_shift($arguments);
        $formHelper = FormHelper::class; 
        if (method_exists($formHelper, $helper)) {
            return call_user_func_array([$formHelper, $helper], $arguments);
        }
        return null;
    }

    /** 
     * Method overloading
     *
     * @param string $name
     * @param array $arguments 
     */
    public function __call($name, $arguments) 
    {
        $result = null;
        if (! in_array($name, $this->_nonResolvable)) {
            $helper = $this->_getHelper($name);
            if (false !== $helper) {
                if (0 == count($arguments)) {
                    $result = call_user_func_array([$helper, $name], []);
                } else {
                    $result = call_user_func_array([$helper, $name], $arguments);
                }
            } else {
                $this->_nonResolvable[] = $name;
            }
        }
        return $result;
    }
    
    
    /**
     * get helper instance
     * 
     * @param string $name
     * @return ViewHelper::class
     */
    private function _getHelper($name)
    {
        if (! array_key_exists($name, $this->_helpers)) {
            foreach ($this->_helperPaths as $path) {
                $helperClass = $path . ucfirst($name);
                if (class_exists($helperClass)) {
                    $this->_helpers[$name] = new $helperClass();
                    break;
                }
            }
            if (array_key_exists($name, $this->_helpers)) {
                return $this->_helpers[$name];
            }
        } else {
           return $this->_helpers[$name]; 
        }
        return false;
    }
    
}
