<?php
//Parsonline/Iterator/Filter/DirectoryVideo.php
/**
 * defines the Parsonline_Iterator_Filter_DirecrtoryVideo class.
 *
 * @copyright   Copyright 2010 ParsOnline, Inc.
 * @license     all rights reserved.
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.2 2010-03-10
 */

/**
 * iterator to iterate over video files in a given path.
 * uses SPL DirectoryIterator to iterave over files.
 *
 * @uses    FilterIterator
 * @uses    DirectoryIterator
 */

if (!class_exists('FilterIterator')) throw new Exception("SPL class 'FilterIterator' is not available on execution.");

class Parsonline_Iterator_Filter_DirectoryVideo extends FilterIterator
{
    /**
     *
     * @var array
     */
    protected $_extensions = array('mp4','mov','avi','mpeg','qt','flv','wmv');

    /**
     *
     * @var true
     */
    protected $_checkPermissions = true;

    /**
     * creates an iterator to iterate over video files in a given path
     * 
     * @param  string   $path   director path
     */
    public function __construct($path, $extensions=null)
    {
        if (!class_exists('DirectoryIterator')) throw new Exception("SPL class 'DirectoryIterator' is not available on execution.");
        if (is_array($extensions)) $this->_extensions = $extensions;
        parent::__construct(new  DirectoryIterator($path));
    }

    /**
     * if should check file permissions
     * 
     * @param   bool    $check
     */
    public function setCheckPermissions($check=true)
    {
        $this->_checkPermissions = !!$check;
    }

    /**
     * if a give file is acceptable on the filter or not.
     *
     *
     * @return  bool
     */
    public function accept()
    {
        $valid = true;
        $innerIterator = $this->getInnerIterator();
        if ( $innerIterator->isDot() || $innerIterator->isDir() ) {
            $valid = false;
        } else {
            $fileName = $innerIterator->getPathname();
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            if ( !is_string($ext) || !in_array($ext, $this->_extensions)) $valid = false;
            if ($this->_checkPermissions && !is_readable($fileName) ) $valid = false;
        }
        return $valid;
    }
}
