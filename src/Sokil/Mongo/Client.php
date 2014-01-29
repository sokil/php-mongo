<?php

namespace Sokil\Mongo;

class Client
{
    private $_dsn;
    
    private $_connectOptions = array('connect' => true);
    
    /**
     *
     * @var \MongoClient
     */
    private $_connection;
    
    private $_databasePool = array();
    
    /**
     * @var array Database to class mapping
     */
    protected $_mapping = array();
    
        
    private $_logger;
    
    /**
     * 
     * @param type $dsn
     * @param array $options
     */
    public function __construct($dsn = null, array $options = null) {
        if($dsn) {
            $this->setDsn($dsn);
        }
        
        if($options) {
            $this->setConnectOptions($options);
        }
    }
    
    public function setDsn($dsn)
    {
        $this->_dsn = $dsn;
        return $this;
    }
    
    public function setConnectOptions(array $options)
    {
        $this->_connectOptions = $options;
        return $this;
    }
    
    public function setConnection(\MongoClient $client)
    {
        $this->_connection = $client;
        return $this;
    }
    
    public function getConnection()
    {
        if(!$this->_connection) {
            
            if(!$this->_dsn) {
                throw new Exception('DSN not specified');
            }
            
            $this->_connection = new \MongoClient($this->_dsn, $this->_connectOptions);
        }
        
        return $this->_connection;
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
            
            $this->_databasePool[$name] = new $className($this, $name);
        }
        
        return $this->_databasePool[$name];
    }
    
    public function readPrimaryOnly()
    {
        $this->getConnection()->setReadPreference(\MongoClient::RP_PRIMARY);
        return $this;
    }
    
    public function readPrimaryPreferred(array $tags = null)
    {
        $this->getConnection()->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readSecondaryOnly(array $tags = null)
    {
        $this->getConnection()->setReadPreference(\MongoClient::RP_SECONDARY, $tags);
        return $this;
    }
    
    public function readSecondaryPreferred(array $tags = null)
    {
        $this->getConnection()->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readNearest(array $tags = null)
    {
        $this->getConnection()->setReadPreference(\MongoClient::RP_NEAREST, $tags);
        return $this;
    }
    
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
        return $this;
    }
    
    /**
     * 
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }
    
    public function hasLogger()
    {
        return (bool) $this->_logger;
    }
}