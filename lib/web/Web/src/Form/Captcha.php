<?php
/**
 * Form Captcha
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Core\Input;


class Captcha
{
    const CAPTCHA_SALT = 'xGhj^jdhKl4zP1=@U?pa~X93_qW$Lwe!';
    const CHALLENGE = 'challenge';
    const VERIFICATION = 'verification';
    const EXPIRY = 600;
    const SEPARATOR = ':';
    
    /**
     *
     * @var string 
     */
    private $_expression;
    
    /**
     *
     * @var string 
     */
    private $_challenge;
    
    /**
     *
     * @var \Web\Core\Input 
     */
    private $_input;
    
    /**
     *
     * @var array 
     */
    private $_fields;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->_input = new Input();
        $this->_fields = [
            self::CHALLENGE => 'loginchallenge',
            self::VERIFICATION => 'loginverification',
        ];
        $this->_loadCaptcha();
    }
    
    /**
     * set challenge field
     * 
     * @param string $field
     */
    public function setChallengeField($field)
    {
        $this->_fields[self::CHALLENGE] = $field;
    }
    
    /**
     * set verification field
     * 
     * @param string $field
     */
    public function setVerificationField($field)
    {
        $this->_fields[self::VERIFICATION] = $field;
    }
    
    /**
     * get verification field
     * 
     * @return string
     */
    public function getVerificationField()
    {
        return $this->_fields[self::VERIFICATION];
    }
    
    /**
     * get challenge field
     * 
     * @return string
     */
    public function getChallengeField()
    {
        return $this->_fields[self::CHALLENGE];
    }

    /**
     * get expression
     * 
     * @return string
     */
    public function getExpression()
    {
        return $this->_expression;
    }
    
    /**
     * get challenge
     * 
     * @return string
     */
    public function getChallenge()
    {
        return sprintf('<input type="hidden" name="%s" value="%s">', $this->_fields[self::CHALLENGE], $this->_challenge);
    }
    
    /**
     * get challenge value
     * 
     * @return string
     */
    public function getChallengeValue()
    {
        return $this->_challenge;
    }
    
    /**
     * get captcha options
     * 
     * @return array
     */
    public function getCaptcha()
    {
        $challenge = $this->getChallenge();
        
        return [
            'challenge' => $challenge,
            'expression' => $this->_expression,
        ];
    }
    
    /**
     * validate captcha
     * 
     * @return boolean
     */
    public function isValid($challenge = null, $verification = null)
    {
        if (empty($challenge)) {
            $challenge = $this->_fields[self::CHALLENGE];
        }
        if (empty($verification)) {
            $verification = $this->_fields[self::VERIFICATION];
        }
        $valid = false;
        $code = $this->_input->post($challenge);
        if (strlen($code) > 33) {
            if (1 == preg_match('/^[a-f0-9][a-f0-9]+' . quotemeta(self::SEPARATOR) . '\d+$/', $code)) {
                $decoded = explode(self::SEPARATOR, $code);
                if (2 == count($decoded)) {
                    if (32 == strlen($decoded[0]) && is_numeric($decoded[1])) {
                        if ($decoded[1] > time()) {
                            $input = $this->_input->post($verification);
                            if (2 == strlen($input)) {
                                if (1 == preg_match('/^\d\d$/', $input)) {
                                    $valid = 0 === strcmp($this->_createChallenge($input, $decoded[1]), $code);
                                }
                            } 
                        }
                    }
                }
            }
        }
        
        return $valid;
    }
    
    
    /**
     * load captcha
     */
    private function _loadCaptcha()
    {
        $number1 = rand(10, 49);
        $number2 = rand(10, 49);
        $this->_challenge = $this->_createChallenge($number1 + $number2);
        $characters = str_split(sprintf('%s + %s =', $number1, $number2), 1);
        $this->_expression = '';
        foreach ($characters as $item) {
            $this->_expression .= '&#' . ord($item) . ';';
        }
    }
    
    /**
     * create challenge
     * 
     * @param string $input
     * @return string
     */
    private function _createChallenge($input, $time = null)
    {
        if (null === $time) {
            $time = time() + self::EXPIRY;
        }
        
        return md5($input . self::CAPTCHA_SALT . $time) . self::SEPARATOR . $time;
    }
    
}
