<?php
//Parsonline/Utils/ArrayCalculations.php
/**
 * Defines Parsonline_Utils_ArrayCalculations class.
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
 * @package     Utils
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.8 2012-07-03
 */

/**
 * ArrayCalculations
 *
 * Capsulates functionality to work with array data.
 */
class Parsonline_Utils_ArrayCalculations
{
    const NORMALIZE_ACTION_DROP = 0;
    const NORMALIZE_ACTION_FLATTERN = 1;

    /**
     * Counts all items in the array, walks into nested arrays.
     * Accepts a callable filter, the filter is passed each element.
     * If the filter returns true, the element is counted.
     * 
     * might count sub array indexes (as containers of other values) as well.
     * If so the result of the total count would also include the top level
     * container (which is the array parameter), and would always be more than 1.
     * 
     * @param   array       $array
     * @param   callable    $filter
     * @param   bool        $countContainers        count arrays of other values as well
     * @return  int
     */
    public static function getDeepCount($array, $filter=null, $countContainers=false)
    {
        if ( !is_array($array) ) return 1;
        if ($countContainers) {
            $num = 1;
        } else {
            $num = 0;
        }
        foreach ($array as $index) {
            if ( is_array($index) ) {
                $num += self::getDeepCount($index, $filter, $countContainers);
            } else {
                if ($filter && !call_user_func($filter, $index)) continue;
                $num++;
            }
        }
        
        return $num;
    } // public static function getDeepCount()

    /**
     * calculates sum of all items in the array, walks into nested arrays.
     * 
     * @param   array $array
     * @return  int
     */
    public static function getDeepSum($array)
    {
        $sum = 0;
        if ( !is_array($array) )  return $sum += $array;
        foreach ($array as $index) {
            if ( !is_array($index) ) {
                $sum += $index;
            } else {
                $sum += self::getDeepSum($index);
            }
        }
        return $sum;
    }

    /**
     * returns the first value in an array.
     *
     * @param   array   $array
     * @return  mixed
     */
    public static function getFirstValue($array)
    {
        if (!is_array($array)) return $array;
        reset($array);
        $first = current($array);
        if ( !is_array($first) ) {
            return $first;
        }
        return self::getFirstValue($first);
    }


    /**
     * calculates mean of all items in the array, walks into nested arrays.
     *
     * @param   array $array
     * @return  float|null          null if there was no value
     */
    public static function getDeepMean($array)
    {
        $num = self::getDeepCount($array);
        if ($num == 0) return null;
        return (self::getDeepSum($array) / $num);
    }

    /**
     * normalize values in an array and returns the new array. accepts a factor
     * parameter. this factor is multiplied by the standard deviation to calculate
     * the target space that contains all the output items.
     * 
     * NOTE: converts string values to numbers, but null and boolean values
     * are untouched.
     *
     *
     * @param   array   $array      the array to normalize the data
     * @param   int     $factor     the factor of normalization. the more this value is, the less data will be dropped out of the array. default is 3 (calc 6sigma)
     * @param   int     $action     the to take on out of scope values. default is to drop them. use class constants for this.
     * @return  array
     * @throws  Parsonline_Exception_SystemException if statistics extension is not installed
     * @throws  Parsonline_Exception_ValueException on negative value for factor
     */
    public static function normalize(array $array, $factor=3, $action=self::NORMALIZE_ACTION_DROP)
    {
        if ( !function_exists('stats_standard_deviation') ) {
            /**
             *@uses  Parsonline_Exception_SystemException
             */
            require_once("Parsonline/Exception/SystemException.php");
            throw new Parsonline_Exception_SystemException('statistic functions are not available. try installing PHP statistics extension');
        }
        if ($factor < 0) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException(
                "Invalid factor parameter. factor should be a none negative integer"
            );
        }
        $dev = round(floatval($factor) * stats_standard_deviation($array), 3);
        $mean = self::getDeepMean($array);
        $min = $mean - $dev;
        $max = $mean + $dev;
        $results = array();
        foreach($array as $value) {
            if ( !is_null($value) && !is_bool($value) ) {
                $value = floatval($value);
                if ( ($value < $min || $value > $max) ) {
                    switch ($action) {
                        case self::NORMALIZE_ACTION_FLATTERN:
                                    $value = ($value < $min) ? $min : $max;
                                    break;
                        // in drop action, continue to next childArray and skip pushing this one into results
                        case self::NORMALIZE_ACTION_DROP:
                        default:
                            continue 2;
                    }
                }
            }
            array_push($results, $value);
        }
        return $results;
    } // public static function getNormalized()
    
    /**
     * normalize values in an array of arrays and returns the new array. an index
     * should be specified, that is the index number of target data in the child arrays
     * whose values should be normalized. accepts another parametr, a factor
     * parameter. this factor is multiplied by the standard deviation to calculate
     * the target space that contains all the output items.
     * NOTE: drops non-array members, or arrays without a member in the specified index
     * NOTE: converts string values to numbers, but null and boolean values
     * are untouched.
     *
     *
     * @param   array   $array      the array of arrays
     * @param   int     $index      index of target data in the child arrays
     * @param   int     $factor     the factor of normalization. the more this value is, the less data will be dropped out of the array. default is 3 (calc 6sigma)
     * @param   int     $action     the to take on out of scope values. default is to drop them. use class constants for this.
     * @return  array
     * @throws  Parsonline_Exception_SystemException if statistics extensions are not installed
     * @throws  Parsonline_Exception_ValueException on negative factor or index values
     */
    public static function normalized2DimentionalArray(array $array, $index=0, $factor=3, $action=self::NORMALIZE_ACTION_DROP)
    {
        if ( !function_exists('stats_standard_deviation') ) {
            /**
             *@uses  Parsonline_Exception_SystemException
             */
            require_once("Parsonline/Exception/SystemException.php");
            throw new Parsonline_Exception_SystemException('statistic functions are not available. try installing PHP statistics extension');
        }
        if ( $index < 0 ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException(
                "Invalid index parameter. index should be a none negative integer"
            );
        }
        if ( $factor < 0 ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException(
                "Invalid factor parameter. factor should be a none negative integer"
            );
        }
        $index = intval($index);
        $factor = floatval($factor);
        
        $listOfData = array();
        $cleanArray = array();
        $results = array();
        $value = null;
        
        foreach ($array as $childArray) {
            if ( !is_array($childArray) || !isset($childArray[$index]) || is_array($childArray[$index]) ) continue; // drop bad children
            $value = $childArray[$index];
            array_push($cleanArray, $childArray);
            if (!is_null($value) && !is_bool($value)) array_push($listOfData, floatval($value));
        }
        unset($value, $childArray, $array);

        $dev = round( floatval($factor) * stats_standard_deviation($listOfData), 3 ) / 2;
        $mean = round( self::getDeepMean($listOfData), 3 );
        $min = $mean - $dev;
        $max = $mean + $dev;

        foreach($cleanArray as $childArray) {
            $value = $childArray[$index];
            if ( !is_null($value) && !is_bool($value) ) {
                $value = floatval($value);
                if ( ($value < $min || $value > $max) ) {
                    switch ($action) {
                        case self::NORMALIZE_ACTION_FLATTERN:
                                    $value = ($value < $min) ? $min : $max;
                                    $childArray[$index] = $value;
                                    break;
                        // in drop action, continue to next childArray and skip pushing this one into results
                        case self::NORMALIZE_ACTION_DROP:
                        default:
                            continue 2;
                    }
                }
            }
            array_push($results, $childArray);
        }
        return $results;
    } // public static function normalized2DimentionalArray()
}