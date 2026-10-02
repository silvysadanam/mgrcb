<?php
/**
 * Form validator Factory
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Exception;


class ValidatorFactory
{
    /**
     * load validator class
     * 
     * @param string $name
     * @return \Web\Form\class
     * @throws Exception
     */
    public static function load($name)
    {
        $class = __NAMESPACE__ . BS . 'Validator' . BS . ucfirst($name);
        if (class_exists($class)) {
            return new $class;
        }
        throw new Exception('"' . $class . '" not found');
    }
    
}
