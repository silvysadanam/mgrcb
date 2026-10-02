<?php
/**
 * Cli App Index controller
 * 
 * @category App
 * @package  App\Controller
 */

namespace App\Controller;

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
        $this->out('Success');
        
        $this->finish();
    }
    
}