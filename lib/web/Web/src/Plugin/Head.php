<?php
/**
 * Web Head plugin
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;


class Head
{
    const TITLE = 'title';
    const DESCRIPTION = 'description';
    const KEYWORDS = 'keywords';
    
    /**
     *
     * @var array 
     */
    private $_meta;
    
    /**
     *
     * @var string 
     */
    private $_titlePrefix;
    
    public function __construct()
    {
        $this->_meta = array (
            self::TITLE => 'Home',
            self::DESCRIPTION => '',
            self::KEYWORDS => '',
        );
    }
    
    /**
     * set title prefix
     * 
     * @param string $prefix
     */
    public function setTitlePrefix($prefix)
    {
        $this->_titlePrefix = $prefix;
    }
    
    /**
     * get meta
     * 
     * @return array
     */
    public function get()
    {
        return $this->_meta;
    }
 
    /**
     * set title
     * 
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->_addMeta(self::TITLE, $this->_titlePrefix . $title);
    }
    
    /**
     * get title
     * 
     * @return string
     */
    public function getTitle()
    {
        return $this->_meta[self::TITLE];
    }
    
    /**
     * set description
     * 
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->_addMeta(self::DESCRIPTION, $description);
    }
    
    /**
     * get description
     * 
     * @return string
     */
    public function getDescription()
    {
        return $this->_meta[self::DESCRIPTION];
    }
   
    /**
     * set keywords
     * 
     * @param string $keywords
     */
    public function setKeywords($keywords)
    {
        $this->_addMeta(self::KEYWORDS, $keywords);
    }
    
    /**
     * get keywords
     * 
     * @return string
     */
    public function getKeywords()
    {
        return $this->_meta[self::KEYWORDS];
    }
    
    
    /**
     * add meta
     * 
     * @param string $name
     * @param string $value
     */
    private function _addMeta($name, $value)
    {
        $this->_meta[$name] = $value;
    }
    
}
