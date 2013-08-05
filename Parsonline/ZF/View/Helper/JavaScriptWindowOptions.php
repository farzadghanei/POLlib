<?php
//Parsonline/ZF/View/Helper/JavaScriptWindowOptions.php
/**
 * Defines Parsonline_ZF_View_Helper_JavaScriptWindowOptions class.
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
 * @package     Parsonline_ZF_View
 * @subpackage  Helper
 * @author      Farzad Ghanei <f.ghanei@parsonline.net>
 * @version     0.3.0 2012-07-09
 */

/**
 * @uses    Zend_View
 * @uses    Zend_View_Helper_Abstract
 */
require_once('Zend/View.php');
require_once('Zend/View/Helper/Abstract.php');

/**
 * Parsonline_ZF_View_Helper_JavaScriptWindowOptions
 * 
 * a view helper to return a string of JavaScript window options from an array
 * 
 * @uses    Zend_View_Helper_Abstract
 */
class Parsonline_ZF_View_Helper_JavaScriptWindowOptions extends Zend_View_Helper_Abstract
{
    /**
     * Returns the appropriate string for window options as used in window.open()
     * javascript call.
     * configuration options:
     *      'url'               =>  string|array        absolute URL string, or an associative array of module,controller,action names
     *      'attributes'        =>  array               an associative array of additional attributes to set for the generated element
     *      'nojavascript'      =>  bool                do not use JavaScript code, and return just HTML link
     *      'window'            =>  string              JavaScript window.open option string (if javascript is not disabled in configurations)
     * 
     * 
     * @param   array|Zend_Config|object    $config     associative array of configurations.
     * @return  string
     * @throws  Parsonline_Exception_ValueException
     */
    public function javaScriptWindowOptions($config)
    {
        if ( is_object($config) ) {
            if ( method_exists($config, 'toArray') ) {
                $config = $config->toArray();
            } else {
                $config = get_object_vars($config);
            }
        } elseif ( !is_array($config) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException('configurations should be an associative array');
        }
        
        // keep the generated code as an array for better performance than the immutable string concatination
        $code = array();
        foreach($config as $attr => $value) {
            array_push($code, "{$attr}={$value}");
        }
        return implode(', ', $code);
    } // public function javaScriptWindowOptions()
}