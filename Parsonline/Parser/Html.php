<?php
//Parsonline/Parser/Html.php
/**
 * Defines the Parsonline_Parser_Html.
 *
 * Parsonline
 * 
 * Copyright (c) 2012 ParsOnline, Inc. (www.parsonline.com)
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
 * @copyright  Copyright (c) 2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parser
 * @version     0.0.5 2012-07-22
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 */

/**
 * Parsonline_Parser_Html
 * 
 * Parses an HTML web page.
 */
class Parsonline_Parser_Html
{
    /**
     * The URL of the HTML page
     * 
     * @var string
     */
    protected $_url = null;
    
    /**
     * The contents of the HTML page
     * 
     * @var string
     */
    protected $_contents = null;
    
    /**
     * The dom document that represents the HTML contents
     * 
     * @var DOMDocument 
     */
    protected $_dom = null;
        
    /**
     * Constructor
     * 
     * Creates a new HTML parser for the specified HTML document
     * 
     * @param   string  $html   [optional]
     * @param   string  $url    [optional]
     */
    public function __construct($html=null, $url=null)
    {
        if ($url) $this->setURL($url);
        if ($html) $this->setContents($html);
    }
    
    /**
     * Returns the URL of the web page
     * 
     * @return string
     */
    public function getURL()
    {
        return $this->_url;
    }
    
    /**
     * Sets the URL of the web page
     * 
     * @param   string  $url 
     * @return  Parsonline_Parser_Html
     */
    public function setURL($url)
    {
       $this->_url = (string) $url;
       return $this;
    }
    
    /**
     * Returns the contents of the web page
     * 
     * @return string
     */
    public function getContents()
    {
        return $this->_contents;
    }
    
    /**
     * Sets the contents of the web page
     * 
     * @param   string  $contents 
     * @return  Parsonline_Parser_Html
     * @throws  Parsonline_Exception_ValueException on invalid HTML
     */
    public function setContents($contents)
    {
       $this->_contents = (string) $contents;
       $this->_dom = $this->getDOMDocment(true);
       return $this;
    }
    
    /**
     * Returns the DOMDocument object representing the HTML contents.
     * 
     * @param   bool     $rebuild   [optional]
     * @return  DOMDocument
     * @throws  Parsonline_Exception_ValueException on invalid HTML
     */
    public function getDOMDocment($rebuild=false)
    {
        if (!$this->_dom || $rebuild) {
            /**
             *@uses DOMDocument 
             */
            $dom = new DOMDocument();
            if ($this->_contents) {
                if (!$dom->loadHTML($this->_contents)) {
                    /**
                    * @uses Parsonline_Exception_ValueException
                    */
                    require_once('Parsonline/ExceptionValueException.php');
                    throw new Parsonline_Exception_ValueException(
                        "Faield to parse HTML contents. data is not valid HTML"
                    );
                }
            }
            $this->_dom = &$dom;
        }
        return $this->_dom;
    }
        
    /**
     * Parses image references out of the contents of the HTML page.
     * Returns an array of references.
     *
     * Note: the references might be relative or absolute URLs.
     * 
     * @return  array
     */
    public function parseImageReferences()
    {
        $references = array();
        $dom = $this->getDOMDocment();
        if ($dom) {
            $images = $dom->getElementsByTagName('img');
            /*@var $images DOMNodeList */
            if ($images && $images->length) {
                for ($i = 0; $i < $images->length; $i++) {
                    $img = $images->item($i);
                    $ref = $img->getAttribute('src');
                    if ($ref) $references[] = $ref;
                    unset($ref, $img);
                }
            }
        }
        return $references;
    }
        
    /**
     * Parses script references out of the contents of the HTML page.
     * Returns an array of references.
     *
     * Note: the references might be relative or absolute URLs.
     * 
     * @return  array
     */
    public function parseScriptReferences()
    {
        $references = array();
        $dom = $this->getDOMDocment();
        if ($dom) {
            $scripts = $dom->getElementsByTagName('script');
            /*@var $scripts DOMNodeList */
            if ($scripts && $scripts->length) {
                for ($i = 0; $i < $scripts->length; $i++) {
                    $script = $scripts->item($i);
                    $ref = $script->getAttribute('src');
                    if ($ref) $references[] = $ref;
                }
                unset($ref, $script);
            }
        }
        return $references;
    }
    
    /**
     * Parses link references out of the contents of the HTML page.
     * Returns an array of references.
     *
     * Note: the references might be relative or absolute URLs.
     * 
     * @return  array
     */
    public function parseLinkReferences()
    {
        $references = array();
        $dom = $this->getDOMDocment();
        if ($dom) {
            $links = $dom->getElementsByTagName('link');
            /*@var $links DOMNodeList */
            if ($links && $links->length) {
                for ($i = 0; $i < $links->length; $i++) {
                    $link = $links->item($i);
                    $ref = $link->getAttribute('href');
                    if ($ref) $references[] = $ref;
                }
                unset($ref, $link);
            }
        }
        return $references;
    }
    
    /**
     * Parses style sheet references out of the contents of the HTML page.
     * Returns an array of references.
     *
     * Note: the references might be relative or absolute URLs.
     * 
     * @return  array
     */
    public function parseStyleSheetReferences()
    {
        $references = array();
        $dom = $this->getDOMDocment();
        if ($dom) {
            $links = $dom->getElementsByTagName('link');
            /*@var $links DOMNodeList */
            if ($links && $links->length) {
                for ($i = 0; $i < $links->length; $i++) {
                    $stylesheet = $links->item($i);
                    $rel = $stylesheet->getAttribute("rel");
                    $type = $stylesheet->getAttribute("type");
                    if (strtolower($rel) == 'stylesheet' || strtolower($type) == 'text/css') {
                        $ref = $stylesheet->getAttribute('href');
                        if ($ref) $references[] = (string) $ref;
                    }
                }
                unset($ref, $stylesheet);
            }
        }
        return $references;
    }
    
    /**
     * Converts a reference to an absolute URL. accepts the reference, and the
     * base URL of the path referencing the file.
     * 
     * @param   string  $reference  the reference (relative URL)
     * @param   string  $pageURL    the absolute URL to the web page containing reference
     * @param   string  $baseURL    [optional] the base URL of the HTML page
     * @return  string
     * @throws  Parsonline_Exception_ValueException on base URL without scheme/host
     * @TODO    currently does not handle upward references and works as simple
     *          as just concating the values
     */
    public function convertReferenceToURL($reference, $pageURL, $baseURL=null)
    {
        $url = '';
        $pageBaseURL = $this->getBaseURL($pageURL);
        if (!$pageBaseURL) {
            /**
             *@uses Parsonline_Exception_ValueException 
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("Failed to parse page URL. URL '{$pageURL}' is not valid");
        }
        
        if (!$baseURL) {
            $baseURL = $this->getDocumentBaseURL($pageURL);
        }
        
        if ( $reference['0'] == '/') {
            $url = $pageBaseURL . $reference;
        } else {
            $url = $baseURL . '/' . $reference;
        }
        return $url;
    }
    
    /**
     * Returns the base part of a URL, this includes the scheme, user and 
     * password and host.
     * 
     * Returns null on invalid URLs.
     * 
     * Note: the difference between this method and getDocumentBaseURL()
     * is that this method does not include path section at all, and
     * does not use contents to detect base.
     * 
     * @param   string  $url
     * @return  string|null
     * @see     getDocumentBaseURL()
     */
    public function getBaseURL($url)
    {
        $parts = parse_url($url);
        if ( !isset($parts['scheme']) || !isset($parts['host']) ) {
            return null;
        }
        $baseURL = $parts['scheme'] . "://";
        if ( isset($parts['user']) && $parts['user'] ) {
            $baseURL .= $parts['user'];
            if ( isset($parts['pass']) && $parts['pass'] ) $baseURL .= ':' . $parts['pass'];
            $baseURL .= '@';
        }
        $baseURL .= $parts['host'];
        return $baseURL;
    }
    
    /**
     * Returns the base URL of the document, if the contents of the document is
     * provided, searches for the HTML base tag.
     * Otherwise produces the base URL from the URL of the document (the first
     * parameter).
     * 
     * If failed to convert to base URL, returns null. This case happens where
     * the contents do not have a valid HTML base tag, and the URL is not
     * an absolute URL.
     * 
     * 
     * @param   string  $url
     * @param   string  $contents   [optional]
     * @return  string|null
     * @see     getBaseURL()
     */ 
    public function getDocumentBaseURL($url, $contents=null)
    {
        if ($contents) {
            
            $dom = new DOMDocument();
            if ($dom->loadHTML($contents)) {
                $base = null;
                $bases = $dom->getElementsByTagName('base');
                        
                if ($bases && $bases->length) {
                    $base = $bases->item($bases->length - 1);
                    $baseURL = $base->getAttribute('href');
                    if ($baseURL) {
                        return $base->getAttribute('href');
                    }
                }
                
            }
            unset($dom);
            
            // to support broken HTML pages, use regex if DOM failed
            $basePattern = '/<\s*base\s+.*href\s*=\s*\"?(\S+)\"?\.*/i';
            $matches = array();
            if ( preg_match($basePattern, $contents, $matches) && count($matches) > 1 ) {
                return $matches[1];
            }
            unset($matches, $basePattern);
        } // if ($contenst) ...
        unset($contents);
        
        $base = array();
        $urlParts = parse_url($url);
        if ( !isset($urlParts['scheme']) || !isset($urlParts['host']) ) {
            return null;
        }
        
        $base[] = $urlParts['scheme'] . '://';
        
        if ( isset($urlParts['user']) ) {
            $userPart = $urlParts['user'];
            if ( isset($urlParts['pass']) ) $userPart .= ':' . $urlParts['pass'];
            $base[] = $userPart . '@';
        }
        
        $base[] = $urlParts['host'];
        
        if ( isset($urlParts['port']) ) {
            $base[] = ':' . $urlParts['port'];
        }
        
        if ( isset($urlParts['path']) ) {
            $path = $urlParts['path'];
            if ($path[strlen($path) - 1] != '/') $path = dirname($path); 
            $base[] = $path;
        }
        
        return implode('', $base);
    } // public function getDocumentBaseURL()
}