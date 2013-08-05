<?php
//Parsonline/Converter/Exception.php
/**
 * Defines Parsonline_Converter_Exception class.
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
 * @copyright  Copyright (c) 2010-2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Converter
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.2.3 2011-02-13
 */

/**
 * Parsonline_Converter_Exception
 * 
 * Converts exception objects to different formats.
 *
 * @uses    Parsonline_Converter_Abstract
 */
require_once('Parsonline/Converter/StringConverterAbstract.php');
class Parsonline_Converter_Exception extends Parsonline_Converter_StringConverterAbstract
{
    const DATA_CODE = 'code';
    const DATA_MESSAGE = 'message';
    const DATA_LINE = 'line';
    const DATA_FILE = 'file';
    const DATA_TRACE_STRING = 'traceAsString';

    /**
     * if the returning string should contain debugging information
     * 
     * @var bool
     */
    protected $_debugMode = false;

    /**
     * returns an array of supported string formats.
     * values are lower cased and correspond to class constants STRING_FORMAT_*
     * values.
     *
     * @return  array
     */
    public function getSupportedStringFormats()
    {
        return array(
                self::STRING_FORMAT_PHP_SERIALIZED,
                self::STRING_FORMAT_COMMA_SEPARATED_VALUES,
                self::STRING_FORMAT_TAB_SEPARATED_VALUES,
                self::STRING_FORMAT_PIPE_SEPARATED_VALUES
            );
    }

    /**
     * if the converter is setup for the debugging mode.
     * if so, the converter methods would include debugging information out
     * of the excption object.
     * 
     * @return  bool
     */
    public function isDebugMode()
    {
        return $this->_debugMode;
    }

    /**
     * setup the converter for the debugging mode.
     * if so, the converter methods would include debugging information out
     * of the excption object.
     *
     * @param   bool    $set        default is true
     * @return  Parsonline_Converter_Exception
     */
    public function setDebugMode($set=true)
    {
        $this->_debugMode = true && $set;
        return $this;
    }

    /**
     * Sets the target data to be converted.
     * overrides the parent method, by making sure the data has methods
     * of an Exception object.
     * 
     * @param   Exception   $data
     * @return  Parsonline_Converter_Exception
     */
    public function setData(Exception $data)
    {
        parent::setData($data);
        return $this;
    } // public function setData()

    /**
     * Returns an associative array of key => values
     * from the exception object.
     * keys are class contants of DATA_* (and are corresponding to
     * get* methods of the Exception class).
     * debugging informatio from the exception is only returned when
     * the debug mode is on.
     *
     * @return  array
     * @throws  Parsonline_Exception_ContextException on no exception data available
     */
    public function toArray()
    {
        if (!$this->_data) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                    "no exception data is set for the converter yet"
            );
        }
        $exp = $this->_data;
        /*@var $exp Exception*/
        $result = array();
        $result[self::DATA_CODE] = $exp->getCode();
        $result[self::DATA_MESSAGE] = $exp->getMessage();
        if ($this->_debugMode) {
            $result[self::DATA_FILE] = $exp->getFile();
            $result[self::DATA_LINE] = $exp->getLine();
            $result[self::DATA_TRACE_STRING] = $exp->getTraceAsString();
        }
        return $result;
    } // function toArray()

    /**
     * Converts the exception object to a string value. the string would be
     * formatted based on the specified format for the string.
     *
     * @return  string
     * @throws  Parsonline_Exception_ContextException on no valid string format setup
     */
    public function toString()
    {
        switch ($this->_stringFormat) {
            case self::STRING_FORMAT_PHP_SERIALIZED:
                return $this->toPHPSerialized();
            case self::STRING_FORMAT_TAB_SEPARATED_VALUES:
                return $this->toTabSeparatedValues();
            case self::STRING_FORMAT_COMMA_SEPARATED_VALUES:
                return $this->toCommaSeparatedValues();
            case self::STRING_FORMAT_PIPE_SEPARATED_VALUES:
                return $this->toPipeSeparatedValues();
            default:
                /**
                 * @uses    Parsonline_Exception_Context_MethodCallException
                 */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException(
                    "no valid string format target is specified"
                );
        }
    } // public function toString()

    /**
     * returns tab separated format of the information about the exception.
     * could be configured to ignore keys, or how to separate key and values
     * and how to enclose or not the key/value pairs.
     *
     * @param   string  $encloseBy      the string to enclose each data field. empty string to disable
     * @param   bool    $addKeys        if should add the keys before each value
     * @param   string  $keyValueSeparator  the string to separate key and values
     * @return  string
     * @throws  Parsonline_Exception_ContextException on no exception data available
     */
    public function toTabSeparatedValues($encloseBy='"', $addKeys=true, $keyValueSeparator=':')
    {
        return self::convertAssocArrayToString(
            $this->toArray(), "\t", $encloseBy, $addKeys, $keyValueSeparator
        );
    }

    /**
     * Returns comma separated format of the information about the exception.
     * could be configured to ignore keys, or how to separate key and values
     * and how to enclose or not the key/value pairs.
     *
     * @param   string  $encloseBy      the string to enclose each data field. empty string to disable
     * @param   bool    $addKeys        if should add the keys before each value
     * @param   string  $keyValueSeparator  the string to separate key and values
     * @return  string
     * @throws  Parsonline_Exception_ContextException on no exception data available
     */
    public function toCommaSeparatedValues($encloseBy='"', $addKeys=true, $keyValueSeparator=':')
    {
        return self::convertAssocArrayToString(
            $this->toArray(), ",", $encloseBy, $addKeys, $keyValueSeparator
        );
    }

    /**
     * Returns pipe separated format of the information about the exception.
     * could be configured to ignore keys, or how to separate key and values
     * and how to enclose or not the key/value pairs.
     *
     * @param   string  $encloseBy      the string to enclose each data field. empty string to disable
     * @param   bool    $addKeys        if should add the keys before each value
     * @param   string  $keyValueSeparator  the string to separate key and values
     * @return  string
     * @throws  Parsonline_Exception_ContextException on no exception data available
     */
    public function toPipeSeparatedValues($encloseBy=' ', $addKeys=true, $keyValueSeparator=': ')
    {
        return self::convertAssocArrayToString(
            $this->toArray(), "|", $encloseBy, $addKeys, $keyValueSeparator
        );
    }
}
