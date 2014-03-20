<?php

namespace Sokil\Mongo;

class Database
{
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private $_client;
    
    private $_databaseName;
    
    /**
     *
     * @var \MongoDB
     */
    private $_mongoDB;
    
    private $_mapping = array();
    
    private $_classPrefix;
    
    private $_collectionPool = array();
    
    public function __construct(Client $client, $databaseName) {
        $this->_client = $client;
        $this->_databaseName = $databaseName;
        
        $this->_mongoDB = $this->_client->getConnection()->selectDB($databaseName);
    }
    
    /**
     * 
     * @return \MongoDB
     */
    public function getMongoDB()
    {
        return $this->_mongoDB;
    }
    
    /**
     * 
     * @return \Sokil\Mongo\Client
     */
    public function getClient()
    {
        return $this->_client;
    }
    
    /**
     * Map collection name to class
     * 
     * @param string|array $name collection name or array like [collectionName => collectionClass, ...]
     * @param string|null $class if $name is string, then full class name, else ommited
     * @return \Sokil\Mongo\Client
     */
    public function map($name, $class = null) {
        
        // map collections to classes
        if(is_array($name)) {
            $this->_mapping = array_merge($this->_mapping, $name);
        }
        // map collection to class
        elseif($class) {
            $this->_mapping[$name] = $class;
        }
        // define class prefix
        else {
            $this->_classPrefix = rtrim($name, '\\');
        }
        
        return $this;
    }
    
    /**
     * Get class name mapped to collection
     * @param string $name name of collection
     * @return string name of class
     */
    public function getCollectionClassName($name)
    {        
        if(isset($this->_mapping[$name])) {
            $className = $this->_mapping[$name];
        } elseif($this->_classPrefix) {
            $className = $this->_classPrefix . '\\' . implode('\\', array_map('ucfirst', explode('.', strtolower($name))));
        } else {
            $className = '\Sokil\Mongo\Collection';
        }
        
        return $className;
    }
    
    /**
     * 
     * @param string $name name of collection
     * @return \Sokil\Mongo\Collection
     */
    public function getCollection($name) {
        if(!isset($this->_collectionPool[$name])) {
            $className = $this->getCollectionClassName($name);
            if(!class_exists($className)) {
                throw new Exception('Class ' . $className . ' not found while map collection name to class');
            }
            
            $this->_collectionPool[$name] = new $className($this, $name);
        }
        
        return $this->_collectionPool[$name];
    }
    
    public function readPrimaryOnly()
    {
        $this->_mongoDB->setReadPreference(\MongoClient::RP_PRIMARY);
        return $this;
    }
    
    public function readPrimaryPreferred(array $tags = null)
    {
        $this->_mongoDB->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readSecondaryOnly(array $tags = null)
    {
        $this->_mongoDB->setReadPreference(\MongoClient::RP_SECONDARY, $tags);
        return $this;
    }
    
    public function readSecondaryPreferred(array $tags = null)
    {
        $this->_mongoDB->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readNearest(array $tags = null)
    {
        $this->_mongoDB->setReadPreference(\MongoClient::RP_NEAREST, $tags);
        return $this;
    }
    
    public function setWriteConcern($w, $timeout)
    {
        if(!$this->_mongoDB->setWriteConcern($w, (int) $timeout)) {
            throw new Exception('Error setting write concern');
        }
        
        return $this;
    }
    
    public function setUnacknowledgedWriteConcern($timeout)
    {
        $this->setWriteConcern(0, (int) $timeout);
        return $this;
    }
    
    public function setMajorityWriteConcern($timeout)
    {
        $this->setWriteConcern('majority', (int) $timeout);
        return $this;
    }
    
    public function getWriteConcern()
    {
        return $this->_mongoDB->getWriteConcern();
    }
}