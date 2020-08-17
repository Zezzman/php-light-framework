<?php
namespace System\Helpers;

/**
 * 
 */
final class TimeHelper
{
    protected $datePhase = null;
    protected $dateString = null;
    protected $microtime = null;

    public function __toString()
    {
        return $this->format();
    }
    public function __construct(string $dateString = null, string $format = null)
    {
        if (is_null($dateString) || empty($dateString)) {
            $this->dateString = date_create();
        } else {
            if (is_null($format)) {
                $this->dateString = date_create($dateString);
            } else {
                $this->dateString = date_create_from_format($format, $dateString);
            }
        }
        if (! ($this->dateString)) {
            $this->dateString = false;
        } else {
            $this->datePhase = $this->dateString->format('a');
        }
    }
    /**
     * Create date instance
     * 
     * @param       string      $dateString       time string
     * 
     * @return      self        date instance
     */
    public static function create(string $dateString = null, string $format = null)
    {
        return new self($dateString, $format);
    }
    /**
     * Create date instance with elapsed time set
     * 
     * @return      self        date instance
     */
    public static function createMicro()
    {
        $timer = self::create();
        $timer->microtime = microtime(true);
        return $timer;
    }
    /**
     * time elapse
     * 
     * @return      self        date instance
     */
    public function elapse(bool $reset = false)
    {
        if (! is_numeric($this->microtime))
        {
            $this->microtime = microtime(true);
        }

        $elapseTime = (microtime(true) - $this->microtime);
        if($reset){
            $this->microtime = microtime(true);
        }
        return $elapseTime;
    }
    /**
     * DateTime instance
     * 
     * @return   DateTime     DateTime instance
     */
    public function dateTime()
    {
        return $this->dateString;
    }
    /**
     * Format time based on dateString variable
     * 
     * @param   string      $format          format of time
     * 
     * @return   string     formated time
     */
    public function format(string $format = 'Y-m-d H:i:s.u')
    {
        if ($this->dateString) {
            return $this->dateString->format($format);
        }
        return '';
    }
    /**
     * Add an amount days/weeks/months/years to date instance
     * 
     * @param       int         $amount         amount to add
     * @param       string      $type           type of amount
     * 
     * @return      self        date instance
     */
    public function add(int $amount, string $type = 'days')
    {
        $interval = '';
        if ($type === 'days') {
            $interval = 'P'. abs($amount). 'D';
        } elseif ($type === 'months') {
            $interval = 'P'. abs($amount). 'M';
        } elseif ($type === 'years') {
            $interval = 'P'. abs($amount). 'Y';
        } elseif ($type === 'weeks') {
            $interval = 'P'. abs($amount). 'W';
        }
        if ($interval !== '') {
            $this->dateString->add(new \DateInterval($interval));
        }
        return $this;
    }
    /**
     * Subtract an amount days/weeks/months/years to date instance
     * 
     * @param       int         $amount         amount to add
     * @param       string      $type           type of amount
     * 
     * @return      self        date instance
     */
    public function subtract(int $amount, string $type = 'days')
    {
        $interval = '';
        if ($type === 'days') {
            $interval = 'P'. abs($amount). 'D';
        } elseif ($type === 'months') {
            $interval = 'P'. abs($amount). 'M';
        } elseif ($type === 'years') {
            $interval = 'P'. abs($amount). 'Y';
        } elseif ($type === 'weeks') {
            $interval = 'P'. abs($amount). 'W';
        }
        if ($interval !== '') {
            $this->dateString->sub(new \DateInterval($interval));
        }
        return $this;
    }
    /**
     * Set date instance timezone
     * 
     * @param       string      $timezone       DateTime Timezone
     * 
     * @return      bool        true if timezone is set
     */
    public function setTimezone(string $timezone)
    {
        if ($this->dateString) {
            try{
                $timezone = new \DateTimeZone($timezone);
                if ($timezone) {
                    $this->dateString->setTimezone($timezone);
                    $this->datePhase = $this->format('a');
                }
                return true;
            } catch (Exception $e) {
                throw new Exception("Timezone Set Error: (". $e. ")");
            }
        }
    }
    /**
     * Difference between two DateTime objects
     * 
     * @param       DateTime        $date             time string
     * 
     * @return      date_diff      true if given time is larger than time instance
     */
    public function diff($date = null)
    {
        if (empty($this->dateString)) {
            return false;
        }
        if (is_string($date)) {
            $date = self::create($date);
        } elseif (! is_object($date) && ! ($date instanceof self)){
            return false;
        }

        if (empty($date->dateString)) {
            return false;
        }
        return date_diff($this->dateString, $date->dateString);
    }
    /**
     * smaller than date given
     * 
     * @param       string          $date               dateString
     * 
     * @return      bool            true if given date is larger than date instance
     */
    public function smallerThan($date = null)
    {
        if (empty($this->dateString)) {
            return false;
        }
        if (is_string($date)) {
            $date = self::create($date);
        } elseif (! is_object($date) && ! ($date instanceof self)){
            return false;
        }

        if (empty($date->dateString)) {
            return false;
        }
        return $this->dateString < $date->dateString;
    }
    /**
     * larger than date given
     * 
     * @param       string          $date               dateString
     * 
     * @return      bool            true if given date is smaller than date instance
     */
    public function largerThan($date = null)
    {
        if (empty($this->dateString)) {
            return false;
        }
        if (is_string($date)) {
            $date = self::create($date);
        } elseif (! is_object($date) && ! ($date instanceof self)){
            return false;
        }

        if (empty($date->dateString)) {
            return false;
        }
        return $this->dateString > $date->dateString;
    }
    /**
     * larger than date given
     * 
     * @param       string          $date               dateString
     * 
     * @return      bool            true if given date is smaller than date instance
     */
    public function equalTo($date = null)
    {
        if (empty($this->dateString)) {
            return false;
        }
        if (is_string($date)) {
            $date = self::create($date);
        } elseif (! is_object($date) && ! ($date instanceof self)){
            return false;
        }

        if (empty($date->dateString)) {
            return false;
        }
        return $this->dateString == $date->dateString;
    }
}