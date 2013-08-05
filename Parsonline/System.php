<?php
//Parsonline/System.php
/**
 * Defines the Parsonline_System class.
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
 * @package     Parsonline_System
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.7 2012-07-03
 *
 */

/**
 * Parsonline_System
 * 
 * Funciontality to get information about current system.
 */
class Parsonline_System
{
    const OS_TYPE_WINDOWS = 'win';
    const OS_TYPE_UNIX = 'nix';
    const OS_TYPE_MAC_OS = 'mac';
    
    /**
     * Returns type of the operating system.
     * use OS_TYPE_* class constants.
     *
     * @return string
     *		win for Windows
     *		mac for Mac OS
     *		nix for unix like systems
     */
    public static function getOsType()
    {
        $os = strtolower( PHP_OS );
        if ( strpos($os,'mac') !== false || strpos($os, 'darwin') !== false ) {
            $os = self::OS_TYPE_MAC_OS;
        } elseif ( strpos($os,'win') !== false ) {
            $os = self::OS_TYPE_WINDOWS;
        } else {
            $os = self::OS_TYPE_UNIX;
        }
        return $os;
    }
    
    /**
     * Returns the ammount of memory limit that a PHP script can achieve in bytes.
     *
     * @return int
     */
    public static function getMemoryLimit()
    {
        $maxMemory = strtolower( ini_get('memory_limit') );
        if ( strpos($maxMemory,'k') !== false ) {
            $maxMemory = intval($maxMemory) * 1024;
        } elseif ( strpos($maxMemory,'m') !== false ) {
            $maxMemory = intval($maxMemory) * 1048576;
        } elseif ( strpos($maxMemory,'g') !== false ) {
            $maxMemory = intval($maxMemory) * 1073741824;
        } else {
            $maxMemory = intval($maxMemory);
        }
        return $maxMemory;
    }

    /**
     * Returns the ammount of memory available to be used,
     * base on the maximum memory limit for PHP scripts and the currently
     * memory beeing used by the current script process.
     *
     * @return int
     */
    public static function getAvailableMemory()
    {
        return self::getMemoryLimit() - self::getMemoryUsage();
    }

    /**
     * Returns the ammount of memory currently beeing used by the PHP process,
     * of false.
     * 
     * Note: This is equivalent to use memory_get_usage()
     * Note: This method is for older versions of PHP
     * 
     * @return int
     */
    public static function getMemoryUsage()
    {
        if ( function_exists('memory_get_usage') ) {
            return memory_get_usage(true);
        }
        /*
         * this section is borrowed from a comment in PHP documentation
         * http://www.php.net/manual/en/function.memory-get-usage.php#64156
         */
        $pid = getmypid();
        $exitCode = 0;
        if ( self::getOsType() == self::OS_TYPE_WINDOWS ) {
            $output = array();
            exec('tasklist /FI "PID eq ' . $pid . '" /FO LIST', $output , $exitCode);
            if ($exitCode) {
                return false;
            }
            $currentMemory = intval( preg_replace( '/[\D]/', '', $output[5] ) ); // returns in KB
            $currentMemory *= 1024;
        } else {
            exec("ps -eo%mem,rss,pid | grep {$pid}", $output, $exitCode);
            if ($exitCode) {
                return false;
            }
            $output = explode("  ", $output[0]);
            //rss is given in 1024 byte units
            $currentMemory = intval($output[1]); // in KB
            $currentMemory *= 1024;
        }
        return $currentMemory;
    } // public static function getMemoryUsage()
    
    /**
     * Returns newline character (end of line)
     * based on the operating system.
     * 
     * Note: This is equivalent to use PHP_EOL constant.
     * Note: This method is for backward compatibility
     * 
     * @return  string
     */
    public static function getNewLine()
    {
        if ( defined('PHP_EOL') ) return PHP_EOL;
        $os = self::getOsType();
        switch ($os) {
            case self::OS_TYPE_MAC_OS:
                return "\r";
            case self::OS_TYPE_WINDOWS:
                return "\r\n";
            default:
                return "\n";
        }
    } // public static function getNewLine()

    /**
     * Returns the path to system temp directory.
     *
     * Note: This is equivalent to use sys_get_temp_dir()
     * Note: This method is for older versions of PHP
     * 
     * @return  string|false
     */
    public static function getTempDir()
    {
        if ( !function_exists('sys_get_temp_dir') ) {
            $variables = array('TMP', 'TEMP', 'TMPDIR', 'TEMPDIR');
            foreach($variables as $var) {
                $value = getenv($var);
                if ($value) {
                    return $value;
                }
            }
            return false;
        }
        return sys_get_temp_dir();
    }
}