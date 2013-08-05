<?php
//Parsonline/Parser/Syslog/SyslogAbstract.php
/**
 * Defines Parsonline_Parser_Syslog_SyslogAbstract class.
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
 * @package     Parsonlne_Parser
 * @subpackage  Syslog
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.0 2010-09-26
 */

/**
 * Parsonline_Parser_Syslog_SyslogAbstract
 * 
 * Capsulates shared functionality between all syslog log message parsers.
 *
 */
abstract class Parsonline_Parser_Syslog_SyslogAbstract
{
    /**
     * the raw text output from SyslogAbstract syslog message
     * 
     * @var string
     */
    protected $_logMessage = '';

    /**
     * Constructor.
     * 
     * capsulates shared functionality between all syslog log message parsers.
     * 
     * @param   string    $msg
     */
    public function __construct($msg='')
    {
        $this->setLogMessage($msg);
    }

    /**
     * gets the raw data of SyslogAbstract output
     *
     * @return   string  $data
     */
    public function getLogMessage()
    {
        return $this->_logMessage;
    }

    /**
     * sets the raw data of SyslogAbstract output
     *
     * @param   string  $msg
     * @return   Parsonline_Parser_Syslog_SyslogAbstract     object self reference
     */
    public function setLogMessage($msg='')
    {
        $this->_logMessage = strval($msg);
        return $this;
    }
}
