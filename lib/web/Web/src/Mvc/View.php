<?php
/**
 * Web Mvc View Singleton
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\View\Template;
use Web\View\ViewModel;


class View
{
    const RENDERER_HTML = 0;
    const RENDERER_JSON = 1;
    const RENDERER_CSV = 2;
    const RENDERER_XML = 3;
    
    /**
     * Singleton class instance
     * 
     * @var \Web\Core\View 
     */
    private static $instance = null;
    
    /**
     * layout name
     * 
     * @var string 
     */
    private $_layout = '';
    
    /**
     * script name
     * 
     * @var string 
     */
    private $_script = '';
    
    /**
     * rendered status
     * 
     * @var boolean 
     */
    private $_rendered = false;
    
    /**
     * ViewModel instance
     * 
     * @var \Core\ViewModel 
     */
    private $view;
    
    /**
     *
     * @var \Web\View\Template
     */
    private $_template;
    
    /**
     *
     * @var array 
     */
    private $_blocks = [];
    
    /**
     *
     * @var array 
     */
    private $_scriptBlocks = [];
    
    /**
     *
     * @var array 
     */
    private $_modals = [];
    
    /**
     *
     * @var array 
     */
    private $_headBlocks = [];
        
    /**
     * Protected constructor to prevent creating a new instance of the
     * Class via the `new` operator from outside of this class.
     */
    protected function __construct()
    {

    }

    /**
     * get class singleton instance 
     * 
     * @return \Web\Core\View 
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
       
    /**
     * send response
     * 
     * @param \Web\View\ViewModel $viewModel
     */
    public function sendResponse(ViewModel $viewModel)
    {
        $renderer = $viewModel->getRenderer();
        switch ($renderer) {
            case self::RENDERER_JSON:
                header('Content-Type:application/json;charset="UTF-8"');
                echo json_encode($viewModel->getResponse());
                break;
            
            case self::RENDERER_CSV:
                header('Content-Type:text/csv;charset="UTF-8"');
                echo $viewModel->getResponse();
                break;
            
            case self::RENDERER_XML:
                header('Content-Type:text/xml;charset="UTF-8"');
                echo $viewModel->getResponse();
                break;
            
            default:
                echo $viewModel->getResponse();
                break;
        }
        
        exit;
    }
         
    /**
     * set layout 
     * 
     * @param string $layout 
     */
    public function setLayout($layout = '')
    {
        $this->_layout = $layout;
    }
    
    /**
     * render layout file 
     * 
     * @param \Web\Core\ViewModel $viewModel 
     */
    public function layout(ViewModel $viewModel)
    {
        if (! $this->_rendered) {
            if ($viewModel instanceof ViewModel) {
                $this->view = $viewModel;
            } else {
                $this->view = new ViewModel();
            }
            if ('' != $this->_layout) {
                $this->_include($this->_template->layout($this->_layout));
            } else {
                $this->content();
            }
            $this->_rendered = true;
        }
    }
    
    /**
     * render content
     */
    public function content()
    {
        if ('' != $this->_script) {
            $this->_render($this->_script);
        }
    }
    
    /**
     * set script name 
     * 
     * @param string $script
     */
    public function setScript($script)
    {
        $this->_script = $script;
    }
    
    /**
     * set no script
     */
    public function noScript()
    {
        $this->_script = '';
    }

    /**
     * render script file 
     * 
     * @param string $script
     */
    public function renderScript($script)
    {
        $this->_render($script);
    }
    
    /**
     * render partial script
     * 
     * @param string $path
     * @param string $template
     */
    public function renderPartial($path, $template = null)
    {
        $partial = trim($path, '/');
        if (null !== $template) {
            if (0 === strpos($template, 'partial-')) {
                $partial .= '/' . $template;
            } else {
                $partial .= '/partial-' . $template;
            }
        }
        $this->_render($partial);
    }
    
    /**
     * render block file 
     * 
     * @param string $name 
     */
    public function block($name)
    {
        $this->_include($this->_template->block($name));
    }

    /**
     * add block
     * 
     * @param string $name
     */
    public function addBlock($name)
    {
        if (! in_array($name, $this->_blocks)) {
            $this->_blocks[] = $name;
        }
    }
    
    /**
     * add script block
     * 
     * @param string $name
     */
    public function addScriptBlock($name)
    {
        if (! in_array($name, $this->_scriptBlocks)) {
            $this->_scriptBlocks[] = $name;
        }
    }
    
    /**
     * add head block
     * 
     * @param string $name
     */
    public function addHeadBlock($name)
    {
        if (! in_array($name, $this->_headBlocks)) {
            $this->_headBlocks[] = $name;
        }
    }
    
    /**
     * add modal
     * 
     * @param string $name
     */
    public function addModal($name)
    {
        $this->_modals[] = $name;
    }
    
    /**
     * render blocks
     */
    public function blocks()
    {
        if (! empty($this->_blocks)) {
            foreach ($this->_blocks as $name) {
                $this->block($name);
            }
        }
        
        if (! empty($this->_modals)) {
            foreach ($this->_modals as $name) {
                if (false === strpos($name, '-modal')) {
                    $template = $name . '-modal';
                } else {
                    $template = $name;
                }
                $this->_render($template);
            }
        }
    }
    
    /**
     * render script blocks
     */
    public function scriptBlocks()
    {
        if (! empty($this->_scriptBlocks)) {
            foreach ($this->_scriptBlocks as $name) {
                $this->block($name);
            }
        }
    }
    
    /**
     * render head blocks
     */
    public function headBlocks()
    {
        if (! empty($this->_headBlocks)) {
            foreach ($this->_headBlocks as $name) {
                $this->block($name);
            }
        }
    }
    
    /**
     * render portlet
     * 
     * @param string $name
     */
    public function portlet($name)
    {
        $this->_include($this->_template->portlet($name));
    }
    
    /**
     * get form instance
     * 
     * @return \Web\Core\Form
     */
    public function getForm()
    {
        return $this->view->getVariable('form');
    }
    
    /**
     * load template helper
     */
    public function loadTemplateHelper()
    {
        $this->_template = new Template();
    }
    

    /**
     * render template by including it 
     * 
     * @param string $template 
     */
    private function _render($template)
    {
        $this->_include($this->_template->script($template));
    }
    
    /**
     * include view file
     * 
     * @param string|boolean $file
     */
    private function _include($file)
    {
        if (false !== $file) {
            include $file;
        }
    }
    
    /**
     * Private clone method to prevent cloning of the instance
     *
     * @return void
     */
    private function __clone()
    {
    }

    
}
