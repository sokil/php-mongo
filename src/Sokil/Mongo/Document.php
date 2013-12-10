<?php

namespace Sokil\Mongo;

class Document
{
    private $_data = array();
    
    public function __construct(array $data = null)
    {        
        if($data) {
            $this->_data = array_merge($this->_data, $data);
        }
    }
    
    public function getId()
    {
        return isset($this->_data['_id']) ? $this->_data['_id'] : null;
    }
    
    public function setId($id) {
        if($id instanceof \MongoId) {
            $this->_data['_id'] = $id;
        }
        
        else {
            $this->_data['_id'] = new \MongoId((string) $id);
        }
        
        return $this;
    }
    
    public function rules()
    {
        return array();
    }
    
    public function __get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }
    
    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }
    
    public function get($selector)
    {
        if(false === strpos($selector, '.')) {
            return  isset($this->_data[$selector]) ? $this->_data[$selector] : null;
        }

        $value = $this->_data;
        foreach(explode('.', $selector) as $field)
        {
            if(!isset($value[$field])) {
                return null;
            }

            $value = $value[$field];
        }

        return $value;
    }
    
    public function set($selector, $value)
    {
        if(!$selector) {
            throw new Exception('Selector not specified');
        }
        
        $arraySelector = explode('.', $selector);
        $chunksNum = count($arraySelector);
        
        // optimize one-level selector search
        if(1 == $chunksNum)
        {
            $this->_data[$selector] = $value;
            
            return $this;
        }
        
        // selector is nested
        $section = &$this->_data;

        for($i = 0; $i < $chunksNum - 1; $i++)
        {

            $field = $arraySelector[$i];

            if(!isset($section[$field]))
            {
                $section[$field] = array();
            }

            $section = &$section[$field];

        }
        
        $section[$arraySelector[$chunksNum - 1]] = $value;
        
        return $this;
    }
    
    public function toArray()
    {
        return $this->_data;
    }
}