<?php
//Parsonline/ZF/Form/Decorator/AnchorElement.php
/**
 * Defines Parsonline_ZF_Form_Decorator_AnchorElement class.
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
 * @package     Parsonline_ZF_Form
 * @subpackage  Decorator
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.1 2011-01-04
 */

/**
 * @uses    Zend_Form_Decorator_Abstract
 */
require_once('Zend/Form/Decorator/Abstract.php');

/**
 * Parsonline_ZF_Form_Decorator_AnchorElement
 * 
 * Adds an HTML anchor for a form element.
 * Accepts these options:
 *  - name: the name of the anchor (or would be the ID or if not, name of the element
 *  - namePrefix:   name prefix for the anchor name.
 */
class Parsonline_ZF_Form_Decorator_AnchorElement extends Zend_Form_Decorator_Abstract
{
    /**
     * The default name prefix for all anchors.
     * 
     * @staticvar string
     */
    protected static $_defaultNamePrefix = '';
    
    /**
     * Default placement: prepend
     * 
     * @var string
     */
    protected $_placement = self::PREPEND;
    
    /**
     * Returns the default name prefix for all anchors.
     * 
     * @return  string
     */
    public static function getDefaultNamePrefix()
    {
        return self::$_defaultNamePrefix;
    }
    
    /**
     * Sets the default name prefix for all anchors.
     * 
     * @param   string  $name 
     */
    public static function setDefaultNamePrefix($name)
    {
        self::$_defaultNamePrefix = strval($name);
    }
    
    /**
     * Returns the prefix of the name of the anchor, which is stored as
     * the namePrefix option.
     * 
     * 
     * @return  string
     */
    public function getNamePrefix()
    {
        $prefix = $this->getOption('namePrefix');
        if (empty($prefix)) {
            $prefix = self::$_defaultNamePrefix;
            $this->setOption('namePrefix', $prefix);
        }
        return $prefix;
    }
    
    /**
     * Sets the prefix of the name of the anchor, which is stored as the
     * namePrefix option.
     * 
     * 
     * @param string    $prefix
     * @return Parsonline_ZF_Form_Decorator_AnchorElement 
     */
    public function setNamePrefix($prefix)
    {
        $this->setOption('namePrefix', strval($prefix));
        return $this;
    }
    
    /**
     * Returns the name of the anchor, which is stored in the name option.
     * 
     * @return string
     */
    public function getName()
    {
        $name = $this->getOption('name');
        if (!$name) {
            $el = $this->getElement();
            $id = $el->getId();
            if (!$id) $id = $el->getFullyQualifiedName();
            $name  = $this->getNamePrefix() . $id;
            $this->setName($name);
        }
        return $name;
    } // public function getName()
    
    /**
     * Sets the name of the anchor, which is stored in the name option.
     * 
     * @param string    $name
     * @return Parsonline_ZF_Form_Decorator_AnchorElement 
     */
    public function setName($name)
    {
        $this->setOption('name', $name);
        return $this;
    }
    
    /**
     * Returns a string of HTML code for the anchor.
     * 
     * @return  string
     */
    public function getAnchor()
    {
        $name = $this->getName();
        return "<a name=\"{$name}\">";
    }
        
    /**
     * Adds an anchor element to the content.
     * 
     * @param   string  $content
     * @return  string
     */
    public function render($content)
    {
        $placement = $this->getPlacement();
        $separator = $this->getSeparator();
        $anchor = $this->getAnchor();
        
        switch($placement) {
            case self::APPEND:
                return $content . $separator . $anchor;
            default:
                return $anchor . $separator . $content;
        }   
    } // public function render()    
}
