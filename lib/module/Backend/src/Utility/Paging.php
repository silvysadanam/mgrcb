<?php
/**
 * Backend Paging Utility
 *
 * @category   Backend
 * @package    Backend\Utility
 */

namespace Backend\Utility;

use Web\Core\Server;


class Paging
{
    const PAGE_PLACE_HOLDER = '[PAGE]';
    const PAGE = 'page';
    const LIMIT_DEFAULT = 200;
    const LIMIT_MIN = 200;
    const LIMIT_MAX = 500;
    const NO_DOTS_LIMIT = 10;
    const ADJACENTS = 2;
    const DOTS = '...';
    
    /**
     * Current page
     *
     * @var int
     */
    private $_page = 1;
    
    /**
     * Total records
     *
     * @var int
     */
    private $_total = 0;
    
    /**
     * Records per page
     *
     * @var int
     */
    private $_limit = 200;
    
    /**
     * Number of pages
     *
     * @var int
     */
    private $_pages = 1;
    
    /**
     * Current URL - REQUEST_URI
     *
     * @var string
     */
    private $_url = '';
    
    /**
     * Link elements
     *
     * @var array
     */
    private $_links = [];
    
    /**
     * paging result
     * 
     * @var \stdClass 
     */
    private $_result;
    
    /**
     * max limit
     * 
     * @var int 
     */
    private $_maxLimit;
    
    /**
     * min limit
     * 
     * @var int 
     */
    private $_minLimit;
    
    /**
     * html templates
     * 
     * @var array 
     */
    private $_templates;
    
    /**
     *
     * @var boolean 
     */
    private $_showPreviousAndNext = true;
	
    public function __construct()
    {
        $requestUri = Server::requestUri();
        $baseUrl = $requestUri;
        if (false !== strpos($requestUri, '?')) {
            $uriParts = explode('?', $requestUri);
            $baseUrl = $uriParts[0];
        }
        $this->_url = rtrim($baseUrl, '/');
        $queryString = Server::queryString();
        if ('' != $queryString) {
            $parts = explode('&', $queryString);
            $newParams = [];
            foreach ($parts as $item) {
                $itemParts = explode('=', $item);
                if ('' != $itemParts[0] && '' != $itemParts[1]) {
                    if (self::PAGE == $itemParts[0]) {
                        $this->_page = max(1, (int) $itemParts[1]);
                    } else {
                        if (is_numeric($itemParts[1])) {
                            $newParams[] = $itemParts[0] . '=' . $itemParts[1];
                        } else {
                            $newParams[] = $itemParts[0] . '=' . rawurlencode($itemParts[1]);
                        }
                    }
                }
            }
            $newParams[] = self::PAGE . '=' . self::PAGE_PLACE_HOLDER; 
            $this->_url .= '/?' . implode('&amp;', $newParams);
        } else {
            $this->_url .= '/?' . self::PAGE . '=' . self::PAGE_PLACE_HOLDER; 
        }
        $this->_maxLimit = self::LIMIT_MAX;
        $this->_minLimit = self::LIMIT_MIN;
        $this->_initResult();
    }
    
    /**
     * Reset paging values
     */
    public function reset()
    {
        $this->_page = 1;
        $this->_total = 0;
        $this->_limit = self::LIMIT_DEFAULT;
        $this->_maxLimit = self::LIMIT_MAX;
        $this->_minLimit = self::LIMIT_MIN;
        $this->_links = [];
        $this->_initResult();
    }
    
    /**
     * Set page number
     * 
     * @param int $page
     */
    public function page($page)
    {
        $this->_page = (int) $page;
    }
    
    /**
     * Set max limit
     * 
     * @param int $limit
     */
    public function maxLimit($limit)
    {
        $this->_maxLimit = (int) $limit;
    }
    
    /**
     * Set min limit
     * 
     * @param int $limit
     */
    public function minLimit($limit)
    {
        $this->_minLimit = (int) $limit;
    }
    
    /**
     * Set total
     * 
     * @param int $total
     */
    public function total($total)
    {
        $this->_total = (int) $total;
    }
    
    /**
     * Set limit
     * 
     * @param int $limit
     */
    public function limit($limit)
    {
        $this->_limit = (int) $limit;
    }
    
    /**
     * get paging result
     * 
     * @return \stdClass
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Generate paging
     * 
     * @param int $total
     * @param int $limit
     * @param bool $generateLinks
     * @return \stdClass object
     */
    public function run($total = null, $limit = null, $generateLinks = true)
    {
        //set total
        if (null !== $total) {
            $this->total($total);
        }
        if ($this->_total < 1) {
            return $this->_result;
        }
        if (null !== $limit) {
            $this->limit($limit);
        }
        if ($this->_limit < $this->_minLimit || $this->_limit > $this->_maxLimit) {
            $this->_limit = self::LIMIT_DEFAULT;
        }
        //number of pages
        $this->_calculatePages();
        $this->_result->total = $this->_total;
        $this->_result->page = $this->_page;
        $this->_result->pages = $this->_pages;
        $this->_result->limit = $this->_limit;
        $this->_result->sqlLimit = $this->_limit;
        $this->_result->sqlOffset = $this->_getSqlOffset();
        if ($this->_result->sqlOffset > 0) {
            $this->_result->sqlLimit = $this->_result->sqlOffset . ',' . $this->_limit;
        }
        if ($generateLinks) {
            $this->_loadTemplates();
            $this->_generateLinks();
        }
        
        $this->_result->html = implode(PHP_EOL, $this->_links);
        $this->_getPagingInfo();
        
        return $this->_result;
    }
    
    /**
     * get page link
     * 
     * @param int $page
     * $return string
     */
    private function _getPageLink($page)
    {
        return str_replace(self::PAGE_PLACE_HOLDER, $page, $this->_url);
    }
    
    /**
     * get sql offset
     * 
     * @return int
     */
    private function _getSqlOffset()
    {
        return ($this->_page - 1) * $this->_limit;
    }
    
    /**
     * initialize result
     */
    private function _initResult()
    {
        $this->_result = (object) [
            'total' => 0,
            'page' => 1,
            'pages' => 1,
            'limit' => self::LIMIT_DEFAULT,
            'sqlLimit' => self::LIMIT_DEFAULT,
            'sqlOffset' => 0,
            'html' => '',
            'info' => '',
        ];
    }
    
    /**
     * calculate number of pages
     */
    private function _calculatePages()
    {
        $this->_pages = (int) ceil($this->_total / $this->_limit);
        if ($this->_page < 1 || $this->_page > $this->_pages) {
            $this->_page = 1;
        }
    }
    
    /**
     * generate links
     */
    private function _generateLinks()
    {
        $this->_links[] = $this->_templates['links']['open'];

        if ($this->_showPreviousAndNext) {
            if (1 == $this->_page) {
                $this->_links[] = $this->_templates['links']['prev_disabled'];
            } else {
                $page = $this->_page - 1;
                $this->_links[] = sprintf($this->_templates['links']['prev'], $this->_getPageLink($page));
            }
        }
 
        //sort pages in ascending oder
        $visiblePages = $this->_getVisiblePages();
        sort($visiblePages);
        $last = 1;
        foreach ($visiblePages as $page) {
            if ($page > $last + 1) {
                $this->_links[] = sprintf($this->_templates['links']['disabled'], self::DOTS);
            } 
            if ($page == $this->_page) {
                $this->_links[] = sprintf($this->_templates['links']['current'], $page, $page);
            } else {
                $this->_links[] = sprintf($this->_templates['links']['page'], $this->_getPageLink($page), $page, $page);
            }            
            $last = $page;
        }
    
        //next link
        if ($this->_showPreviousAndNext) {
            if ($this->_page == $this->_pages) {
                $this->_links[] = $this->_templates['links']['next_disabled'];
            } else {
                $page = $this->_page + 1;
                $this->_links[] = sprintf($this->_templates['links']['next'], $this->_getPageLink($page));
            }
        }

        $this->_links[] = $this->_templates['links']['close'];
    }
    
    /**
     * generate paging info
     */
    private function _getPagingInfo()
    {
        if ($this->_total > 1) {
            $entry = $this->_templates['info']['entries'];
            $start = $this->_result->sqlOffset + 1;
            $end = $this->_result->sqlOffset + $this->_limit;
            if ($end > $this->_total) {
                $end = $this->_total;
            }
            if ($start == $end) {
                $display = '%s ' . $start . ' of ' . $this->_total . '%s';
                $display = sprintf($this->_templates['info']['last'], '%s', $start, $this->_total, '%s');
            } else {
                $display = sprintf($this->_templates['info']['normal'], '%s', $start, $end, $this->_total, '%s');
            }
        } else {
            $display = $this->_templates['info']['single'];
            $entry = $this->_templates['info']['entry'];
        }
        $this->_result->info = sprintf($display, $this->_templates['info']['prefix'], $entry);
    }
    
    /**
     * load paging html templates
     */
    private function _loadTemplates()
    {
        $this->_templates = [
            'links' => [],
            'info' => [],
        ];
        $this->_templates['links']['open'] = '<ul class="pagination justify-content-center">';
        // closing tag
        $this->_templates['links']['close'] = '</ul>';
        // previous page
        $this->_templates['links']['prev'] = '<li class="page-item"><a class="page-link" href="%s" title="Previous">&larr;</a></li>';
        // disabled previous page
        $this->_templates['links']['prev_disabled'] = '<li class="page-item disabled"><a class="page-link" href="javascript:;">&larr;</a></li>';
        // disabled links
        $this->_templates['links']['disabled'] = '<li class="page-item disabled"><a class="page-link" href="javascript:;">%s</a></li>';
        // normal page link
        $this->_templates['links']['page'] = '<li class="page-item"><a class="page-link" href="%s" title="%s">%d</a></li>';
        // current page
        $this->_templates['links']['current'] = '<li class="page-item active"><a class="page-link" href="javascript:;" title="%s">%d</a></li>';
        // next page
        $this->_templates['links']['next'] = '<li class="page-item next"><a class="page-link" href="%s" title="Next">&rarr;</a></li>';
        // disabled next page
        $this->_templates['links']['next_disabled'] = '<li class="page-item disabled"><a class="page-link" href="javascript:;">&rarr;</a></li>';
        
        // Info options
        $this->_templates['info']['entry'] = 'record';
        $this->_templates['info']['entries'] = ' total records';
        $this->_templates['info']['single'] = '%s 1 of 1 %s';
        $this->_templates['info']['last'] = '%s %d of %d %s';
        $this->_templates['info']['normal'] = '%s %d to %d of %d %s';
        $this->_templates['info']['prefix'] = '';
    }
    
    /**
     * get visible pages
     * 
     * @return array
     */
    private function _getVisiblePages()
    {
        $pages = [1];
        if ($this->_pages > 1) {
            $pages[] = $this->_pages;
            if ($this->_pages > self::NO_DOTS_LIMIT) {
                $half = ceil(self::NO_DOTS_LIMIT / 2) - 1;
                $end = $half;
                for ($page = 2; $page <= $end; $page++) {
                    $pages[] = $page;
                }
                $start = $this->_pages - $half + 1;
                for ($page = $start; $page < $this->_pages; $page++) {
                    $pages[] = $page;
                }
            } else {
                for ($page = 2; $page < $this->_pages; $page++) {
                    $pages[] = $page;
                }
            }
        }
        
        return $pages;
    }
    
}
