<?php
//Parsonline/ZF/Form/ElementModifier/ErrorsInTitle.php
/**
 * Defines Parsonline_ZF_Form_ElementModifier_ErrorsInTitle class.
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
 * @version     1.0.1 2011-01-04
 */

/**
 * @uses    Parsonline_ZF_Form_ElementModifier_AddTitle
 * @uses    Parsonline_ZF_Form_IElementModifier
 */
require_once('Parsonline/ZF/Form/ElementModifier/AddTitle.php');
require_once('Parsonline/ZF/Form/IElementModifier.php');

/**
 * Parsonline_ZF_Form_ElementModifier_ErrorsInTitle
 * 
 * A specific element title modifier, which automatically adds the element
 * error messages to the element title.
 * 
 * @see Parsonline_ZF_Form_ElementModifier_AddTitle
 */
class Parsonline_ZF_Form_ElementModifier_ErrorsInTitle extends Parsonline_ZF_Form_ElementModifier_AddTitle
implements Parsonline_ZF_Form_IElementModifier
{
    /**
     * Modifies the form element object by appending the specified titles to it.
     * And then adding element error messages to the title as well.
     * 
     * If the element has no title attribute, it would be created for it.
     * 
     * @return  Parsonline_ZF_Form_ElementModifier_ErrorsInTitle
     * @throws  Parsonline_Exception_ContextException on no element set
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
        /*@var $el Zend_Form_Element*/
        $titles = array_merge($this->getTitles(), array_values($el->getMessages()));
        $separator = $this->getSeparator();
        $additionalTitles = implode($separator, $titles);
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