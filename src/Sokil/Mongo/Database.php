<?php

namespace Sokil\Mongo;

class Database
{
    private $_database;
    
    private $_collectionPool = array();
    
    public function __construct(\MongoDb $db) {
        $this->_database = $db;
    }
    
    /**
     * 
     * @param string $name name of collection
     * @return \Sokil\Mongo\Collection
     */
    public function getCollection($name) {
        if(!isset($this->_collectionPool[$name])) {
            
            $className = '\\' . get_called_class() . '\\' . implode('\\', array_map('ucfirst', array_map('strtolower', explode('.', $name))));
            if(!class_exists($className)) {
                $className = '\Sokil\Mongo\Collection';
            }
            
            $this->_collectionPool[$name] = new $className($this->_database->selectCollection($name));
        }
        
        return $this->_collectionPool[$name];
    }
}