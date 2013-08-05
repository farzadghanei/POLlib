<?php
//Parsonline/StreamWapper/StreamWapperInterface.php
/**
 * Defines Parsonline_StreamWrapper_StreamWrapperInterface interface.
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
 * Parsonline_StreamWrapper_StreamWrapperInterface
 *
 * a stream wrapper interface that all stream wrapper classes should implement
 * 
 * @see http://www.php.net/manual/en/class.streamwrapper.php
 */
interface Parsonline_StreamWrapper_StreamWrapperInterface
{
    /**
     * called by closedir()
     * 
     * @return bool
     */
    public function dir_closedir();

    /**
     * called by opendir()
     *
     * @param   string  $path
     * @param   int     $options
     * @return  bool
     */
    public function dir_opendir($path , $options);

    /**
     * called by readdir()
     *
     * @return  string
     */
    public function dir_readdir();

    /**
     * called by rewinddir()
     *
     * @return  bool
     */
    public function dir_rewinddir();

    /**
     * called by mkdir()
     *
     * @param   string  $path
     * @param   int     $mode
     * @param   int     $options
     * @return  bool
     */
    public function mkdir($path , $mode , $options);

    /**
     * called by rename()
     *
     * @param   string $path_from
     * @param   string $path_to
     * @return  bool
     */
    public function rename($path_from , $path_to);

    /**
     * called by rmdir()
     * NOTE: PHP documentation recommends this method should not be defined if the wrapper does not support it.
     *
     * @param string $path
     * @param int $options
     * @return bool
     */
    //public function rmdir($path , $options);

    /**
     * called by stream_select()
     *
     * @param int $cast_as
     * @return resource
     */
    public function stream_cast($cast_as);

    /**
     * called by fclose()
     *
     */
    public function stream_close();

    /**
     * called by feof()
     *
     * @return bool
     */
    public function stream_eof();

    /**
     * called by fflush()
     *
     * @return bool
     */
    public function stream_flush();

    /**
     * called by flock()
     *
     * @param mode $operation
     * @return bool
     */
    public function stream_lock($operation);

    /**
     * called by fopen()
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string &$opened_path
     * @return bool
     */
    public function stream_open($path , $mode , $options , &$opened_path);

    /**
     * called by fread()
     *
     * @param int $count
     * @return string
     */
    public function stream_read($count=null);

    /**
     * called by fseek()
     *
     * @param int $offset
     * @param int $whence = SEEK_SET
     * @return bool
     */
    public function stream_seek($offset , $whence = SEEK_SET);

    /**
     * called by
     *
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     * @return bool
     */
    public function stream_set_option($option , $arg1 , $arg2);

    /**
     * called by fstat()
     *
     * @return array
     */
    public function stream_stat();

    /**
     * called by ftell()
     *
     * @return int
     */
    public function stream_tell();

    /**
     * called by fwrite()
     *
     * @param string $data
     * @return int
     */
    public function stream_write($data);

    /**
     * called by unlink()
     * NOTE: PHP documentation recommends this method should not be defined if the wrapper does not support it.
     *
     * @param string $path
     * @return bool
     */
    //public function unlink($path);

    /**
     * called by stat()
     *
     * @param string $path
     * @param int $flags
     * @return array
     */
    public function url_stat($path , $flags);
}