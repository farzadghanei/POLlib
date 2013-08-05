<?php
//Parsonline/ZF/Form/ElementModifier/AddTitle.php
/**
 * Defines Parsonline_ZF_Form_ElementModifier_AddTitle class.
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
 * @subpackage  ElementModifier
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     3.0.0 2010-12-28
 */

/**
 * @uses    Parsonline_ZF_Form_ElementModifier_Abstract
 * @uses    Parsonline_ZF_Form_IElementModifier
 */
require_once('Parsonline/ZF/Form/ElementModifier/Abstract.php');
require_once('Parsonline/ZF/Form/IElementModifier.php');

/**
 * Parsonline_ZF_Form_ElementModifier_AddTitle
 * 
 * Modifies a form element by adding values to the element title.
 */
class Parsonline_ZF_Form_ElementModifier_AddTitle extends Parsonline_ZF_Form_ElementModifier_Abstract
implements Parsonline_ZF_Form_IElementModifier
{
    const APPEND = 'append';
    const PREPEND = 'prepend';
    
    /**
     * Constructor.
     * 
     * @param   array|Zend_Config   $options 
     */
    public function __construct($options=null)
    {
        $this->_options['titles'] = array();
        $this->_options['separator'] = '. ';
        $this->_options['placement'] = self::APPEND;
        parent::__construct($options);
    }
    
    /**
     * Returns the placement of the new titles.
     * 
     * @return  string
     */
    public function getPlacement()
    {
        return $this->getOption('placement');
    }
    
    /**
     * Sets the placement of the new titles.
     * 
     * @param   string      $place
     * @return  Parsonline_ZF_Form_ElementModifier_AddTitle
     */
    public function setPlacement($place)
    {
        $this->setOption('placement', $place);
        return $this;
    }
    
    /**
     * Returns the string used to separate titles.
     * 
     * @return  string
     */
    public function getSeparator()
    {
        return $this->getOption('separator');
    }
    
    /**
     * Sets the string used to separate titles.
     * 
     * @param   string      $sep
     * @return  Parsonline_ZF_Form_ElementModifier_AddTitle
     */
    public function setSeparator($sep)
    {
        $this->setOption('separator', $sep);
        return $this;
    }
    
    /**
     * Returns an array of titles
     * 
     * @return  array
     */
    public function getTitles()
    {
        if ( !array_key_exists('titles', $this->_options) ) {
            $this->_options['titles'] = array();
        }
        return $this->getOption('titles');
    }
    
    /**
     * Adds a group of titles to the list of target titles
     * 
     * @param array|string  $titles
     * @return Parsonline_ZF_Form_ElementModifier_AddTitle 
     */
    public function addTitles($titles)
    {
        if ( !is_array($titles) ) $titles = array($titles);
        if ( !array_key_exists('titles', $this->_options) ) {
            $this->_options['titles'] = array();
        }
        foreach ($titles as $t) {
            array_push($this->_options['titles'], $t);
        }
        return $this;
    }
    
    /**
     * Removes a group of titles from the list of target titles
     * 
     * @param array|string  $titles
     * @return Parsonline_ZF_Form_ElementModifier_AddTitle 
     */
    public function removeTitles($titles)
    {
        if (!is_array($titles)) $titles = array(strval($titles));
        if ( !array_key_exists('titles', $this->_options) ) {
            $this->_options['titles'] = array();
        }
        foreach ($titles as $t) {
            $pos = array_search($t, $this->_options['titles']);
            if ( $pos === false) continue;
            unset($this->_options['titles'][$pos]);
        }
        return $this;
    }
    
    /**
     * Sets the group of titles as the list of target titles
     * 
     * @param array $titles
     * @return Parsonline_ZF_Form_ElementModifier_AddTitle 
     */
    public function setTitles(array $titles)
    {
        $this->removeOption('titles');
        $this->addTitles($titles);
        return $this;
    }
    
    /**
     * Modifies the form element object by appending the titles to it.
     * If the element has no title attribute, it would be created for it.
     * 
     * @return  Parsonline_ZF_Form_ElementModifier_AddTitle
     * @throws  Parsonline_Exception_ContextException
     */
    public function modifyFormElement()
    {
        $el = $this->getElement();
        if (!$el) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "No form element is assigned to the element modifier yet"
            );
        }
        $separator = $this->getSeparator();
        $additionalTitles = implode($separator, $this->getTitles());
        if (!$additionalTitles) return $this;
        
        $title = $el->getAttrib('title');
        $placement = $this->getPlacement();
        
        if (!$title) {
            $newTitle = $additionalTitles;
        } elseif ($placement === self::PREPEND) {
            $newTitle = $additionalTitles . $separator . $title;
        } else {
            if (!strrpos($title, $separator)) $title .= $separator;
            $newTitle = $title . $additionalTitles;
        }
        
        $el->setAttrib('title', $newTitle);
        return $this;
    } // public function modifyFormElement()
}