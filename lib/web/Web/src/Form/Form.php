<?php
/**
 * Form
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Form\FormValidator;
use Web\Form\FormError;
use Web\Core\Input;
use Web\Form\Csrf;
use Web\Form\FormFilter;
use Web\Core\Request;
use Web\Database\TableGateway;
use Web\Exception;
use Web\Core\Server;
use Web\Utility\DateHelper;
use Web\Form\FormFormatter;


class Form
{
    const ORIGINAL = 'fho_hidden_original';
    
    /**
     *
     * @var \Web\Form\FormValidator 
     */
    protected $validator;
    
    /**
     *
     * @var \Web\Form\FormError 
     */
    protected $error;
    
    /**
     *
     * @var \Web\Core\Input 
     */
    protected $input;
    
    /**
     *
     * @var \Web\Form\FormFilter
     */
    protected $filter;
    
    /**
     *
     * @var \Web\Form\FormFormatter 
     */
    protected $formatter;
    
    /**
     *
     * @var boolean 
     */
    private $_hasPost = false;
    
    /**
     *
     * @var array 
     */
    private $_fields = [];
    
    /**
     *
     * @var array 
     */
    private $_values = [];
    
    /**
     *
     * @var boolean 
     */
    private $_postback = false;
    
    /**
     *
     * @var array 
     */
    private $_checkboxes = [];
    
    /**
     *
     * @var array 
     */
    private $_multiselect = [];
    
    /**
     *
     * @var boolean 
     */
    private $_track = false;
    
    /**
     *
     * @var string 
     */
    private $_token = '';
    
    /**
     *
     * @var array 
     */
    private $_fieldTitles = [];
    
    /**
     *
     * @var string 
     */
    private $_heading = '';
    
    /**
     *
     * @var \Web\Form\Csrf
     */
    private $_csrf;
    
    /**
     *
     * @var string 
     */
    private $_tokenField = 'ft__token';
    
    /**
     *
     * @var array 
     */
    private $_rawValues = [];
    
    /**
     *
     * @var \Web\Database\TableGateway
     */
    private $_model;
    /**
     *
     * @var int 
     */
    private $_id = 0;
  
    /**
     * Constructor
     */
	public function __construct()
    {
        $this->input = new Input();
        $this->validator = new FormValidator();
        $this->error = new FormError();
        $this->filter = new FormFilter();
        $this->_hasPost = $this->input->isPost();
        $this->_csrf = new Csrf();
        $this->formatter = new FormFormatter();
    }
    
    /**
     * add field to form
     * 
     * @param string $name
     */
    public function addField($name)
    {
        if (! in_array($name, $this->_fields)) {
            $this->_fields[] = $name;
        }
    }
    
    /**
     * add fields to form
     * @param array $fields
     */
    public function addFields($fields)
    {
        foreach ($fields as $name) {
            $this->addField($name);
        }
    }
    
    /**
     * add filter
     * 
     * @param string $field
     * @param array $options
     */
    public function addFilter($field, $options)
    {
        if (in_array($field, $this->_fields)) {
            $this->filter->add($field, $options);
        }
    }
    
    /**
     * add filters
     * 
     * @param array $filters
     */
    public function addFilters($filters)
    {
        foreach ($filters as $field => $item) {
            $this->addFilter($field, $item);
        }
    }
    
    /**
     * reset form
     */
    public function reset()
    {
        $this->_fields = [];
        $this->_values = [];
        $this->error->clear();
        $this->_postback = false;
    }
    
    /**
     * get form fields
     * 
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }
    
    /**
     * populate form values
     * 
     * @param array|\stdClass $ras 
     * @param boolean $direct
     */
    public function populate($raw)
    {
        $data = $this->toArray($raw);
        if (! empty($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->_fields)) {
                    $this->_fields[] = $key;
                }
                $this->_values[$key] = $value;
            }
            foreach ($this->_fields as $key) {
                if (! isset($this->_values[$key])) {
                    if (in_array($key, $this->_checkboxes)) {
                        $this->_values[$key] = '0';
                    } elseif (in_array($key, $this->_multiselect)) {
                        $this->_values[$key] = [];
                    } else {
                        $this->_values[$key] = '';
                    }
                }
            }
        }
    }
    
    /**
     * check if form posted
     * 
     * @return boolean
     */
    public function isPost()
    {
        return $this->_hasPost;
    }

    /**
     * run postback
     * 
     * @return array
     */
    public function postback()
    {
        if (! $this->_postback) {
            $this->_postback = true;
            $this->_values = [];
            if ($this->_hasPost) {
                foreach ($this->_fields as $field) {
                    if (in_array($field, $this->_multiselect)) {
                        $this->_values[$field] = $this->_postArray($field);
                    } elseif (in_array($field, $this->_checkboxes)) {
                        $this->_values[$field] = $this->_postNumber($field);
                    } else {
                        $this->_values[$field] = $this->_postString($field);
                    }
                }
            }
        }
        
        return $this->_values;
    }
    
    /**
     * apply filters
     * 
     * @return array
     */
    public function applyFilters()
    {
        if (! $this->filter->isFiltered()) {
            $this->_rawValues = $this->_values;
            $this->_values = $this->filter->run($this->_values);
        }
        
        return $this->_values;
    }
    
    /**
     * get filtered values
     * 
     * @return array
     */
    public function getFilteredValues()
    {
        $this->applyFilters();
        
        return $this->_values;
    }
    
    /**
     * get raw/unfiltered values
     * 
     * @return array
     */
    public function getRawValues()
    {
        if (! empty($this->_rawValues)) {
            return $this->_rawValues;
        }
        
        return $this->_values;
    }
    
    /**
     * get post value
     * 
     * @param mixed $key
     * @return mixed
     */
    public function post($key = null, $array = false)
    {
        $value = '';
        if (null !== $key) {
            if ($array) {
                $value = $this->_postArray($key);
            } else {
                $value = $this->_postString($key);
            }
        }
        
        return $value;
    }
    
    /**
     * get field value
     * 
     * @param string $key
     * @param boolean $escape
     * @return string|array
     */
    public function getValue($key, $escape = false)
    {
        $value = '';
        if (isset($this->_values[$key])) {
            $value = $this->_values[$key];
            if ($escape && ! is_array($value)) {
                $value = $this->escape($value);
            }
        }
        
        return $value;
    }
    
    /**
     * get all field values
     * 
     * @return array
     */
    public function getValues()
    {
        return $this->_values;
    }
    
    /**
     * get escaped value for view
     * 
     * @param string $key
     * @return string
     */
    public function getHtmlValue($key)
    {
        return $this->getValue($key, true);
    }
    
    /**
     * get escaped value for view
     * 
     * @param string $key
     * @return string
     */
    public function getEscapedValue($key)
    {
        return $this->getValue($key, true);
    }
    
    /**
     * get numeric value of field
     * 
     * @param string $key
     * @return int
     */
    public function getNumericValue($key)
    {
        return (int) $this->getValue($key);
    }
    
    /**
     * get array value
     * 
     * @param string $name
     * @param string|int $key
     * @param boolean $escape
     * @return string
     */
    public function getArrayValue($name, $key, $escape = false)
    {
        $value = '';
        if (isset($this->_values[$name])) {
            if (is_array($this->_values[$name])) {
                if (isset($this->_values[$name][$key])) {
                    $value = $this->_values[$name][$key];
                    if ($escape && ! is_array($value)) {
                        $value = $this->escape($value);
                    }
                }
            }
        }
        
        return $value;
    }
    
    /**
     * get escaped array value
     * 
     * @param string $name
     * @param string|int $key
     * @return string
     */
    public function getEscapedArrayValue($name, $key)
    {
        return $this->getArrayValue($name, $key, true);
    }
    
    /**
     * add form error
     * 
     * @param string|array $error
     */
    public function addError($error)
    {
        $this->error->add($error);
    }
    
    /**
     * get errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->error->get();
    }
    
    /**
     * check if form has errors
     * 
     * @return boolean
     */
    public function hasError()
    {
        return $this->error->hasError();
    }
    
    /**
     * check if form has no errors
     * 
     * @return boolean
     */
    public function noError()
    {
        return $this->error->noError();
    }
    
    /**
     * HTML escape string
     * 
     * @param string $string
     * @return string
     */
    public function escape($string)
    {
         if (null === $string) {
            return '';
        }
        
        return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8', true);
    }
    
    /**
     * set track changes ON
     */
    public function trackChanges()
    {
        $this->_track = true;
    }
    
    /**
     * check if track changes enabled
     * 
     * @return boolean
     */
    public function isTrackChanges()
    {
        return $this->_track;
    }
    
    /**
     * get original values as hidden field for display in form
     * 
     * @return string
     */
    public function getOriginal()
    {
        $input = '';
        if ($this->_track) {
            if ($this->_postback) {
                if ($this->_isPost(self::ORIGINAL)) {
                    $values = $this->_postString(self::ORIGINAL);
                } else {
                    $values = base64_encode(serialize($this->_values));
                }
            } else {
                $values = base64_encode(serialize($this->_values));
            }
            if (! empty($values)) {
                $input = '<input type="hidden" name="' . self::ORIGINAL . '" value="' . $values . '">';
            }
        }
        
        return $input;
    }

    /**
     * get modified fields and values
     * 
     * @return array
     */    
    public function getModified()
    {
        $modified = $this->_values;
        if ($this->_track) {
            if ($this->_postback) {
                $original = $this->_postString(self::ORIGINAL);
                if (! empty($original)) {
                    $modified = [];
                    $values = unserialize(base64_decode($original));
                    foreach ($this->_values as $key => $value) {
                        if (isset($values[$key])) {
                            if ($values[$key] != $value) {
                                $modified[$key] = $value;
                            }
                        } else {
                            $modified[$key] = $value;
                        }
                    }
                }
            }
        }
        
        return $modified;
    }
    
    /**
     * set checkbox fields
     * 
     * @param string|array $fields
     */
    public function checkboxes($fields)
    {
        if (! is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $field) {
            if (! in_array($field, $this->_checkboxes)) {
                $this->_checkboxes[] = $field;
            }
        }
    }
    
    /**
     * set multi select fields
     * 
     * @param string|array $fields
     */
    public function multiselect($fields)
    {
        if (! is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $field) {
            if (! in_array($field, $this->_multiselect)) {
                $this->_multiselect[] = $field;
            }
        }
    }
    
    /**
     * set token for dynamic field names
     * 
     * @param array $fields
     */
    public function setToken($fields)
    {
        if ($this->_hasPost) {
            $this->_token = $this->_postString($this->_tokenField);
        }
        if ('' == $this->_token) {
            $this->_token = 'ft__' . substr(md5(time()), 0, 5);
        }
        $this->addField($this->_token);
        if (! empty($fields)) {
            $this->_values[$this->_token] = [];
            foreach ($fields as $field) {
                $this->_values[$this->_token][$field] = '';
            }
        }
    }
    
    /**
     * get token name
     * 
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }
    
    /**
     * get token input
     * 
     * @return string
     */
    public function getTokenInput()
    {
        return  $input = '<input type="hidden" name="' . $this->_tokenField . '" value="' . $this->_token . '" >';
    }
    
    /**
     * set field titles
     * 
     * @param array $data
     */
    public function setFieldTitles($data)
    {
        foreach ($data as $field => $title) {
            if (in_array($field, $this->_fields)) {
                $this->_fieldTitles[$field] = $title;
            }
        }
    }
    
    /**
     * get field title
     * 
     * @param string $field
     * @return string
     */
    public function getFieldTitle($field)
    {
        $title = '';
        if (isset($this->_fieldTitles[$field])) {
            $title = $this->_fieldTitles[$field];
        }
        return $title;
    }
    
    /**
     * load default field titles - generate from field name
     */
    public function loadDefaultFieldTitles()
    {
        foreach ($this->_fields as $field) {
            $this->_addFieldTitle($field);
        }
    }
    
    /**
     * set form heading
     * 
     * @param string $heading
     */
    public function setHeading($heading)
    {
        $this->_heading = $heading;
    }
    
    /**
     * get form heading
     * 
     * @return string
     */
    public function getHeading()
    {
        return $this->_heading;
    }
    
    /**
     * Format form errors
     * 
     * @return string
     */
    public function formatErrors()
    {
        $errors = '';
        if ($this->error->hasError()) {
            $errors = implode('<br>', $this->error->get());
        }
        return $errors;
    }
    
    /**
     * populate taken
     * 
     * @param array $data
     */
    public function populateToken($data)
    {
        if ('' == $this->_token) {
            $this->_token = 'ft__' . substr(md5(time()), 0, 5);
            $this->_values[$this->_token] = [];
            $this->addField($this->_token);
        }
        if (! empty($data)) {
            foreach ($data as $field => $value) {
                $this->_values[$this->_token][$field] = $value;
            }
        }
    }
    
    /**
     * Get token values
     * 
     * @return array
     */
    public function getTokenValues()
    {
        $values = [];
        if ('' != $this->_token) {
            if (isset($this->_values[$this->_token])) {
                $values =  $this->_values[$this->_token];
            }
        }
        return $values;
    }
    
    /**
     * check csrf
     */
    public function csrf()
    {
        if (! Request::isAjax()) {
            if (! $this->_csrf->verify()) {
                die('Unauthorized access');
            }
        } elseif (! $this->_csrf->verifyAjax()) {
            die('Unauthorized access');
        }
    }
    
    /**
     * check csrf for ajax request
     */
    public function csrfAjax()
    {
        if (Request::isAjax()) {
            if (! $this->_csrf->verifyAjax()) {
                 die('Unauthorized access');
            }
        }
    }
    
    /**
     * get csrf field
     * 
     * @return string
     */
    public function getCsrf()
    {
        return $this->_csrf->getField();
    }
    
    /**
     * get current mysql date time
     * 
     * @return string
     */
    public function now($timestamp = false)
    {
        if ($timestamp) {
            return DateHelper::timestamp();
        }
        return DateHelper::now();
    }
    
    /**
     * get current mysql date
     * 
     * @return string
     */
    public function today()
    {
        return DateHelper::today();
    }
    
    /**
     * get remote address
     * 
     * @return string
     */
    public function remoteAddress()
    {
        return Server::remoteAddress();
    }
    
    /**
     * convert object to array
     * 
     * @param stdClass $object
     * @return array
     */
    public function toArray($object)
    {
        if (! is_array($object)) {
            return (array) $object;
        }
        return $object;
    }
    
    /**
     * set model for form
     * 
     * @param TableGateway
     */
    public function setModel($model)
    {
        if (! $model instanceof TableGateway) {
            throw new Exception('Unsupported model instance');
        }
        $this->_model = $model;
    }
    
    /**
     * check model for form
     * 
     * @return boolean
     */
    public function hasModel()
    {
        return null !== $this->_model;
    }
    
    /**
     * get form model instance
     * 
     * @return \Web\Database\TableGateway
     */
    public function getModel()
    {
        return $this->_model;
    }
    
    /**
     * set id
     * 
     * @param int $id
     */
    public function setId($id)
    {
        $this->_id = (int) $id;
    }

    /**
     * get id
     * 
     * @return int
     */
    public function getId()
    {
        return $this->_id ;
    }
    
    /**
     * populate by id
     */
    public function populateById()
    {
        if ($this->_id > 0) {
            $record = $this->getModel()->selectById($this->_id);
            if (! empty($record)) {
                $this->populate($record);
            }
        }
    }
    
    /**
     * save record - insert or update
     * 
     * @param array $data
     * @return int|boolean
     */
    public function save($data)
    {
        if ($this->_id > 0) {
            return $this->getModel()->updateById($data, $this->_id);
        }
        
        return $this->getModel()->insert($data);
    }
    
    /**
     * get formatter instance
     * 
     * @return \Web\Form\FormFormatter
     */
    public function getFormatter()
    {
        return $this->formatter;
    }
    
    /**
     * format input
     * 
     * @param string $method
     * @param string $input
     * @return string
     */
    public function format($method, $input)
    {
        if (method_exists($this->formatter, $method)) {
            return $this->formatter->{$method}($input);
        }
        
        return $input;
    }
    
    /**
     * register array list for validation
     * 
     * @param string $name
     * @param array $list
     */
    public function registerArray($name, $list)
    {
        $this->validator->registerArray($name, $list);
    }
    
    /**
     * extract array change
     * 
     * @param array $selected
     * @param array $existing
     * @return array
     */
    public function extractArrayChange($selected, $existing = [])
    {
        $ids = [ 'new' => [], 'removed' => [] ];
        if (empty($existing)) {
            $ids['new'] = $selected;
        } else {
            if (empty($selected)) {
                $ids['removed'] = $existing;
            } else {
                foreach ($existing as $item) {
                    if (! in_array($item, $selected)) {
                        $ids['removed'][] = $item;
                    }
                }
                foreach ($selected as $item2) {
                    if (! in_array($item2, $existing)) {
                        $ids['new'][] = $item2;
                    }
                }
            }
        }
        
        return $ids;
    }
    
    /**
     * add field title from field name
     * 
     * @param string $name
     */
    private function _addFieldTitle($name)
    {
        $field = $name;
        if (1 == preg_match('/[A-Z_-]/', $name)) {
            $name = preg_replace('/([a-z])([A-Z])/', '\\1_\\2', $name);
            $name = trim(preg_replace('/[_-]+/', '_', $name), '_');
            $temp = explode('_', $name);
            if (count($temp) > 1) {
                $words = [];
                foreach ($temp as $item) {
                    $words[] = ucfirst($item);
                }
                $title = implode(' ', $words);
            } else {
                $title = ucfirst($name);
            }
        } else {
            $title = ucfirst($name);
        }
        $this->_fieldTitles[$field] = $title;
    }

    /**
     * get post value as string
     * 
     * @param string $name
     * @return string
     */
    private function _postString($name)
    {
        return $this->input->post($name);
    }
    
    /**
     * get post value as array
     * 
     * @param string $name
     * @return array
     */
    private function _postArray($name)
    {
        return (array) $this->input->postArray($name);
    }

    /**
     * get post value as number
     * 
     * @param string $name
     * @return string
     */
    private function _postNumber($name)
    {
        $value = '0';
        if ($this->_isPost($name)) {
            $value = (string) $this->input->postId($name);
        }
        return $value;
    }
    
    /**
     * check if field is posted 
     * 
     * @param string $name
     * @return boolean
     */
    private function _isPost($name)
    {
        return $this->input->isPost($name);
    }
    
}
