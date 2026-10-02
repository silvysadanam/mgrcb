<?php
/**
 * Web Mailer Utility
 * 
 * @category Web
 * @package Utility
 */

namespace Web\Utility;

use Web\Utility\Mailer\Template;
use Web\Core\Registry;
use Web\Exception;
use PHPMailer\PHPMailer;
use Web\Utility\HttpClient;


class Mailer
{
    const TRANSPORT_PHP = 'php';
    const TRANSPORT_MAIL = 'mail';
    const TRANSPORT_GMAIL = 'gmail';
    const TRANSPORT_SMTP = 'smtp';
    const TRANSPORT_REMOTE = 'remote';
    const SEND_MODE_SEND = 1;
    const SEND_MODE_DISCARD = 2;
    const SEND_MODE_PRINT = 3;
    const SEND_MODE_DEBUG = 4;
    const SEND_MODE_TEST = 5;
    const SMTP_DEBUG = false;
    
    /**
     *
     * @var array 
     */
    private $_config;
    
    /**
     *
     * @var string 
     */
    private $_transport;
    
    /**
     *
     * @var int 
     */
    private $_template;
    
    /**
     *
     * @var int 
     */
    private $_sendMode;
    
    /**
     *
     * @var string 
     */
    private $_subject;
    
    /**
     *
     * @var string 
     */
    private $_message;
    
    /**
     *
     * @var string 
     */
    private $_from;
    
    /**
     *
     * @var array 
     */
    private $_recipients = [];
    
    /**
     *
     * @var boolean 
     */
    private $_singleTo = false;
    
    /**
     * 
     * @param array $config
     */
    public function __construct($config = null)
    {
        if (null !== $config) {
            $this->_config = $config;
        } else {
            $this->_config = Registry::get('mail');
        }
        if (isset($this->_config['transport'])) {
            $this->_transport = $this->_config['transport'];
        }
        if (isset($this->_config['from'])) {
            $this->_from = explode(',', $this->_config['from']);
        }
        if (isset($this->_config['send'])) {
            $this->_sendMode = $this->_config['send'];
        }
        $this->_template = Template::USER;
    }
    
    /**
     * set subject
     * 
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;
    }
    
    /**
     * set recipients
     * 
     * @param array $recipients
     */
    public function setRecipients($recipients)
    {
        $this->_recipients = $recipients;
    }
    
    /**
     * add recipient
     * 
     * @param string $recipient
     */
    public function addRecipient($recipient)
    {
        if (! is_array($this->_recipients)) {
            $this->_recipients = (array) $this->_recipients;
        }
        $this->_recipients[] = $recipient;
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
     * set message
     * 
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->_message = $message;
    }
    
    /**
     * set transport
     * 
     * @param string $transport
     */
    public function setTransport($transport)
    {
        $this->_transport = $transport;
    }
    
    /**
     * set from
     * 
     * @param string|array $from
     */
    public function setFrom($from)
    {
        $this->_from = $from;
    }
    
    /**
     * set send mode
     * 
     * @param int $mode
     */
    public function setSendMode($mode)
    {
        $this->_sendMode = $mode;
    }
    
    /**
     * set single to
     * 
     * @param boolean $singleTo
     */
    public function setSingleTo($singleTo = true)
    {
        $this->_singleTo = $singleTo;
    }
    
    /**
     * clear recipients
     */
    public function clearRecipients()
    {
        $this->_recipients = [];
    }
    
    /**
     * send mail
     * 
     * @param array|string $to
     * @param string $subject
     * @param string $message
     * @param string|array $from
     * @throws Exception
     */
    public function send($to = null, $subject = null, $message = null, $from = null)
    {
        if (null !== $to) {
            $this->setRecipients($to);
        }
        if (null !== $subject) {
            $this->setSubject($subject);
        }
        if (null !== $message) {
            $this->setMessage($message);
        }
        if (null !== $from) {
            $this->setFrom($from);
        }
        if (empty($this->_recipients) || empty($this->_subject) || empty($this->_message)) {
            throw new Exception('Required mail parameters missing');
        }
        if (! is_array($this->_recipients)) {
            $recipients = [$this->_recipients];
        } else {
            $recipients = array_unique($this->_recipients);
        }
        $this->_recipients = $recipients;
        $template = new Template();
        $template->setTemplate($this->_template);
        $messageBody = $template->getMailMessage($this->_message);

        if (self::SEND_MODE_PRINT == $this->_sendMode) {
            echo $messageBody;
        } elseif  (self::SEND_MODE_DEBUG == $this->_sendMode) {
            $this->_debug($messageBody);
        } else {
            switch ($this->_transport) {
                case self::TRANSPORT_MAIL:
                    $this->_mail($messageBody);
                    break;

                case self::TRANSPORT_GMAIL:
                    $this->_gmail($messageBody);
                    break;

                case self::TRANSPORT_SMTP:
                    $this->_smtp($messageBody);
                    break;
                    
                case self::TRANSPORT_REMOTE:
                    $this->_remote($messageBody);
                    break;

                default:
                    $this->_phpMail($messageBody);
                    break;
            }
        }
    }

    
    /**
     * native php mail()
     * 
     * @param string $message
     */
    private function _phpMail($message)
    {
        $headerOptions = [
            'MIME-Version:1.0',
            'Content-Type: text/html; charset="UTF-8"',
            'From: ' . $this->_formatFrom(),
        ];
        $sender = null;
        if (isset($this->_config['sender'])) {
            $sender = sprintf('-f%s', $this->_config['sender']);
        }
        $headers = implode(PHP_EOL, $headerOptions) . PHP_EOL . PHP_EOL;
        try {
            if (self::SEND_MODE_SEND == $this->_sendMode) {
                if ($this->_singleTo) {
                    foreach ($this->_recipients as $to) {
                        mail($to, $this->_subject, $message, $headers, $sender);
                    }
                } else {
                    mail(implode(',',  $this->_recipients), $this->_subject, $message, $headers, $sender);
                }
            } elseif (self::SEND_MODE_TEST == $this->_sendMode) {
                if (! empty($this->_config['test'])) {
                    mail($this->_config['test'], $this->_subject, $message, $headers, $sender);
                }
            }
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    /**
     * mail() with PHPMailer
     * 
     * @param string $messageBody
     */
    private function _mail($messageBody)
    {
        $mailer = new PHPMailer();
        $mailer->isMail();
        $mailer->isHTML();
        $mailer->Subject = $this->_subject;
        $mailer->setFrom($this->_from[1], $this->_from[0], false);
        if ($this->_singleTo) {
            $mailer->SingleTo = true;
        }
        $mailer->msgHTML($messageBody);
        if (isset($this->_config['sender'])) {
            $mailer->Sender = $this->_config['sender'];
        } else {
            $mailer->Sender = 'rsbescoadmin@demowebsite.org';
        }
        if (self::SEND_MODE_SEND == $this->_sendMode) {
            foreach ($this->_recipients as $to) {
                $mailer->addAddress($to);
            }
            $mailer->send();
        } elseif (self::SEND_MODE_TEST == $this->_sendMode) {
            if (! empty($this->_config['test'])) {
                $mailer->addAddress($this->_config['test']);
                $mailer->send();
            }
        }
        $mailer->clearAllRecipients();
    }
    
    /**
     * gmail smtp
     * 
     * @param string $messageBody
     */
    private function _gmail($messageBody)
    {
        $mailer = new PHPMailer();
        $mailer->isHTML();
        $mailer->isSMTP();
        $mailer->Host = 'smtp.gmail.com';
        $mailer->Port = 587;
        $mailer->SMTPSecure = 'tls';
        $mailer->SMTPAuth = true;
        if (true === self::SMTP_DEBUG) {
            $mailer->SMTPDebug = 2;
        }
        $mailer->Username = $this->_config['user'];
        $mailer->Password = $this->_config['password'];
        $mailer->setFrom($this->_config['user'], $this->_from[0], false);
        $mailer->Subject = $this->_subject;
        $mailer->msgHTML($messageBody);
        if ($this->_singleTo) {
            $mailer->SingleTo = true;
        }
        if (self::SEND_MODE_SEND == $this->_sendMode) {
            foreach ($this->_recipients as $to) {
                $mailer->addAddress($to);
            }
            $mailer->send();
        } elseif (self::SEND_MODE_TEST == $this->_sendMode) {
            if (! empty($this->_config['test'])) {
                $mailer->addAddress($this->_config['test']);
                $mailer->send();
            }
        }
        $mailer->clearAllRecipients();
    }
    
    /**
     * smtp
     * 
     * @param string $messageBody
     */
    private function _smtp($messageBody)
    {
        $mailer = new PHPMailer();
        $mailer->isHTML();
        $mailer->isSMTP();
        $mailer->Host = $this->_config['host'];
        $mailer->Port = $this->_config['port'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $this->_config['user'];
        $mailer->Password = $this->_config['password'];
        $mailer->setFrom($this->_config['username'], $this->_from[0]);
        $mailer->Subject = $this->_subject;
        $mailer->msgHTML($messageBody);
        if ($this->_singleTo) {
            $mailer->SingleTo = true;
        }
        if (self::SEND_MODE_SEND == $this->_sendMode) {
            foreach ($this->_recipients as $to) {
                $mailer->addAddress($to);
            }
            $mailer->send();
        } elseif (self::SEND_MODE_TEST == $this->_sendMode) {
            if (! empty($this->_config['test'])) {
                $mailer->addAddress($this->_config['test']);
                $mailer->send();
            }
        }
        $mailer->clearAllRecipients();
    }
    
    /**
     * remote API
     * 
     * @param string $messageBody
     * @return boolean
     */
    private function _remote($messageBody)
    {
        if (! isset($this->_config['remoteUrl'])) {
            return false;
        }
        $data = [
            'from' => 'Tenderstream',
            'to' => implode(',', $this->_recipients),
            'subject' => $this->_subject,
            'message' => $messageBody,
        ];
        $client = new HttpClient();
        
        return $client->post($this->_config['remoteUrl'], $data);
    }
    
    /**
     * debug
     * 
     * @param string $message
     */
    private function _debug($message)
    {
        echo '<table><tr><td>From:</td>' . $this->_escapeHtml($this->_formatFrom()) . '<td></tr>';
        echo '<tr><td>To:</td><pre>';
        var_export($this->_recipients);
        echo '</pre><td></tr>';
        echo '<tr><td>Subject:</td>' . $this->_escapeHtml($this->_subject) . '<td></tr>';
        echo '<tr><td>Message:</td><code>' . $this->_escapeHtml($message) . '</code><td></tr></table>';
    }
    
    /**
     * escape html
     * 
     * @param string $string
     * @return string
     */
    private function _escapeHtml($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', true);
    }
    
    /**
     * format from address
     * 
     * @return string
     */
    private function _formatFrom()
    {
        $from = $this->_from;
        if (is_array($this->_from)) {
            $from = $this->_from[0];
            if (isset($this->_from[1])) {
                if ($this->_isEmail($this->_from[1])) {
                    $from = sprintf('%s <%s>', $this->_from[0], $this->_from[1]);
                } elseif ($this->_isEmail($this->_from[0])) {
                    $from = sprintf('%s <%s>', $this->_from[1], $this->_from[0]);
                }
            }
        }
        
        return $from;
    }
    
    /**
     * check email address
     * 
     * @param string $string
     * @return boolean
     */
    private function _isEmail($string)
    {
        return $string === filter_var($string, FILTER_VALIDATE_EMAIL);
    }
    
}
