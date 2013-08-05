<?php
//Parsonline/Converter/StringConverterAbstract.php
/**
 * Defines Parsonline_Converter_StringConverterAbstract class.
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
 * @version     0.1.0 2010-12-02
 */

/**
 * Parsonline_Converter_StringConverterAbstract
 * 
 * Capsulates shared functionality between all converters that could convert
 * their target data to string values.
 *
 * @uses    Parsonline_Converter_Abstract
 */
require_once('Parsonline/Converter/Abstract.php');

abstract class Parsonline_Converter_StringConverterAbstract extends Parsonline_Converter_Abstract
{
    const STRING_FORMAT_XML = 'xml';
    const STRING_FORMAT_JSON = 'json';
    const STRING_FORMAT_TAB_SEPARATED_VALUES = 'tsv';
    const STRING_FORMAT_COMMA_SEPARATED_VALUES = 'csv';
    const STRING_FORMAT_PIPE_SEPARATED_VALUES = 'psv';
    const STRING_FORMAT_PHP_SERIALIZED = 'php';

    /**
     * the name of the default string format for the converter class.
     * 
     * @staticvar   string
     */
    protected static $_defaultStringFormat;
    
    /**
     * the target string format to convert the exception.
     *
     * @var string
     */
    protected $_stringFormat;

    /**
     * returns the name of the default string format for the converter class.
     *
     * @return  string
     */
    public static function getDefaultStringFormat()
    {
        return self::$_defaultStringFormat;
    }
    
    /**
     * sets the name of the default string format for the converter class.
     *
     * @return  string
     */
    public static function setDefaultStringFormat($format)
    {
        self::$_defaultStringFormat = $format;
    }
    
    /**
     * Returns well separated format of the associative array.
     * could be configured to ignore keys, or how to separate key and values
     * and how to enclose or not the key/value pairs.
     *
     * @param   string  $separator      data separator
     * @param   string  $encloseBy      the string to enclose each data field. empty string to disable
     * @param   bool    $addKeys        if should add the keys before each value
     * @param   string  $keyValueSeparator  the string to separate key and values
     * @return  string
     */
    public static function convertAssocArrayToString(array $data, $separator, $encloseBy='', $addKeys=true, $keyValueSeparator=':')
    {
        // keep the output as array for better performance
        $result = array();
        foreach($data as $key => $value) {
            $field = $encloseBy;
            if ($addKeys) {
                $field .= $key . $keyValueSeparator;
            }
            $field .= $value;
            $field .= $encloseBy;
            array_push($result, $field);
        }
        return implode($separator, $result);
    } // public static function convertAssocArrayToString()

    /**
     * Constructor.
     *
     * @param   mixed   $data
     */
    public function __construct($data=null)
    {
        parent::__construct($data);
        if (self::$_defaultStringFormat) {
            $this->setStringFormat(self::$_defaultStringFormat);
        }
    }

    /**
     * Returns the name of the target string format to convert the data to
     *
     * @return  string
     */
    public function getStringFormat()
    {
        return $this->_stringFormat;
    }

    /**
     * Returns an array of supported string formats.
     * values are lower cased and correspond to class constants STRING_FORMAT_*
     * values.
     *
     * @return  array
     * @abstract
     */
    abstract public function getSupportedStringFormats();

    /**
     * Sets the name of the target string format to convert the data to.
     *
     * @param   string  $formart        use STRING_FORMAT_* constants
     * @return  Parsonline_Converter_StringConverterAbstract
     * @throws  Parsonline_Exception_InvalidParameterException on invalid string format
     */
    public function setStringFormat($format)
    {
        $format = strtolower($format);
        if ( !in_array($format, $this->getSupportedStringFormats()) ) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "the string format is not supported yet", 0, null,
                'valid format', $format
            );
        }
        $this->_stringFormat = $format;
        return $this;
    } // public function setStringFormat()

    /**
     * converts the target data to a string value,
     * 
     * @return string
     * @abstract
     */
    abstract public function toString();
}
