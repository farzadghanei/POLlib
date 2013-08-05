<?php
//Parsonline/ZF/Controller/Plugin/RegisterRedirect.php
/**
 * Defines Parsonline_ZF_Controller_Plugin_RegisterRedirect class.
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
 * @package     Parsonline_ZF_Plugin
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.0 2012-07-08
 */

/**
 * @uses    Parsonline_ZF_Controller_Plugin_PluginAbstract
 * @uses    Zend_Session_Namespace 
 * @uses    Zend_Controller_Action_Helper_Redirector
 */
require_once("Parsonline/ZF/Controller/Plugin/PluginAbstract.php");
require_once("Zend/Session/Namespace.php");
require_once("Zend/Controller/Action/Helper/Redirector.php");

/**
 * Parsonline_ZF_Controller_Plugin_RegisterRedirect
 *
 * Registers a redirect target for the user, so on some step ahead (where
 * the application wants to), the user could be redirected to the registered
 * address.
 * 
 * this is used where there are steps to be taken and each step specifies which
 * where the next step should redirect the user to.
 *
 * some actions might need to redirect the user to someplace else after they
 * are done, like when a not logged in user requests a page, she is redirected
 * to the login page, but after the login, she should be redirected to
 * the first page she wanted.
 * 
 * uses the information stroed in Zend_Session_Namespace to determine where to
 * redirect.
 */
class Parsonline_ZF_Controller_Plugin_RegisterRedirect extends
Parsonline_ZF_Controller_Plugin_PluginAbstract
{
    /**
     * the name of the session name space used to store the redirection target
     * information.
     *
     * @staticvar string
     */
    protected static $_sessionNamespaceName = 'RedirectAfterNextStep';

    /**
     * an associative array of the target URL, or module/controller/action to
     * redirect to.
     *
     * @var array
     */
    protected $_redirectTarget = array('module' => null, 'controller' => null, 'action' => null, 'params' => array(), 'url' => null);

    /**
     * the redirector object used to do the redirects.
     *
     * @var Zend_Controller_Action_Helper_Redirector
     */
    protected $_redirector = null;

    /**
     * the session namespace object used to save the redirection data.
     *
     * @var Zend_Session_Namespace
     */
    protected $_sessionNamespace = null;

    /**
     * returns the session namespace used to store the redirection target information.
     *
     * @return  string
     */
    public static function getSessionNamespaceName()
    {
        return self::$_sessionNamespaceName;
    }

    /**
     * sets the session namespace used to store the redirection target information.
     *
     * @param   string  $namespace
     */
    public static function setSessionNamespaceName($namespace)
    {
        self::$_sessionNamespaceName = strval($namespace);
    }

    /**
     * returns the Zend_Session_Namespace used
     * to store redirection data.
     *
     * @return  Zend_Session_Namespace
     */
    public function getSessionNamespace()
    {
        if (!$this->_sessionNamespace) {
            /**
             * @uses    Zend_Session_Namespace
             */
            $this->_sessionNamespace = new Zend_Session_Namespace( self::$_sessionNamespaceName );
        }
        return $this->_sessionNamespace;
    }

    /**
     * returns the redirector object used to do the redirection.
     *
     * @return  Zend_Controller_Action_Helper_Redirector
     */
    public function getRedirector()
    {
        if (!$this->_redirector) {
            /**
             * @uses    Zend_Controller_Action_Helper_Redirector
             */
            $this->_redirector = new Zend_Controller_Action_Helper_Redirector();
        }
        return $this->_redirector;
    }

    /**
     * sets the redirector object used to do the redirection
     *
     * @param   Zend_Controller_Action_Helper_Redirector  $redirector
     * @return  Parsonline_ZF_Controller_Plugin_RegisterRedirect
     */
    public function setRedirector(Zend_Controller_Action_Helper_Redirector $redirector)
    {
        $this->_redirector = $redirector;
        return $this;
    }

    /**
     * checks a variable and makes sure if it is a valid redirection target.
     *
     * @param   mixed   $target
     * @return  bool
     */
    public function isValidRedirectTarget($target)
    {
        $valid = true;
        if ( is_array() ) {
            $keys = array_keys($this->_redirectTarget);
            foreach($keys as $k) {
                if ( !array_key_exists($k, $target) ) $valid = false;
            }
        } else {
            $valid = false;
        }
        return $valid;
    } // public function isValidRedirectTarget()

    /**
     * returns the information about the redirect target as an array
     * with these keys: string module, string controller, string action, array params, string url.
     * if there is no on memory data for target, tries to read the data from
     * the session.
     *
     * @param   bool    $useSession     if should read the session information to get redirection target, if no on-memory data is available
     * @return  array
     */
    public function getRedirectTarget($useSession=true)
    {
        if ( $useSession && !$this->hasRedirectTarget(false) ) {
            /**
             * @uses    Zend_Session_Namespace
             */
            $session = $this->getSessionNamespace();
            if ( isset($session->redirectTarget) ) $this->setRedirectTarget( $session->redirectTarget );
        }
        return $this->_redirectTarget;
    }

    /**
     * setsthe information about the redirect target. it should be an associative
     * array with keys: moduel, controller, action, params, url
     *
     * @param   array   $target
     * @return  Parsonline_ZF_Controller_Plugin_RegisterRedirect
     * @throws  Parsonline_Exception_ValueException on invalid target
     */
    public function setRedirectTarget($target=array())
    {
        if ( !is_array($target) ) { // none-array targets are treates as absolute URLs
            $url = strval($target);
            $urlParts = parse_url($url);
            if (!$urlParts) {
                /**
                 * @uses    Parsonline_Exception_ValueException
                 */
                require_once("Parsonline/Exception/ValueException.php");
                throw new Parsonline_Exception_ValueException("Invalid URL for redirection target '{$url}'");
            }
            $cleanedTarget = array (
                                'module' => null,
                                'controller' => null,
                                'action' => null,
                                'params' => array(),
                                'url' => $url
                            );
        } else { // make sure the specified URL is valid and has all the needed values
            $keys = array_keys($this->_redirectTarget);
            $cleanedTarget = array();
            foreach($keys as $k) {
                if ($k == 'params') {
                    if ( array_key_exists($k, $target) ) {
                        if( !is_array($target[$k]) ) {
                            /**
                            * @uses    Parsonline_Exception_ValueException
                            */
                            require_once("Parsonline/Exception/ValueException.php");
                            throw new Parsonline_Exception_ValueException("invalid redirect target. 'params' should be an array");
                        }
                        $cleanedTarget['params'] = $target['params'];
                    } else {
                        $cleanedTarget['params'] = array();
                    }
                } else {
                    $cleanedTarget[$k] = array_key_exists($k, $target) ? $target[$k] : null;
                }
            } // foreach()
            if ( !$cleanedTarget['url'] && !$cleanedTarget['action'] ) {
                /**
                * @uses    Parsonline_Exception_ValueException
                */
                require_once("Parsonline/Exception/ValueException.php");
                throw new Parsonline_Exception_ValueException("invalid redirect target. either an absoulte URL or an action should be specified");
            }
        }
        $this->_redirectTarget = $cleanedTarget;
        $session = $this->getSessionNamespace();
        $session->unsetAll();
        $session->redirectTarget = $this->_redirectTarget;

        return $this;
    } // public function setRedirectTarget()

    /**
     * if there is a target specified to redirect to or not.
     *
     * @param   bool    $useSession     if should read the session information to
     * @return  bool
     */
    public function hasRedirectTarget($useSession=true)
    {
        $target = $this->getRedirectTarget($useSession);
        return $target['url'] || $target['action'];
    }

    /**
     * clears redirection target session
     *
     * @return  Parsonline_ZF_Controller_Plugin_RegisterRedirect   object self reference
     */
    public function clearRedirectTargetSession()
    {
        $session = $this->getSessionNamespace();
        $session->unsetAll();
        return $this;
    }

    /**
     * Redirects to the saved target address:
     *  1. on memory
     *  2. on session
     *
     * @throws  Parsonline_Exception_ContextException on no redirection target specified
     */
    public function redirect()
    {
        $target = $this->getRedirectTarget();
        if ( !$this->hasRedirectTarget(false) ) {
            /**
             *@uses Parsonline_Exception_ContextException 
             */
            require_once("Parsonline/Exception/ContextException");
            throw new Parsonline_Exception_ContextException("no redirection target has been specified");
        }
        $this->clearRedirectTargetSession();
        $redirector = $this->getRedirector();
        if ( $target['url'] ) {
            $redirector->gotoUrl($target['url'], $target['params']);
        } else {
            $redirector->gotoSimple($target['action'], $target['controller'], $target['module'], $target['params']);
        }
    } // public function redirect()
}
