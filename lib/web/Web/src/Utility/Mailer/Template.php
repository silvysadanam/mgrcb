<?php
/**
 * Web Mailer Template Utility
 * 
 * @category   Web
 * @package    Utility
 * @subpackage Mailer
 */

namespace Web\Utility\Mailer;

use Web\Core\Registry;
use DateTime;


class Template
{
    const NONE = 0;
    const USER = 1;
    const ADMIN = 2;
    const CONTENT_PLACEHOLDER = '[MAIL_CONTENT]';
    
    /**
     *
     * @var string 
     */
    private $_template;
    
    /**
     *
     * @var string 
     */
    private $_siteUrl;
    
    /**
     *
     * @var string 
     */
    private $_siteName;
    
    /**
     *
     * @var string 
     */
    private $_imageUrl;
    
    public function __construct()
    {
        $this->_siteUrl = Registry::get('siteUrl');
        $this->_imageUrl = $this->_siteUrl . 'media/images/';
        $this->_siteName = 'RSBBuildingRatingScheme';
    }
    
    /**
     * set template
     * 
     * @param int $template
     */
    public function setTemplate($template)
    {
        $this->_template = $template;
    }
    
    /**
     * get mail message
     * 
     * @param string $message
     * @return string
     */
    public function getMailMessage($message)
    {
        switch($this->_template) {
            case self::USER;
                $mailMessage = $this->_getUserMessage($message);
                break;
            
            case self::ADMIN;
                $mailMessage = $this->_getAdminMessage($message);
                break;
            
            default:
                $mailMessage = $message;
                break;
        }
        
        return $mailMessage;
    }
    
    
    /**
     * get USER message
     * 
     * @param string $message
     * @return string
     */
    private function _getUserMessage($message)
    {
        $template = $this->_loadUserTemplate();
        return str_replace(self::CONTENT_PLACEHOLDER, $message, $template);
    }
    
    /**
     * get ADMIN message
     * 
     * @param string $message
     * @return string
     */
    private function _getAdminMessage($message)
    {
        $template = $this->_loadAdminTemplate();
        return str_replace(self::CONTENT_PLACEHOLDER, $message, $template);
    }
    
    /**
     * load USER template
     * 
     * @return string
     */
    private function _loadUserTemplate()
    {
        $today = new DateTime();
        $year = $today->format('Y');
        
        $template = <<<EOT
<html>
<body style="margin:0; padding: 0; background: #ccdbec;">
<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr><td valign="top">
<table border="0" cellspacing="0" cellpadding="0" align="center" width="100%">
<tr><td valign="top" style="padding-top: 40px; padding-right: 24px; padding-bottom: 80px; padding-left: 24px;">
<table border="0" cellspacing="0" cellpadding="0" width="100%" align="center">
<tr><td valign="top" align="left">
<a href="{$this->_siteUrl}" target="_blank" style="text-decoration: none; outline: none;color: #00adee; font-family:Arial, sans-serif; font-size: 27px; font-weight: bold;  mso-line-height-rule:exactly;" title="{$this->_siteName}">RSBBUILDINGRATINGSCHEME<span style="color: #233f72">.</span>ORG</a>
</td></tr>

[MAIL_CONTENT]

</table></td></tr><tr>
<td style="padding-top: 18px; padding-bottom: 18px; padding-left: 24px; padding-right: 24px; border-top: 1px solid #e5e5e5; font-family: Arial, sans-serif; font-size: 10px;line-height: 12px;mso-line-height-rule:exactly; color: #444342;">
rsbbuildingratingscheme.org | Copyright &copy; {$year} RSB BRS. All rights reserved.
</td></tr></table></td></tr></table>
</body>
</html>
      
EOT;

        return $template;
    }
    
    /**
     * load ADMIN template
     * 
     * @return string
     */
    private function _loadAdminTemplate()
    {
        $template = <<<EOT
<html>
<body style="margin:0; padding: 0; background: #ccdbec;">
<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr><td valign="top">

[MAIL_CONTENT]

</td></tr></table></body></html>
         
EOT;

        return $template;
    }

}
