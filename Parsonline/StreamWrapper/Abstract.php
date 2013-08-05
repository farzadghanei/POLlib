<?php
//Parsonline/StreamWrapper/Abstract.php
/**
 * Defines Parsonline_StreamWrapper_Abstract class.
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
 * @package     StreamWrapper
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.2 2010-02-10
 */

/**
 * Parsonline_StreamWrapper_Abstract
 *
 * an abstract stream wrapper class that returns false values for all methods.
 * each stream wrapper that implements a method, should process its own return value.
 * this abstract class is a base for wrappers that might not support some protocols.
 * 
 */
abstract class Parsonline_StreamWrapper_Abstract
{
    const STREAM_CAST_FOR_SELECT = 0;
    const STREAM_CAST_AS_STREAM = 1;

    const STREAM_OPTION_BLOCKING = 10;
    const STREAM_OPTION_READ_TIMEOUT = 11;
    const STREAM_OPTION_WRITE_BUFFER = 12;

    const STREAM_USE_PATH = 100; // if path  is relative, search for the resource using the include_path.
    const STREAM_REPORT_ERRORS = 101; // If this flag is set, you are responsible for raising errors using trigger_error() during opening of the stream. If this flag is not set, you should not raise any errors.

    const SEEK_SET = 200;
    const SEEK_CUR = 201;
    const SEEK_END = 202;

    const STREAM_OPENDIR_SAFEMODE_OFF = 300;
    const STREAM_OPENDIR_SAFEMODE_ON = 301;
    const STREAM_MKDIR_RECURSIVE = 301;

    const LOCK_SH = 400; // to acquire a shared lock (reader).
    const LOCK_EX = 401; // to acquire an exclusive lock (writer).
    const LOCK_UN = 402; // to release a lock (shared or exclusive).
    const LOCK_NB = 403; // if you don't want flock() to block while locking. (not supported on Windows)

    const STREAM_BUFFER_NONE = 500;
    const STREAM_BUFFER_FULL = 501;

    const STREAM_URL_STAT_LINK = 601; // For resources with the ability to link to other resource (such as an HTTP Location: forward, or a filesystem symlink). This flag specified that only information about the link itself should be returned, not the resource pointed to by the link. This flag is set in response to calls to lstat(), is_link(), or filetype().
    const STREAM_URL_STAT_QUIET = 602; // If this flag is set, your wrapper should not raise any errors. If this flag is not set, you are responsible for reporting errors using the trigger_error() function during stating of the path.

    /**
     * resource context
     *
     * @var resource
     */
    public $context;    

    /**
     * called by opendir()
     *
     * @param   string  $path
     * @param   int     $options
     * @return  bool
     */
    public function dir_opendir($path, $options)
    {
        return false;
    }

    /**
     * called by readdir()
     *
     * @return  string|false
     */
    public function dir_readdir()
    {
        return false;
    }

    /**
     * called by rewinddir()
     *
     * @return  bool
     */
    public function dir_rewinddir()
    {
        return false;
    }

    /**
     * called by closedir()
     *
     * @return bool
     */
    public function dir_closedir()
    {
        return false;
    }

    /**
     * called by rename()
     *
     * @param   string $from
     * @param   string $to
     * @return  bool
     */
    public function rename($from, $to)
    {
        return false;
    }

    /**
     * called by mkdir()
     *
     * @param   string  $path
     * @param   int     $mode
     * @param   int     $options
     * @return  bool
     */
    public function mkdir($path, $mode, $options)
    {
        return false;
    }

    /**
     * called by rmdir()
     * NOTE: PHP documentation recommends this method should not be defined if the wrapper does not support it.
     * @param   string  $path
     * @param   int     $options
     * @return  bool
     */
    /*
    public function rmdir($path, $options)
    {
        return false;
    }
    */

    /**
     * called by fopen()
     *
     * @param   string  $path
     * @param   string  $mode
     * @param   int     $options
     * @param   string  &$opened_path
     * @return  bool
     */
    public function stream_open($path, $mode, $options, &$openedPath)
    {
        return false;
    }


    /**
     * called by stream_select()
     *
     * @param   int     $castAs
     * @return  resource
     */
    public function stream_cast($castAs)
    {
        return $this->context;
    }

    /**
     * called by fclose()
     *
     */
    public function stream_close()
    {

    }

    /**
     * called by feof()
     *
     * @return  bool
     */
    public function stream_eof()
    {
        return false;
    }

    /**
     * called by fflush()
     *
     * @return  bool
     */
    public function stream_flush()
    {
        return false;
    }

    /**
     * called by flock()
     *
     * @param mode $operation
     * @return bool
     */
    public function stream_lock($operation)
    {
        return false;
    }
    
    /**
     * called by fread()
     *
     * @param   int         $count
     * @return  string|false
     */
    public function stream_read($count)
    {
        return '';
    }

    /**
     * called by fseek()
     *
     * @param   int     $offset
     * @param   int     $whence=SEEK_SET
     * @return  bool
     */
    public function stream_seek($offset, $whence=self::SEEK_SET)
    {
        return false;
    }

    /**
     * called by stream_set_* functions.
     *
     * option can be one of:
     * STREAM_OPTION_BLOCKING (The method was called in response to stream_set_blocking())
     * STREAM_OPTION_READ_TIMEOUT (The method was called in response to stream_set_timeout())
     * STREAM_OPTION_WRITE_BUFFER
     *
     * arg1: If option  is
     * STREAM_OPTION_BLOCKING: requested blocking mode (1 meaning block 0 not blocking).
     * STREAM_OPTION_READ_TIMEOUT: the timeout in seconds.
     * STREAM_OPTION_WRITE_BUFFER: buffer mode (STREAM_BUFFER_NONE or STREAM_BUFFER_FULL).
     *
     * arg2:  If option  is
     * STREAM_OPTION_BLOCKING: This option is not set.
     * STREAM_OPTION_READ_TIMEOUT: the timeout in microseconds.
     * STREAM_OPTION_WRITE_BUFFER: the requested buffer size.
     * 
     * @param   int $option
     * @param   int $arg1
     * @param   int $arg2
     * @return  bool
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        return false;
    }

    /**
     * called by fstat()
     *
     * @return array
     */
    public function stream_stat()
    {
        return false;
    }

    /**
     * called by ftell()
     *
     * @return int
     */
    public function stream_tell()
    {
        return 0;
    }

    /**
     * called by fwrite()
     *
     * @param   string  $data
     * @return  int
     */
    public function stream_write($data)
    {
        return 0;
    }

    /**
     * called by unlink().
     * NOTE: PHP documentation recommends this method should not be defined if the wrapper does not support it.
     *
     * @param   string  $path
     * @return  bool
     */
    /*
    public function unlink($path)
    {
        return false;
    }
    */

    /**
     * called by stat()
     *
     * @param   string  $path
     * @param   int     $flags
     * @return  array
     */
    public function url_stat($path, $flags)
    {
        return array();
    }
}