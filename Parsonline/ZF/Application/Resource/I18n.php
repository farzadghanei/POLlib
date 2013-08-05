<?php
//Parsonline/ZF/Application/Resource/I18n.php
/**
 * Defines Parsonline_ZF_Application_Resource_I18n class.
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
 * @package     Parsonline_ZF_Application
 * @subpackage  Resource
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     1.2.0 2012-07-08
 */

/**
 * Parsonline_ZF_Application_Resrouce_I18n
 *
 * Internationalization resource for the Zend Framework based
 * applications. creates instances of Zend_Locale, and/or Zend_Translate objects.
 *
 * @uses    Parsonline_ZF_Application_Resource_Abstract
 * @uses    Zend_Locale
 * @uses    Zend_Registry
 * @uses    Zend_Translate_Adapter
 * @uses    Zend_Translate
 */
require_once('Parsonline/ZF/Application/Resource/Abstract.php');
require_once('Zend/Locale.php');
require_once('Zend/Registry.php');
require_once('Zend/Translate.php');
require_once('Zend/Translate/Adapter.php');

class Parsonline_ZF_Application_Resource_I18n extends Parsonline_ZF_Application_Resource_Abstract
{
    const TEXT_DIRECTION_LTR = 'ltr';
    const TEXT_DIRECTION_RTL = 'rtl';
    
    /**
     * @var Zend_Locale
     */
    protected $_locale = null;

    /**
     * @var Zend_Translate
     */
    protected $_translate = null;

    /**
     * text direction of I18n config
     * 
     * @var string
     */
    protected $_textDirection = '';

    /**
     * absolute path to the translation file
     *
     * @var string
     */
    protected $_translateFilename = '';

    /**
     * Returns the Zend_Locale object based on the i18n locale option.
     * If no default locale for application is set, uses the
     * default Zend_Locale from user browser or application
     * environment.
     *
     * @return  Zend_Locale
     * @throws  Zend_Locale_Exception on invalid locale name in options
     */
    public function getLocale()
    {
        if ( !$this->_locale ) {
            /**
             * @uses Zend_Locale
             */
            $options = $this->getOptions();
            if ( array_key_exists('locale', $options) ) {
                $locale = new Zend_Locale(strval($options['locale']));
            } else {
                $warning = 'locale option is missing';
                $this->_triggerError($warning, E_USER_NOTICE);
                $locale = new Zend_Locale();
            }
            $this->_locale = $locale;
        }
        return $this->_locale;
    } // public function getLocale()

    /**
     * Sets the locale object of the object
     *
     * @param   Zend_Locale|string  $locale
     * @return  Parsonline_ZF_Application_Resrouce_I18n
     * @throws  Parsonline_Exception_ValueException on none Zend_Locale|string input
     *          Zend_Locale_Exception on invalid locale
     */
    public function setLocale($locale)
    {
        if ( is_string($locale) && Zend_Locale::isLocale($locale) ) {
            $locale = new Zend_Locale($locale);
        }
        if ( !is_object($locale) || !($locale instanceof Zend_Locale) ) {
            $err = "locale should be a valid Zend_Locale instance";
            $this->_triggerError($err, E_USER_WARNING);
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException($err);
        }
        $this->_locale = $locale;
        return $this;
    } // public function setLocale()

    /**
     * Returns the Zend_Translate object of the i18n resource to translate
     * messages strings based on the configured locale.
     * 
     * @return  Zend_Translate
     */
    public function getTranslate()
    {
        if (!$this->_translate) {
            $adapterName = $this->getTranslateAdapterName();
            $messageFile = $this->getTranslateMessageFilename();
            $this->_translate = new Zend_Translate($adapterName, $messageFile, $this->getLocale());
        }
        return $this->_translate;
    } // public function getTranslate()

    /**
     * Returns the array of options for translate module of the i18n resource
     * 
     * @return  array
     * @throws  Parsonline_Exception_ContextException on missing options for translate
     */
    public function getTranslateOptions()
    {
        $options = $this->getOptions();
        if ( !array_key_exists('translate', $options) ) {
            $err = "translate options are missing from i18n resource options";
            $this->_triggerError($err);
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException($err);
        }
        $transOptions = $options['translate'];
        if ( !is_array($transOptions) ) {
            $err = "translate option of i18n resource should be an array";
            $this->_triggerError($err);
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException($err);
        }
        return $transOptions;
    } // public function getTranslateOptions()

    /**
     * Returns the full path to the message file of the translate module
     * of the resource. checks if the file exists or not.
     * available options are:
     *      messageFile: absolute path to message file
     *      massegaPath: directory where message files reside (used if no messageFile is available)
     *      massegafileBaseName: name of the message file in messagePath. default is locale name
     *      massegafileExtension: message file extension. default is guessed from translation adapter
     *
     * @return  string
     * @throws  Parsonline_Exception_ContextException on missing options
     *          Parsonline_Exception_IOException on not readable file
     */
    public function getTranslateMessageFilename()
    {
        if (!$this->_translateFilename) {
            $options = $this->getTranslateOptions();
            if ( array_key_exists('messageFile', $options) ) {
                $filename = realpath($options['messageFile']);
            } else {
                if ( !array_key_exists('messagePath',$options) ) {
                    $err = 'missing messagePath option from translate i18n module options';
                    $this->_triggerError($err);
                    /**
                     * @uses    Parsonline_Exception_ContextException
                     */
                    require_once('Parsonline/Exception/ContextException.php.php');
                    throw new Parsonline_Exception_ContextException($err);
                }
                $path = $options['messagePath'];
                $name = array_key_exists('messageFileBaseName', $options) ? $options['messageFileBaseName'] : strval($this->getLocale());
                $ext = array_key_exists('messageFileExtension', $options) ? $options['messageFileExtension'] : $this->guessTranslateAdapterFileExtension($this->getTranslateAdaptername());
                $filename = "{$path}/{$name}.{$ext}";
            }
            $canonicalFilename = realpath($filename);
            if ( !$canonicalFilename || !is_file($canonicalFilename) || !is_readable($canonicalFilename) ) {
                if (!$canonicalFilename) $canonicalFilename = $filename;
                $err = "failed to access the translation message file '$canonicalFilename'";
                $this->_triggerError($err);
                /**
                 * @uses    Parsonline_Exception_IOException
                 */
                require_once'Parsonline/Exception/IOException.php';
                throw new Parsonline_Exception_IOException($err);
            }
            $this->_translateFilename = $canonicalFilename;
        }
        return $this->_translateFilename;
    } // public function getTranslateMessageFilename()

    /**
     * Guesses the file extension for message files of a given translate adapter
     * name.
     *
     * @param   Zend_Translate_Adapter|string   $adapter
     * @return  string|false
     * @throws  Parsonline_Exception_ValueException on not string or Zend_Translate_Adapter input
     */
    public function guessTranslateAdapterFileExtension($adapter)
    {
        if ( is_string($adapter) ) {
            $adapter = strtolower($adapter);
        } elseif (is_object($adapter) && $adapter instanceof Zend_Translate_Adapter) {
            $adapter = strtolower( get_class($adapter) );
        } else {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("translate adapter should specify a translation adapter object, or name of one");
        }
        $adapter = str_replace('zend_translate_adapter_','', $adapter);
        switch($adapter) {
            case 'gettext':
                return  'mo';
            case 'csv':
                return  'csv';
            case 'array':
                return  'php';
            case 'Ini':
                return  'ini';
            case 'qt':
                return  'ts';
            case 'tmx':
                return  'tmx';
            case 'tbx':
                return  'tbx';
            case 'xliff':
                return  'xliff';
            case 'xml':
                return  'xml';
            default:
                return false;
        }
    } // public function guessTranslateAdapterFileExtension()

    /**
     * Returns the name of the translate adapter from the options.
     *
     * @return  string
     * @throws  Parsonline_Exception_ContextException on missing adapter option
     */
    public function getTranslateAdapterName()
    {
        $options = $this->getTranslateOptions();
        if ( !is_array($options) || !array_key_exists('adapter',$options) ) {
            $err = "translate option should be an array, at least specifying the adapter to use";
            $this->_triggerError($err);
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException($err);
        }
        return $options['adapter'];
    }

    /**
     * Guesses text direction of the specified locale. if no locale is passed,
     * uses the I18n object locale object.
     * returns the appropriate direction, or the null if failed to guess.
     *
     * @param   Zend_Locale|string     $locale
     * @return  string|null
     * @throws  Parsonline_Exception_ValueException on invalid locale name
     */
    public function guessTextDirectionFromLocale($locale)
    {

        if ( !($locale instanceof Zend_Locale) ) {
            if (!Zend_Locale::isLocale($locale) ) {
                /**
                 * @uses    Parsonline_Exception_ValueException
                 */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException("invalid locale");
            }
            
        }
        $locale = strtoupper($locale);
        /**
         * @TODO
         * use Zend_Locale::getTranslationList('layout') to get the text direction
         */
        switch($locale) {
            case 'FA_IR':
                return self::TEXT_DIRECTION_RTL;
            case 'EN_US':
                return self::TEXT_DIRECTION_LTR;
            default:
                return null;
        }
    } // public function guessTextDirectionFromLocale()

    /**
     * Returns text direction of the i18n settings.
     * use class constants for convinience.
     *
     * @return  string
     * @throws  Parsonline_Exception_ContextException on missing layout option
     */
    public function getTextDirection()
    {
        if ( !$this->_textDirection ) {
            $options = $this->getOptions();
            $direction = null;
            if ( array_key_exists('layout', $options) ) {
                $direction = strtoupper($options['layout']);
                $direction = ($direction === 'RTL') ?  self::TEXT_DIRECTION_RTL : self::TEXT_DIRECTION_LTR;
            } else {
                $direction = $this->guessTextDirectionFromLocale($this->getLocale());
            }

            if ($direction === null) {
                $err = 'layout option is missing';
                $this->_triggerError($err, E_USER_WARNING);
                /**
                 * @uses    Parsonline_Exception_ContextException
                 */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException($err);
            }
            $this->_textDirection = $direction;
        }        
        return $this->_textDirection;
    } // public function getTextDirection()

    /**
     * Sets the text direction of the i18 object.
     * use class constants for convinience
     *
     * @param   string  $direction
     * @return  Parsonline_ZF_Application_Resrouce_I18n
     * @throws  Parsonline_Exception_ValueException
     */
    public function setTextDirection($direction)
    {
        $direction = strtolower($direction);
        if ( $direction === self::TEXT_DIRECTION_LTR || $direction === self::TEXT_DIRECTION_RTL ) {
            $this->_textDirection = $direction;
        } else {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("text direction '{$direction}' is not valid");
        }
        return $this;
    } // public function setTextDirection()

    /**
     * Initializes the resource.
     * Reads configurations, generates Zend_Locale and Zend_Translate objects,
     * stores them in the Zend_Registry and returns itself.
     *
     * @return  Parsonline_ZF_Application_Resrouce_I18n
     */
    public function init()
    {
        /**
         * @uses    Zend_Locale
         * @uses    Zend_Registry
         */
        require_once('Zend/Locale.php');
        require_once('Zend/Registry.php');
        $locale = $this->getLocale();
        $this->getTextDirection();
        Zend_Locale::setDefault($locale);
        Zend_Registry::set('Zend_Locale', $locale);
        Zend_Registry::set('i18n', $this);
        $options = $this->getOptions();
        if (array_key_exists('translate',$options)) {
            /**
             * @uses    Zend_Translate
             */
            $trans = $this->getTranslate();
            Zend_Registry::set('Zend_Translate', $trans);
        }
        return $this;
    } // public function init()
}