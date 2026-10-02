<?php
/**
 * Cli Index controller
 * 
 * @category Cli
 * @package Cli\Controller
 */

namespace Cli\Controller;

use Web\Cli\CliController;


class IndexController extends CliController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function indexAction()
    {
        $this->start('Index Controller');
        
        // Do the stuff
        
        $this->finish();
    }
    
}
