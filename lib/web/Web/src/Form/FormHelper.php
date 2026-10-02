<?php
/**
 * Form helper
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use DateTime;


class FormHelper
{
    const CHECKED = ' checked="checked"';
    const SELECTED = ' selected="selected"';
    const DATEPICKER = 'd/m/Y';
    const SQL_DATE = 'Y-m-d';
    const SQL_DATETIME = 'Y-m-d H:i:s';
    
    /**
     *
     * @var array 
     */
    private static $_empty = ['', '0000-00-00'];
    
    /**
     * 
     * @var string
     */
    private static $_indentation = '';


    /**
     * HTML escape
     * 
     * @param string $string
     * @param bool $doubleEncode
     * @return string
     */
    public static function escapeHtml($string, $doubleEncode = false)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', $doubleEncode);
    }
    
    /**
     * generate OPTION tags for <select> element
     * 
     * @param array $options
     * @param int|array $selected
     * @param int $indentation
     * @return string
     */
    public static function select($options, $selected = 0, $indentation = null)
    {
        self::_setTabs($indentation);
        $html = '';
        $optionTag = '<option value="%s">%s</option>' . PHP_EOL;
        $optionTagSelected = '<option value="%s"' . self::SELECTED . '>%s</option>' . PHP_EOL;
        if (is_array($selected)) {
            if (! empty($selected)) {
                foreach ($options as $value => $text) {
                    if (in_array($value, $selected)) {
                        $html .= self::$_indentation . sprintf($optionTagSelected, $value, self::escapeHtml($text));
                    } else {
                        $html .= self::$_indentation . sprintf($optionTag, $value, self::escapeHtml($text));
                    }
                }
            } else {
                foreach ($options as $value => $text) {
                    $html .= self::$_indentation . sprintf($optionTag, $value, self::escapeHtml($text));
                }
            }
        } else {
            $hasSelected = false;
            if ('' != $selected) {
                if (is_numeric($selected)) {
                    if ($selected > 0) {
                        $hasSelected = true;
                    }
                } else {
                    $hasSelected = true;
                }
            }
            if ($hasSelected) {
                $found = false;
                foreach ($options as $value => $text) {
                    $tag = $optionTag;
                    if (! $found) {
                        if ($selected == $value) {
                            $found = true;
                            $tag = $optionTagSelected;
                        }
                    }
                    $html .= self::$_indentation . sprintf($tag, $value, self::escapeHtml($text));
                }
            } else {
                foreach ($options as $value => $text) {
                    $html .= self::$_indentation . sprintf($optionTag, $value, self::escapeHtml($text));
                }
            }
            
        }
        
        return $html;
    }
    
    /**
     * generate checkbox element
     * 
     * @param string $name
     * @param string $value
     * @param bool $checked
     * @return string
     */
    public static function checkbox($name, $value = 1, $checked = false)
    {
        $html = '<input type="checkbox" name="' . $name . '" id="' . $name 
              . '" value="' . self::escapeHtml($value) . '"';
        if ($checked) {
            $html .= self::CHECKED;
        }
        $html .= '>';
        
        return $html;
    }
    
    /**
     * generate textbox element
     * 
     * @param string $name
     * @param string $value
     * @param array $attributes
     * @return string
     */
    public static function textbox($name, $value = '', $attributes = array())
    {
        $exclude = [ 'name', 'id', 'type', 'value' ];
        $html = '<input type="text" name="' . $name . '" id="' . $name 
              . '" value="' . self::escapeHtml($value) . '"';
        if (! empty($attributes)) {
            if (is_array($attributes)) {
               $extra = [];
               foreach ($attributes as $attributeName => $attributeValue) {
                   if (! in_array($attributeName, $exclude)) {
                       $extra[] = $attributeName . '="' . self::escapeHtml($attributeValue) . '"';
                   }
               }
               if (! empty($extra)) {
                   $html .= ' ' . implode(' ', $extra);
               }
            } else {
                $html .= ' ' . $attributes;
            }
        }
        $html .= '>';
        
        return $html;
    }
    
    /**
     * generate textarealement
     * 
     * @param string $name
     * @param string $value
     * @param array $attributes
     * @return string
     */
    public static function textarea($name, $value = '', $attributes = [])
    {
        $exclude = [ 'name', 'id' ];
        $html = '<textarea name="' . $name . '" id="' . $name  . '"';
        if (! empty($attributes)) {
            if (is_array($attributes)) {
               $extra = array();
               foreach ($attributes as $attributeName => $attributeValue) {
                   if (! in_array($attributeName, $exclude)) {
                       $extra[] = $attributeName . '="' . self::escapeHtml($attributeValue) . '"';
                   }
               }
               if (! empty($extra)) {
                   $html .= ' ' . implode(' ', $extra);
               }
            } else {
                $html .= ' ' . $attributes;
            }
        }
        $html .= '>' . self::escapeHtml($value) . '</textarea>';
        
        return $html;
    }
    
    /**
     * change SQL date to datepicker format
     * 
     * @param string $date
     * @return string
     */
    public static function toDatepicker($date)
    {
        $formattedDate = '';
        if (! in_array($date, self::$_empty)) {
            $dateTime = new DateTime($date);
            $formattedDate = $dateTime->format(self::DATEPICKER);
        }
        
        return $formattedDate;
    }
    
    /**
     * change datepicker date to SQL date format
     * 
     * @param string $date
     * @return string
     */
    public static function fromDatepicker($date)
    {
        $formattedDate = '';
        if ('' != $date) {
            $dateTime = DateTime::createFromFormat(self::DATEPICKER, $date);
            $formattedDate = $dateTime->format(self::SQL_DATE);
        }
        
        return $formattedDate;
    }
    
    /**
     * today
     * 
     * @param bool $datepicker
     * @return string
     */
    public static function today($datepicker = true)
    {
        if ($datepicker) {
            $format = self::DATEPICKER;
        } else {
            $format = self::SQL_DATE;
        }
        $dateTime = new DateTime();
        
        return $dateTime->format($format);
    }
    
    /**
     * now - sql datetime
     * 
     * @return string
     */
    public static function now()
    {
        $dateTime = new DateTime();
        
        return $dateTime->format(self::SQL_DATETIME);
    }
    
    /**
     * generate OPTION tags for <select> element between given range
     * 
     * @param array $options
     * @param int|array $selected
     * @return type 
     */
    public static function selectRange($options, $selected = 0)
    {
        $html = '';
        $optionTag = '<option value="%s">%s</option>' . PHP_EOL;
        $optionTagSelected = '<option value="%s"' . self::SELECTED . '>%s</option>' . PHP_EOL;
        $min = 1;
        $max = 1;
        if (is_array($options)) {
            if (isset($options['min'])) {
                $min = (int) $options['min'];
            }
            if (isset($options['max'])) {
                $max = (int) $options['max'];
            }
            if ($min > $max) {
                $min = $max;
            }
        } else {
            $max = (int) $options;
        }
        if ($selected > 0) {
            $found = false;
            for ($count = $min; $count <= $max; $count++) {
                $tag = $optionTag;
                if (! $found) {
                    if ($selected == $count) {
                        $found = true;
                        $tag = $optionTagSelected;
                    }
                }
                $html .= self::$_indentation . sprintf($tag, $count, $count);
            }
        } else {
            for ($count = $min; $count <= $max; $count++) {
                $html .= self::$_indentation . sprintf($optionTag, $count, $count);
            }
        }
        
        return $html;
    }
    
    /**
     * generate option group
     * 
     * @param array $options
     * @param int $selected
     * @return string
     */
    public static function selectGroup($options, $selected = 0)
    {
        $html = '';
        $optionTag = '<option value="%s">%s</option>' . PHP_EOL;
        $optionTagSelected = '<option value="%s"' . self::SELECTED . '>%s</option>' . PHP_EOL;
        $optionGroup = '<optgroup label="%s">%s</optgroup>';
        $hasSelected = false;
        $multiple = false;
        $optionIndentation = self::$_indentation . str_repeat(' ', 4);
        if (is_array($selected)) {
            $multiple = true;
            $hasSelected = ! empty($selected);
        } elseif ('' != $selected) {
            if (is_numeric($selected)) {
                if ($selected > 0) {
                    $hasSelected = true;
                }
            } else {
                $hasSelected = true;
            }
        }
        if ($hasSelected) {
            $found = false;
            foreach ($options as $group => $items) {
                $tags = '';
                foreach ($items as $value => $text) {
                    $tag = $optionTag;
                    if ($multiple) {
                        if (in_array($value, $selected)) {
                            $tag = $optionTagSelected;
                        }
                    } elseif (! $found) {
                        if ($selected == $value) {
                            $found = true;
                            $tag = $optionTagSelected;
                        }
                    }
                    $tags .= $optionIndentation . sprintf($tag, $value, self::escapeHtml($text));
                }
                $html .= self::$_indentation . sprintf($optionGroup, self::escapeHtml($group), $tags);
            }
        } else {
            foreach ($options as $group => $items) {
                $tags = '';
                foreach ($items as $value => $text) {
                    $tags .= $optionIndentation . sprintf($optionTag, $value, self::escapeHtml($text));
                }
                $html .= self::$_indentation . sprintf($optionGroup, self::escapeHtml($group), $tags);
            }
        }
       
        return $html;
    }
    
    /**
     * get current timestamp
     * 
     * @return int
     */
    public static function timestamp()
    {
        return time();
    }
    
    /**
     * get checked attribute
     * 
     * @param int $value
     * @param int|array $compare
     * @return string
     */
    public static function checked($value, $compare = null)
    {
        $checked = '';
        if (null !== $compare) {
            if (is_array($compare)) {
                if (in_array($value, $compare)) {
                    $checked = self::CHECKED;
                }
            } elseif ($value == $compare) {
                $checked = self::CHECKED;
            }
        } elseif ($value > 0) {
            $checked = self::CHECKED;
        }
        
        return $checked;
    }
    
    /**
     * generate OPTION tags for <select> element with text values
     * 
     * @param array $options
     * @param string $selected
     * @return string
     */
    public static function selectText($options, $selected = '')
    {
        $html = '';
        $optionTag = '<option value="%s">%s</option>' . PHP_EOL;
        $optionTagSelected = '<option value="%s"' . self::SELECTED . '>%s</option>' . PHP_EOL;
        
        $hasSelected = false;
        if ('' != $selected) {
            $hasSelected = true;
        }
        if ($hasSelected) {
            $found = false;
            foreach ($options as $text) {
                $tag = $optionTag;
                if (! $found) {
                    if ($selected == $text) {
                        $found = true;
                        $tag = $optionTagSelected;
                    }
                }
                $value = self::escapeHtml($text);
                $html .= self::$_indentation . sprintf($tag, $value, $value);
            }
        } else {
            foreach ($options as $text) {
                $value = self::escapeHtml($text);
                $html .= self::$_indentation . sprintf($optionTag, $value, $value);
            }
        }
        
        return $html;
    }
    
    /**
     * generate OPTION tags for <select> element with 0 as a value
     * 
     * @param array $options
     * @param int $selected
     * @return string
     */
    public static function selectWithZero($options, $selected = 0)
    {
        $html = '';
        $optionTag = '<option value="%s">%s</option>' . PHP_EOL;
        $optionTagSelected = '<option value="%s"' . self::SELECTED . '>%s</option>' . PHP_EOL;
        
        foreach ($options as $value => $text) {
            if ($selected == $value) {
                $tag = self::$_indentation . $optionTagSelected;
            } else {
                $tag = self::$_indentation . $optionTag;
            }
            $html .= self::$_indentation . sprintf($tag, $value, self::escapeHtml($text));
        }
         
        return $html;
    }
    
    /**
     * set indentation tabs
     * 
     * @param int $tabs
     */
    public static function tabs($tabs = 0)
    {
        if ($tabs > 0) {
            $length = 4 * $tabs;
            self::$_indentation = str_repeat(' ', $length);
        } else {
            self::$_indentation = '';
        }
    }
    
    private static function _setTabs($tabs)
    {
        if (null !== $tabs) {
            self::tabs($tabs);
        }
    }
    
}
