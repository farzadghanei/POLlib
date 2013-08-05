<?php
//Parsonline/ZF/View/Helper/OpenURLInWindowLink.php
/**
 * Defines Parsonline_ZF_View_Helper_OpenURLInWindowLink class.
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
 * Parsonline_ZF_View_Helper_OpenURLInWindowLink
 * 
 * a view helper to create HTML code for a link to open a URL in a new window
 * 
 * @uses    Zend_View_Helper_Abstract
 */
class Parsonline_ZF_View_Helper_OpenURLInWindowLink extends Zend_View_Helper_Abstract
{
    /**
     * Returns the appropriate HTML code for a link to open a URL in a new window.
     * the returned sting is a link that opens a pop up windows with JavaScript
     * or a blank window if JavaScript is disabled.
     * configuration options:
     * 
     *      'url'               =>  string|array        absolute URL string, or an associative array of module,controller,action names
     *      'attributes'        =>  array               an associative array of additional attributes to set for the generated element
     *      'nojavascript'      =>  bool                do not use JavaScript code, and return just HTML link
     *      'window'            =>  string              JavaScript window.open option string (if javascript is not disabled in configurations)
     * 
     * 
     * @param   array|Zend_Config|object    $config     associative array of configurations.
     * @param   string|null                 $text       the text (or HTML coe) to show for the link text. default is the URL itself.
     * @return  string
     * @throws  Parsonline_Exception_ValueException
     */
    public function openURLInWindowLink($config, $text=null)
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
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException('configurations should be an associative array');
        }
        
        if ( !array_key_exists('url', $config) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException('no url is specified in the configurations');
        }
        
        $attributes = array_key_exists('attributes', $config) ? $config['attributes'] : array();
        if ( !is_array($attributes) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException('attributes configuraion should be array of attr => value pairs');
        }
        
        $attrString = '';
        foreach( $attributes as $attr => $value ) {
            $attrString .= ' ' . $attr . '=" ' . $value . '" ';
        }
        unset($attributes);
        
        $url = $config['url'];
        if ( is_array($url) ) {
            $url = $this->view->url($url);
        } else {
            $url = strval($url);
        }
        
        if (!$text) $text = $url;
        
        $urlScaped = addslashes($url);
        $textScaped = addslashes($text);
        $windowOptions = array_key_exists('window', $config) ? $config['window'] : '';
        $noJavaScript = (array_key_exists('nojavascript',$config) ? $config['nojavascript'] : false) && true;
        
        if ($noJavaScript) {
            $code = <<< END_OF_LINK_CODE
<a href="{$url}" target="_blank"{$attrString}>{$text}</a>
END_OF_LINK_CODE;
        } else {
            $code = <<< END_OF_LINK_CODE_JS
    <script language="javascript" type="text/javascript">
        document.write('<a href="#" onclick="window.open(\'{$urlScaped}\',\'\',\'{$windowOptions}\'); return false;"{$attrString}>{$textScaped}</a>');
    </script>
    <noscript>
        <a href="{$url}" target="_blank"{$attrString}>{$text}</a>
    </noscript>
END_OF_LINK_CODE_JS;
        }
        return $code;
    } // public function openURLInWindowLink()
}