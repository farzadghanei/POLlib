<?php
//Parsonline/ZF/Model/PHPSyslogNg/Log.php
/**
 * Defines Parsonline_ZF_Model_PHPSyslogNg_Log class.
 * 
 * Parsonline
 * 
 * Copyright (c) 2010-2011-2012 ParsOnline, Inc. (www.parsonline.com)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 * 
 * @copyright   Copyright (c) 2010-2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license     Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_ZF_Model
 * @subpackage  PHPSyslogNg
 * @author      Farzad Ghanei <f.ghanei@parsonline.net>
 * @version     2.2.0 2012-07-08
 */

/**
 * @uses    Parsonline_ZF_Model_DbModelAbstract
 * @uses    Parsonline_ZF_Model_Mapper_PHPSyslogNg_Log
 */
require_once('Parsonline/ZF/Model/DbModelAbstract.php');
require_once('Parsonline/ZF/Model/Mapper/PHPSyslogNg/Log.php');

/**
 * Parsonline_ZF_Model_PHPSyslogNg_Log
 * 
 * a high level data model class for log records stored in php-syslog-ng database.
 * uses a DB backed end data mapper as the gateway to the database.
 *
 * @see     Parsonline_ZF_Model_DbModelAbstract
 */
class Parsonline_ZF_Model_PHPSyslogNg_Log extends Parsonline_ZF_Model_DbModelAbstract
{
    /**
     * the hostname of the facility who issued the log
     * 
     * @var string
     */
    protected $_host = '';

    /**
     * the name (role) of the facility who issued the log.
     * though the facility property of syslog is transferred as integer codes,
     * they are stored as string values in PHP-Syslog-ng database.
     * 
     * @var string
     */
    protected $_facility = '';

    /**
     * log priority, one of standard syslog priorities
     * 
     * @var string
     */
    protected $_priority = '';

    /**
     * log level, one of standard syslog levels
     * 
     * @var string
     */
    protected $_level = '';

    /**
     * tag code of the log
     * 
     * @var string
     */
    protected $_tag = '';

    /**
     * the date time of the log in YYYY-mm-dd format
     * 
     * @var string
     */
    protected $_datetime = '';

    /**
     * the name of the program/process who issued the log
     * 
     * @var string
     */
    protected $_program = '';

    /**
     * log message string
     * 
     * @var string
     */
    protected $_msg = '';

    /**
     * sequence id. log record unique identifier
     * 
     * @var int
     */
    protected $_seq = null;

    /**
     *
     * @var int
     */
    protected $_counter = null;
    
    /**
     * a datetime value (for data partitioning)
     * 
     * @var string
     */
    protected $_fo = '';

    /** 
     * a datetime value (for data partitioning)
     * 
     * @var string
     */
    protected $_lo = '';

    /**
     * Constructor.
     * 
     * @param array|int $options an array of property => value, or int for log id (sequence)
     */
    public function __construct( $options = null )
    {
        if ( is_int($options) ) {
            $this->load($options);
        } else if ( is_array($options) ) {
            $this->setDataFromArray($options);
        }
    } // public function __construct()

    /**
     * Returns the data mapper object of the model
     * 
     * @return  Parsonline_ZF_Model_Mapper_PHPSyslogNg_Log
     */
    public function getMapper()
    {
        if ( !$this->_mapper ) {
            /**
             * @uses    Parsonline_ZF_Model_Mapper_PHPSyslogNg_Log
             */
            $this->_mapper = new Parsonline_ZF_Model_Mapper_PHPSyslogNg_Log();
        }
        return $this->_mapper;
    }

    /**
     * Returns the hostname of the device who issued the log
     *
     * @return  string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * sets the hostname of the device who issued the log
     *
     * @param   string          $host
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setHost($host='')
    {
        $this->_host = strval($host);
        return $this;
    }

    /**
     * returns the name of the facility who issued the log
     *
     * @return  string
     */
    public function getFacility()
    {
        return $this->_facility;
    }

    /**
     * sets the name of the facility who issued the log
     *
     * @param   string          $facility
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setFacility($facility='')
    {
        $this->_facility = strval($facility);
        return $this;
    }

    /**
     * returns the priority of the log
     * 
     * @return  string
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * sets the priority of the log
     * 
     * @param   string  $priority
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setPriority($priority='')
    {
        $this->_priority = strval($priority);
        return $this;
    }

    /**
     * returns the level of the log
     * 
     * @return  string
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * sets the level of the log
     * 
     * @param   string  $level
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setLevel($level='')
    {
        $this->_level = strval($level);
        return $this;
    }

    /**
     * returns the tag of the log
     * 
     * @return  string
     */
    public function getTag()
    {
        return $this->_tag;
    }

    /**
     * sets the tag of the log
     * 
     * @param   string      $tag
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setTag($tag='')
    {
        $this->_tag = strval($tag);
        return $this;
    }

    /**
     * returns the data and time of the log
     * 
     * @return  string
     */
    public function getDateTime()
    {
        return $this->_datetime;
    }

    /**
     * sets the data and time of the log
     * 
     * @param   string  $datetime
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setDateTime($datetime='')
    {
        $this->_datetime = strval($datetime);
        return $this;
    }

    /**
     * returns the name of the program who sent the log
     * 
     * @return  string
     */
    public function getProgram()
    {
        return $this->_program;
    }

    /**
     * sets the name of the program who sent the log
     * 
     * @param   string  $program
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setProgram($program='')
    {
        $this->_program = strval($program);
        return $this;
    }

    /**
     * returns the log message
     * 
     * @return  string
     */
    public function getMessage()
    {
        return $this->_msg;
    }

    /**
     * sets the log message string
     * 
     * @param   string  $message
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setMessage($message='')
    {
        $this->_msg = strval($message);
        return $this;
    }

    /**
     * returns the unique identifier of the log
     * 
     * NOTE: the unique identifier could be large enought so PHP would convert it to float automatically
     *
     * @return  int
     */
    public function getSequence()
    {
        return $this->_seq;
    }

    /**
     * sets the unique identifier of the log. to prevent cropping of data, this method
     * does not convert the parameter to integer.
     *
     * NOTE: the unique identifier could be larger than PHP max integer value.
     * that's why this method does not cast the parameter to anything. so make sure
     * the sequence is a valid ID (sequence) for the data storage.
     * 
     * @param   int         $seq
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setSequence($seq=null)
    {
        $this->_seq = $seq;
        return $this;
    }

    /**
     * returns the counter
     *
     * @return  int
     */
    public function getCounter()
    {
        return $this->_counter;
    }

    /**
     * sets the counter
     *
     * @param   int     $counter
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setCounter($counter=null)
    {
        $this->_counter = intval($counter);
        return $this;
    }

    /**
     * returns the fo date time value
     * 
     * @return  string
     */
    public function getFo()
    {
        return $this->_fo;
    }

    /**
     * sets the fo date time vlaue
     * 
     * @param   string  $fo
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setFo($fo='')
    {
        $this->_fo = strval($fo);
        return $this;
    }

    /**
     * returns the lo date time vlaue
     * 
     * @return  string
     */
    public function getLo()
    {
        return $this->_lo;
    }

    /**
     * sets the lo date time value
     * 
     * @param   string  $lo
     * @return  Parsonline_ZF_Model_PHPSyslogNg_Log
     */
    public function setLo($lo='')
    {
        $this->_lo = strval($lo);
        return $this;
    }

    /**
     * return syslog log objects for a host, could make it more specific with time period and message search
     *
     * @param   string  $host
     * @param   int     $startTime
     * @param   int     $endTime
     * @param   string  $message
     * @param   int     $limit
     * @param   bool    $like       search for hosts like the hostname. default is false
     * @return  array   indexed array of Parsonline_ZF_Model_PHPSyslogNg_Log objects
     * @throws  Parsonline_Exception_ValueException on invalid time periods
     * @throws  Parsonline_Exception_ContextException on no mapper set
     */
    public function search($host=null, $startTime=null, $endTime=null, $message=null, $limit=null, $like=false)
    {
        $data = array();
        $config = array();

        if ($host !== null) {
            if ($like) {
                $data['~host'] = strval($host);
            } else {
                $data['host'] = strval($host);
            }
        }
        if ($startTime !== null) {
            $startTime = intval($startTime);
            if ($startTime < 0) {
                /**
                 * @uses    Parsonline_Exception_ValueException
                 */
                require_once("Parsonline/Exception/ValueException.php");
                throw new Parsonline_Exception_ValueException("start time should be a positive integer");
            }
            $data['mindatetime'] = date('Y-m-d H:i:s', $startTime);
        }
        if (null !== $endTime) {
            $endTime = intval($endTime);
            if ( $startTime > $endTime ) {
                /**
                 * @uses    Parsonline_Exception_ValueException
                 */
                require_once("Parsonline/Exception/ValueException.php");
                throw new Parsonline_Exception_ValueException("end time should be a larger than the start time");
            }
            $data['maxdatetime'] = date('Y-m-d H:i:s', $endTime);
        }       
        
        if ($message !== null) $data['~msg'] = strval($message);

        if ($limit !== null) {
            $config['limit'] = max( array(1,intval($limit)) );
        }

        $mapper = $this->getMapper();
        if (!$mapper) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("failed to search for syslog logs. no data mapper is provided for the syslog log model object");
        }

        return $mapper->search($data, $config);
    } // public function search()

    /**
     * Deletes syslog logs for a specific hostname logger
     *
     * @param   string  $hostname               hostname of the logging server
     * @param   bool    $accurateSearch         if the hostname of deleting rows should exactly match the hostname
     * @return  int     number of deleted rows
     * @throws  Parsonline_Exception_ContextException on no mapper set yet
     */
    public function deleteHostLogs($hostname='', $accurateSearch=true)
    {
        $mapper = $this->getMapper();
        if (!$mapper) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("failed to delete syslog logs. no data mapper is provided for the syslog log model object");
        }
        $hostname = strval($hostname);
        $fieldData = array();
        
        if ( $accurateSearch ) {
            $fieldData['host'] = $hostname;
        } else {
            $fieldData['~host'] = $hostname;
        }
        return $mapper->deleteRecords(array(), $fieldData);
    } // public function deleteHostLogs()
}