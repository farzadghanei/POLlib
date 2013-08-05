<?php
//Parsonline/ZF/Controller/Plugin/PluginAbstract.php
/**
 * Defines Parsonline_ZF_Controller_Plugin_PluginAbstract.
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
 * @version     1.0.0 2011-02-24
 */

/**
 * Parsonline_ZF_Controller_Plugin_PluginAbstract
 *
 * Abstract controller plugin for Zend Framework based applications. all such plugins
 * in Parsonline library would extend this class.
 * 
 * @uses    Zend_Controller_Plugin_Abstract
 * @uses    Zend_Controller_Front
 */
require_once('Zend/Controller/Plugin/Abstract.php');
require_once('Zend/Controller/Front.php');

abstract class Parsonline_ZF_Controller_Plugin_PluginAbstract extends Zend_Controller_Plugin_Abstract
{
    /**
     * Returns the application bootstrapper class associated to the
     * front controller.
     *
     * @return  Zend_Application_Bootstrap_Bootstrap
     */
    protected function _getBootstrap()
    {
        $frontController = Zend_Controller_Front::getInstance();
        return $frontController->getParam('bootstrap');
    }
}