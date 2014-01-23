<?php

namespace Sokil\Mongo;

class Structure
{
    protected $_data = array();
    
    protected $_modifiedFields = array();
    
    public function __construct(array $data = null)
    {
        if($data) {
            $this->fromArray($data);
        }
    }
    
    public function __get($name)
    {
        return $this->get($name);
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
    
    /**
     * Get structure object from adocument's value
     * 
     * @param string $selector
     * @param string|closure $className string classname or closure, which accept data and return string class name
     * @return object representation of document with class, passed as argument
     * @throws \Sokil\Mongo\Exception
     */
    public function getObject($selector, $className)
    {
        $data = $this->get($selector);
        if(!$data) {
            return null;
        }
        
        // get classname from callable
        if(is_callable($className)) {
            $className = $className($data);
        }
        
        // prepare structure
        $structure =  new $className();
        if(!($structure instanceof Structure)) {
            throw new Exception('Wring structure class specified');
        }
        
        return clone $structure->fromArray($data);
    }
    
    /**
     * Get list of structure objects from list of values in mongo document
     * 
     * @param string $selector
     * @param string|closure $className string classname or closure, which accept data and return string class name
     * @return object representation of document with class, passed as argument
     * @throws \Sokil\Mongo\Exception
     */
    public function getObjectList($selector, $className)
    {
        $data = $this->get($selector);
        if(!$data) {
            return array();
        }
        
        // classname is string
        if(is_string($className)) {
            
            $structure = new $className();
            if(!($structure instanceof Structure)) {
                throw new Exception('Wring structure class specified');
            }

            return array_map(function($dataItem) use($structure) {
                return clone $structure->fromArray($dataItem);
            }, $data);
        }
        
        // classname id callable
        if(is_callable($className)) {
            
            $structurePool = array();

            return array_map(function($dataItem) use($structurePool, $className) {
                
                $classNameString = $className($dataItem);
                if(empty($structurePool[$classNameString])) {
                    $structurePool[$classNameString] = new $classNameString;
                    if(!($structurePool[$classNameString] instanceof Structure)) {
                        throw new Exception('Wring structure class specified');
                    }
                }
                
                return clone $structurePool[$classNameString]->fromArray($dataItem);
            }, $data);
        }
        
        throw new Exception('Wrong class name specified. Use string or closure');
    }
    
    /**
     * Handle setting params through public property
     * 
     * @param type $name
     * @param type $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
    
    /**
     * Store value to specified selector in local cache
     * 
     * @param type $selector
     * @param type $value
     * @return \Sokil\Mongo\Document
     * @throws Exception
     */
    public function set($selector, $value)
    {
        // mark field as modified
        $this->_modifiedFields[] = $selector;
        
        // modify
        $arraySelector = explode('.', $selector);
        $chunksNum = count($arraySelector);
        
        // optimize one-level selector search
        if(1 == $chunksNum) {
            $this->_data[$selector] = $value;
            
            return $this;
        }
        
        // selector is nested
        $section = &$this->_data;

        for($i = 0; $i < $chunksNum - 1; $i++) {

            $field = $arraySelector[$i];

            if(!isset($section[$field])) {
                $section[$field] = array();
            }

            $section = &$section[$field];
        }
        
        // update local field
        $section[$arraySelector[$chunksNum - 1]] = $value;
        
        return $this;
    }
    
    public function append($selector, $value)
    {
        $oldValue = $this->get($selector);
        if($oldValue) {
            if(!is_array($oldValue)) {
                $oldValue = (array) $oldValue;
            }
            $oldValue[] = $value;
            $value = $oldValue;
        }
        
        $this->set($selector, $value);
        return $this;
    }
    
    public function isModified($selector)
    {
        if(!$this->_modifiedFields) {
            return false;
        }
        
        foreach($this->_modifiedFields as $modifiedField) {
            if(preg_match('/^' . $selector . '($|.)/', $modifiedField)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getModifiedFields()
    {
        return $this->_modifiedFields;
    }
        
    public function toArray()
    {
        return $this->_data;
    }
    
    public function fromArray(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
        
        return $this;
    }
}