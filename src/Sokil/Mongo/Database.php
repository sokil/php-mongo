<?php

namespace Sokil\Mongo;

class Database
{
    private $_database;
    
    private $_mapping = array();
    
    private $_collectionPool = array();
    
    public function __construct(\MongoDb $db) {
        $this->_database = $db;
    }
    
    /**
     * Map collection name to class
     * 
     * @param type $name
     * @param type $class
     * @return \Sokil\Mongo\Client
     */
    public function map($name, $class = null) {
        
        if(is_array($name)) {
            $this->_mapping = array_merge($this->_mapping, $name);
        }
        else {
            $this->_mapping[$name] = $class;
        }
        
        return $this;
    }
    
    /**
     * 
     * @param string $name name of collection
     * @return \Sokil\Mongo\Collection
     */
    public function getCollection($name) {
        if(!isset($this->_collectionPool[$name])) {
            
            if(isset($this->_mapping[$name])) {
                $className = $this->_mapping[$name];
            }
            else {
                $className = '\\' . get_called_class() . '\\' . implode('\\', array_map('ucfirst', array_map('strtolower', explode('.', $name))));
            }
                
            if(!class_exists($className)) {
                $className = '\Sokil\Mongo\Collection';
            }
            
            $this->_collectionPool[$name] = new $className($this->_database->selectCollection($name));
        }
        
        return $this->_collectionPool[$name];
    }
}