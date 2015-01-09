<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

class Operator
{
        /**
     *
     * @var array list of update operations
     */
    private $_operators = array();
    
    public function set($fieldName, $value)
    {        
        if(!isset($this->_operators['$set'])) {
            $this->_operators['$set'] = array();
        }
        
        $this->_operators['$set'][$fieldName] = Structure::prepareToStore($value);
        
        return $this;
    }
    
    public function push($fieldName, $value)
    {
        // value must be list, not dictionary
        if(is_array($value)) {
            $value = array_values($value);
        }

        // prepasre to store
        $value = Structure::prepareToStore($value);
        
        // no $push operator found
        if(!isset($this->_operators['$push'])) {
            $this->_operators['$push'] = array();
        }
        
        // no field name found
        if(!isset($this->_operators['$push'][$fieldName])) {
            $this->_operators['$push'][$fieldName] = $value;
        }
        
        // field name found and has single value
        else if(!is_array($this->_operators['$push'][$fieldName]) || !isset($this->_operators['$push'][$fieldName]['$each'])) {
            $oldValue = $this->_operators['$push'][$fieldName];
            $this->_operators['$push'][$fieldName] = array(
                '$each' => array($oldValue, $value)
            );
        }
        
        // field name found and already $each
        else {
            $this->_operators['$push'][$fieldName]['$each'][] = $value;
        }

        return $this;
    }
    
    public function pushEach($fieldName, array $values)
    {
        // value must be list, not dictionary
        $values = array_values($values);

        // prepasre to store
        $values = Structure::prepareToStore($values);

        // no $push operator found
        if(!isset($this->_operators['$push'])) {
            $this->_operators['$push'] = array();
        }
        
        // no field name found
        if(!isset($this->_operators['$push'][$fieldName])) {
            $this->_operators['$push'][$fieldName] = array(
                '$each' => $values
            );
        }
        
        // field name found and has single value
        else if(!is_array($this->_operators['$push'][$fieldName]) || !isset($this->_operators['$push'][$fieldName]['$each'])) {
            $oldValue = $this->_operators['$push'][$fieldName];
            $this->_operators['$push'][$fieldName] = array(
                '$each' => array_merge(array($oldValue), $values)
            );
        }
        
        // field name found and already $each
        else {
            $this->_operators['$push'][$fieldName]['$each'] = array_merge(
                $this->_operators['$push'][$fieldName]['$each'],
                $values
            );
        }

        return $this;
    }

    /**
     * The $slice modifier limits the number of array elements during a
     * $push operation. To project, or return, a specified number of array
     * elements from a read operation, see the $slice projection operator instead.
     * 
     * @link http://docs.mongodb.org/manual/reference/operator/update/slice
     * @param string $field
     * @param int $slice
     * @return \Sokil\Mongo\Operator
     * @throws \Sokil\Mongo\Exception
     */
    public function pushEachSlice($field, $slice)
    {
        $slice = (int) $slice;
        
        if(!isset($this->_operators['$push'][$field]['$each'])) {
            throw new Exception('Field ' . $field . ' must be pushed wit $each modifier');
        }
        
        $this->_operators['$push'][$field]['$slice'] = $slice;
        
        return $this;
    }

    /**
     * The $sort modifier orders the elements of an array during a $push operation.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/update/sort
     * @param string $field
     * @param array $sort
     * @return \Sokil\Mongo\Operator
     * @throws \Sokil\Mongo\Exception
     */
    public function pushEachSort($field, array $sort)
    {
        $slice = (int) $slice;
        
        // add modifiers
        if(!$sort) {
            throw new Exception('Sort condition is empty');
        }
        
        if(!isset($this->_operators['$push'][$field]['$each'])) {
            throw new Exception('Field ' . $field . ' must be pushed with $each modifier');
        }
        
        $this->_operators['$push'][$field]['$sort'] = $slice;
        
        return $this;
    }

    /**
     * The $position modifier specifies the location in the array at which the $push operator insert elements. Without the $position modifier, the $push operator inserts elements to the end of the array. See $push modifiers for more information.
     * 
     * @link http://docs.mongodb.org/manual/reference/operator/update/position
     * @param string $field
     * @param int $position
     * @return \Sokil\Mongo\Operator
     * @throws \Sokil\Mongo\Exception
     */
    public function pushEachPosition($field, $position)
    {
        $position = (int) $position;
        
        // add modifiers
        if($position <= 0) {
            throw new Exception('Position must be greater 0');
        }
        
        if(!isset($this->_operators['$push'][$field]['$each'])) {
            throw new Exception('Field ' . $field . ' must be pushed wit $each modifier');
        }
        
        $this->_operators['$push'][$field]['$position'] = $position;
        
        return $this;
    }
    
    public function increment($fieldName, $value)
    {
        // check if update operations already added
        $oldIncrementValue = $this->get('$inc', $fieldName);
        if($oldIncrementValue) {
            $value = $oldIncrementValue + $value;
        }
        
        $this->_operators['$inc'][$fieldName] = $value;
        
        return $this;
    }
    
    public function pull($fieldName, $value)
    {
        if($value instanceof Expression) {
            $value = $value->toArray();
        }
        
        // no $push operator found
        $this->_operators['$pull'][$fieldName] = $value;
        
        return $this;
    }
    
    public function unsetField($fieldName)
    {
        $this->_operators['$unset'][$fieldName] = '';
        return $this;
    }
    
    public function bitwiceAnd($field, $value)
    {
        $this->_operators['$bit'][$field]['and'] = (int) $value;
        return $this;
    }
    
    public function bitwiceOr($field, $value)
    {
        $this->_operators['$bit'][$field]['or'] = (int) $value;
        return $this;
    }
    
    public function bitwiceXor($field, $value)
    {
        $this->_operators['$bit'][$field]['xor'] = (int) $value;
        
        return $this;
    }
    
    public function isDefined()
    {
        return (bool) $this->_operators;
    }
    
    public function reset()
    {
        $this->_operators = array();
        return $this;
    }
    
    public function get($operation, $fieldName = null)
    {
        if($fieldName) {
            return isset($this->_operators[$operation][$fieldName])
                ? $this->_operators[$operation][$fieldName]
                : null;
        }
        
        return isset($this->_operators[$operation]) 
            ? $this->_operators[$operation]
            : null;
    }
    
    public function getAll()
    {
        return $this->_operators;
    }
    
    public function isReloadRequired()
    {
        return isset($this->_operators['$inc']) || isset($this->_operators['$pull']);
    }
}