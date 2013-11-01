<?php

namespace Sokil\Mongo;

class Client
{
    private $_connection;
    
    private $_databasePool = array();
    
    public function __construct($dsn, array $options = array("connect" => true)) {
        $this->_connection = new \MongoClient($dsn, $options);
    }
    
    /**
     * 
     * @param tstring $name database name
     * @return \Sokil\Mongo\Database
     */
    public function getDatabase($name) {
        if(!isset($this->_databasePool[$name])) {
            $this->_databasePool[$name] = new Database($this->_connection->selectDB($name));
        }
        
        return $this->_databasePool[$name];
    }
}