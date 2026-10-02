<?php
/**
 * Web Date Utility
 * 
 * @category Web
 * @package Utility
 */

namespace Web\Utility;

use DateTime;
use DateTimeZone;


class DateHelper
{
    const MYSQL_DATE = 'Y-m-d';
    const MYSQL_DATETIME = 'Y-m-d H:i:s';
    const MYSQL_TIME = 'H:i:s';
    const DATEPICKER = 'd/m/Y';
    const MYSQL_DATE_NULL = '0000-00-00';
    const DATE_DAY = 'j';
    const DATE_MONTH = 'n';
    const DATE_YEAR = 'Y';
    const DATE_TIMEZONE = 'T';
    const DATE_WEEK_DAY_NUMBER = 'w';
    const DATE_WEEK_DAY_TEXT = 'l';
    const DATETIMEPICKER = 'd/m/Y h:i A';
    
    /**
     *
     * @var string 
     */
    private static $_timezone = null;
    
    /**
     * set timezone
     * 
     * @param string $timezone
     */
    public static function setTimezone($timezone)
    {
        self::$_timezone = $timezone;
    }
    
    /**
     * clear timezone
     */
    public static function clearTimezone()
    {
        self::$_timezone = null;
    }
    
    /**
     * get timezone
     * 
     * @return string
     */
    public static function getTimezone()
    {
        return self::$_timezone;
    }
    
    /**
     * get unix timestamp
     * 
     * @param mixed $time
     * @return int
     */
    public static function timestamp($time = null)
    {
        $date = self::getDateTime($time);
        
        return $date->getTimestamp();
    }
    
    /**
     * get mysql date
     * 
     * @return string
     */
    public static function today()
    {
        return self::format(self::MYSQL_DATE);
    }
    
    /**
     * get mysql datetime
     * 
     * @return string
     */
    public static function now()
    {
        return self::format(self::MYSQL_DATETIME);
    }
    
    /**
     * get timezone abbreviation
     * 
     * @return string
     */
    public static function getTimezoneAbbreviation()
    {
        return self::format(self::DATE_TIMEZONE);
    }
    
    /**
     * format datetime
     * 
     * @param string $format
     * @param mixed $time
     * @return string
     */
    public static function format($format, $time = null)
    {
        $date = self::getDateTime($time);
        
        return $date->format($format);
    }
    
    /**
     * convert to another timezone
     * 
     * @param string $timezone
     * @param string $format
     * @param mixed $time
     * @return \DateTime | string
     */
    public static function toTimezone($timezone, $format = null, $time = null)
    {
        $date = self::getDateTime($time, $timezone);
        if (null !== $format) {
            return $date->format($format);
        }
        
        return $date;
    }
    
    /**
     * get date in datepicker format
     * 
     * @param mixed $time
     * @return string
     */
    public static function datepicker($time = null)
    {
        $date = self::getDateTime($time);
        
        return $date->format(self::DATEPICKER);
    }
    
    /**
     * convert from datepicker format
     * 
     * @param string $time
     * @return string
     */
    public static function fromDatepicker($time)
    {
        return self::convert($time, self::MYSQL_DATE, self::DATEPICKER);
    }
    
    /**
     * convert from datetimepicker format
     * 
     * @param string $time
     * @param boolean $timestamp
     * @return string
     */
    public static function fromDatetimepicker($time, $timestamp = true)
    {
        $datetime = DateTime::createFromFormat(self::DATETIMEPICKER, $time);
        if (false === $datetime) {
            return null;
        }
        if ($timestamp) {
            return $datetime->getTimestamp();
        }
        
        return $datetime->format(self::MYSQL_DATETIME);
    }
    
    /**
     * convert format
     * 
     * @param mixed $time
     * @param string $to
     * @param string $from
     * @return string
     */
    public static function convert($time, $to, $from = null)
    {
        if (null !== $from) {
            $date = DateTime::createFromFormat($from, $time);
            if (false === $date) {
                return null;
            }
        } else {
            $date = self::getDateTime($time);
        }
        
        return $date->format($to);
    }
    
    /**
     * modify time
     * 
     * @param string $format
     * @param mixed $time
     * @return \DateTime
     */
    public static function modify($format, $time = null)
    {
        $date = self::getDateTime($time);
        
        return $date->modify($format);
    }
    
    /**
     * check format
     * 
     * @param mixed $time
     * @param string $format
     * @return boolean
     */
    public static function checkFormat($time, $format)
    {
        return false !== DateTime::createFromFormat($format, $time);
    }
    
    /**
     * load from format
     * 
     * @param string $time
     * @param string $format
     * @return \DateTime
     */
    public static function fromFormat($time, $format)
    {
        return DateTime::createFromFormat($format, $time);
    }
    
    /**
     * get numeric day from time
     * 
     * @param mixed $time
     * @return int
     */
    public static function day($time = null)
    {
        return (int) self::format(self::DATE_DAY, $time);
    }
    
    /**
     * get numeric month
     * 
     * @param mixed $time
     * @return int
     */
    public static function month($time = null)
    {
        return (int) self::format(self::DATE_MONTH, $time);
    }
    
    /**
     * get 4 digit year
     * 
     * @param mixed $time
     * @return int
     */
    public static function year($time = null)
    {
        return (int) self::format(self::DATE_YEAR, $time);
    }
    
    /**
     * get numeric day of the week
     * 
     * @param mixed $time
     * @return int
     */
    public static function weekDayNumber($time = null)
    {
        return self::format(self::DATE_WEEK_DAY_NUMBER, $time);
    }
    
    /**
     * get day of week as text
     * 
     * @param mixed $time
     * @return string
     */
    public static function weekDayText($time = null)
    {
        return self::format(self::DATE_WEEK_DAY_TEXT, $time);
    }
    
    /**
     * check if value is date
     * 
     * @param string $value
     * @return boolean
     */
    public static function isDate($value)
    {
        if (empty($value)) {
            return false;
        }
        
        return $value != self::MYSQL_DATE_NULL;
    }

    /**
     * get DateTime object
     * 
     * @param string|int|null $time
     * @param string $timezone
     * @return \DateTime
     */
    public static function getDateTime($time = null, $timezone = null)
    {
        if (null === $time) {
            $dateTime = new DateTime();
        } else {
            if (is_numeric($time)) {
                $dateTime = new DateTime();
                $dateTime->setTimestamp($time);
            } else {
                $dateTime = new DateTime($time);
            }
        }
        if (null !== $timezone) {
            if (false !== $timezone) {
                $dateTime->setTimezone(new DateTimeZone($timezone));
            }
        } elseif (null !== self::$_timezone) {
            $dateTime->setTimezone(new DateTimeZone(self::$_timezone));
        }
        
        return $dateTime;
    }
    
    /**
     * check if date is today
     * 
     * @param string $date
     * @return boolean
     */
    public static function isToday($date)
    {
        $now = new DateTime();
        
        return $date == $now->format(self::MYSQL_DATE);
    }
    
    /**
     * check if past date
     * 
     * @param string $date
     * @return boolean
     */
    public static function isPastDate($date)
    {
        $today = new DateTime(self::today());
        $dateTime = new DateTime($date);
        
        return $dateTime->getTimestamp() < $today->getTimestamp();
    }
    
    /**
     * check if future date
     * 
     * @param string $date
     * @return boolean
     */
    public static function isFutureDate($date)
    {
        $today = new DateTime(self::today());
        $dateTime = new DateTime($date);
        
        return $dateTime->getTimestamp() > $today->getTimestamp();
    }
    
    /**
     * get current time
     * 
     * @return string
     */
    public static function currentTime()
    {
        $now = new DateTime();
        
        return $now->format(self::MYSQL_TIME);
    }
    
}
