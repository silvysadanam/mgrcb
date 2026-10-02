<?php
/**
 * Plugin Breadcrumb 
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;


class Breadcrumb
{
    private $_breadcrumbs = [];
    private $_heading = 'Home';
    private $_subheading = '';
    
    public function __construct()
    {
        
    }
    
    /**
     * set breadcrumbs
     * 
     * @param array $breadcrumbs
     */
    public function set($breadcrumbs)
    {
        $this->_breadcrumbs = $breadcrumbs;
    }
    
    /**
     * get breadcrumbs
     * 
     * @return array
     */
    public function get()
    {
        return $this->_breadcrumbs;
    }
    
    /**
     * add breadcrumb
     * 
     * @param string $title
     * @param string $link
     */
    public function add($title, $link = null)
    {
        if (null === $link) {
            $this->_breadcrumbs[] = $title;
        } else {
            $this->_breadcrumbs[] = sprintf('<a href="%s" title="%s">%s</a>', $link, $title, $title);
        }
    }
    
    /**
     * build breadcrumb
     * 
     * @return string
     */
    public function build()
    {
        $breadcrumbs = '';
        if (! empty($this->_breadcrumbs)) {
            $last = array_pop($this->_breadcrumbs);
            if (! empty($this->_breadcrumbs)) {
                $breadcrumbs = '<li class="breadcrumb-item">' . implode('</li>' . PHP_EOL . '<li class="breadcrumb-item">', $this->_breadcrumbs) . '</li>';
            }
            $breadcrumbs .= PHP_EOL . '<li class="breadcrumb-item active">' . $last . '</li>';
        }
        
        return $breadcrumbs;
    }
    
    /**
     * set page heading
     * 
     * @param string $heading
     */
    public function setHeading($heading)
    {
        $this->_heading = $heading;
    }
    
    /**
     * get page heading
     * 
     * @return string
     */
    public function getHeading()
    {
        return $this->_heading;
    }
    
    /**
     * set page subheading
     * 
     * @param string $heading
     */
    public function setSubheading($heading)
    {
        $this->_subheading = $heading;
    }
    
    /**
     * get page subheading
     * 
     * @return string
     */
    public function getSubheading()
    {
        return $this->_subheading;
    }
    
}
