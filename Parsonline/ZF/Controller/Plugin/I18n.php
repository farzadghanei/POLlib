<?php
//Parsonline/ZF/Controller/Plugin/I18n.php
/**
 * Defines Parsonline_ZF_Controller_Plugin_I18n.
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
 * @package     Parsonline_ZF_Controller
 * @subpackage  Plugin
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     1.1.0 2012-07-08
 */

/**
 * @uses    Parsonline_ZF_Controller_Plugin_PluginAbstract
 * @uses    Parsonline_ZF_Application_Resource_I18n
 * @uses    Zend_Controller_Request_Abstract
 */
require_once('Parsonline/ZF/Controller/Plugin/PluginAbstract.php');
require_once('Parsonline/ZF/Application/Resource/I18n.php');
require_once('Zend/Controller/Request/Abstract.php');

/**
 * Parsonline_ZF_Controller_Plugin_I18n
 *
 * Controller plugin for Zend Framework based applications, that setups
 * I18n configurations of the application based on the
 * bootstraped Parsonline_ZF_Application_Resource_I18n.
 * If no such resource had been bootstrapped, triggers a PHP warning
 * but would not stop the execution.
 */
class Parsonline_ZF_Controller_Plugin_I18n extends Parsonline_ZF_Controller_Plugin_PluginAbstract
{
    /**
     *@var Parsonline_ZF_Application_Resource_I18n
     */
    protected $_i18nResource = null;

    /**
     * Returns the I18n resource of the application, or false if no such
     * resource had been bootstrapped.
     *
     * NOTE: triggers a PHP warning error if the i18n resource had not been
     * bootstraped.
     *
     * @return  Parsonline_ZF_Application_Resource_I18n|false
     * @throws  Parsonline_Exception_ContextException on invalid I18n resource bootstrapped
     */
    public function getI18nResource()
    {
        if ( $this->_i18nResource === null ) {
            $bootstrap = $this->_getBootstrap();
            /*@var $bootstrap Zend_Application_Bootstrap_Bootstrap */
            if ( $bootstrap->hasResource('i18n') ) {
                $i18n = $bootstrap->getResource('i18n');
                if ( !$i18n || !($i18n instanceof Parsonline_ZF_Application_Resource_I18n) ) {
                    /**
                     * @uses    Parsonline_Exception_ContextException
                     */
                    require_once('Parsonline/Exception/ContextException.php');
                    $exp = new Parsonline_Exception_ContextException(
                        "Failed to load the I18n controller plugin. Bootstrapped I18n resource is not a Parsonline_ZF_Application_Resource_I18n instance"
                    );
                    trigger_error($exp, E_USER_WARNING);
                    throw $exp;
                }
                $this->_i18nResource = $i18n;
            } else {
                trigger_error(__METHOD__ . " > i18n application resource is not available", E_USER_WARNING);
                $this->_i18nResource = false;
            }
        }
        return $this->_i18nResource;
    } // public function getI18nResource()

    /**
     * Checks the request before dispaching,
     * If the i18n resource is loaded and configured, registers the translator
     * and locale settings of the i18n resource in the Zend_Registry, and
     * assignes the translator the default Zend_View object for controllers.
     *
     * @param   Zend_Controller_Request_Abstract    $request
     * @return  Zend_Controller_Request_Abstract    $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {        
        $i18n = $this->getI18nResource();
        
        /*@var $i18n Parsonline_ZF_Application_Resource_I18n */
        if (!$i18n) return $request;
        
        $bootstrap = $this->_getBootstrap();
        /**
         * @uses    Zend_View
         * @uses    Zend_Registry
         */
        require_once('Zend/View.php');
        require_once('Zend/Registry.php');
        $view = $bootstrap->hasResource('view') ? $bootstrap->getResource('view') : new Zend_View();
        /*@var $view Zend_View */
        $locale = $i18n->getLocale();
        Zend_Registry::set('Zend_Locale', $locale);
        $view->translate()->setLocale($locale);

        $options = $i18n->getOptions();
        if ( array_key_exists('translate', $options) ) {
            $translate = $i18n->getTranslate();
            /*@var $translate   Zend_Translate */
            Zend_Registry::set('Zend_Translate', $translate);
            $view->translate()->setTranslator($translate);
        }
        Zend_Registry::set('Zend_View', $view);
        return $request;
    } // public function preDispatch()
}