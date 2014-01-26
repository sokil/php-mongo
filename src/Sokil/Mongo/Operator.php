<?php

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
        
        $this->_operators['$set'][$fieldName] = $value;
        
        return $this;
    }
    
    public function push($fieldName, $value)
    {
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
    }
    
    public function pushEach($fieldName, array $value)
    {
        // no $push operator found
        if(!isset($this->_operators['$push'])) {
            $this->_operators['$push'] = array();
        }
        
        // no field name found
        if(!isset($this->_operators['$push'][$fieldName])) {
            $this->_operators['$push'][$fieldName] = array(
                '$each' => $value
            );
        }
        
        // field name found and has single value
        else if(!isset($this->_operators['$push'][$fieldName]['$each'])) {
            $oldValue = $this->_operators['$push'][$fieldName];
            $this->_operators['$push'][$fieldName] = array(
                '$each' => array_merge(array($oldValue), $value)
            );
        }
        
        // field name found and already $each
        else {
            $this->_operators['$push'][$fieldName]['$each'] = array_merge(
                $this->_operators['$push'][$fieldName]['$each'],
                $value
            );
        }
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