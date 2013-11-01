<?php

namespace Sokil\Mongo;

class Database
{
    private $_database;
    
    private $_collectionPool = array();
    
    public function __construct(\MongoDb $db) {
        $this->_database = $db;
    }
    
    public function getCollection($name) {
        if(!isset($this->_collectionPool[$name])) {
            $this->_collectionPool[$name] = new Collection($this->_database->selectCollection($name));
        }
        
        return $this->_collectionPool[$name];
    }
}