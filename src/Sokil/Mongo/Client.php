<?php

namespace Sokil\Mongo;

class Client
{
    private $_connection;
    
    private $_databasePool = array();
    
    /**
     * @var array Database to class mapping
     */
    protected $_mapping = array();
    
    /**
     * 
     * @param type $dsn
     * @param array $options
     */
    public function __construct($dsn, array $options = array('connect' => true)) {
        $this->_connection = new \MongoClient($dsn, $options);
    }
    
    /**
     * Map database name to class
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
     * @param tstring $name database name
     * @return \Sokil\Mongo\Database
     */
    public function getDatabase($name) {
        if(!isset($this->_databasePool[$name])) {
            
            if(isset($this->_mapping[$name])) {
                $className = $this->_mapping[$name];
            }
            else {
                $className = '\Sokil\Mongo\Database';
            }
            
            $this->_databasePool[$name] = new $className($this->_connection->selectDB($name));
        }
        
        return $this->_databasePool[$name];
    }
    
    public function readPrimaryOnly()
    {
        $this->_connection->setReadPreference(\MongoClient::RP_PRIMARY);
        return $this;
    }
    
    public function readPrimaryPreferred(array $tags = null)
    {
        $this->_connection->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readSecondaryOnly(array $tags = null)
    {
        $this->_connection->setReadPreference(\MongoClient::RP_SECONDARY, $tags);
        return $this;
    }
    
    public function readSecondaryPreferred(array $tags = null)
    {
        $this->_connection->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readNearest(array $tags = null)
    {
        $this->_connection->setReadPreference(\MongoClient::RP_NEAREST, $tags);
        return $this;
    }
}