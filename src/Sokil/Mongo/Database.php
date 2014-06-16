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
    
    public function __get($name)
    {
        return $this->getCollection($name);
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
     * Create collection
     * 
     * @param array $options array of options
     * @return \Sokil\Mongo\Collection
     */
    public function createCollection($name, array $options = null)
    {
        $className = $this->getCollectionClassName($name);
        if(!class_exists($className)) {
            throw new Exception('Class ' . $className . ' not found while map collection name to class');
        }
            
        $mongoCollection = $this->getMongoDB()->createCollection($name, $options);
        return new $className($this, $mongoCollection);
    }
    
    /**
     * 
     * @param string $name name of collection
     * @param int $maxElements The maximum number of elements to store in the collection.
     * @param int $size Size in bytes.
     * @return \Sokil\Mongo\Collection
     * @throws Exception
     */
    public function createCappedCollection($name, $maxElements, $size)
    {
        $options = array(
            'capped'    => true,
            'size'      => (int) $size,
            'max'       => (int) $maxElements,
        );
        
        if(!$options['size'] && !$options['max']) {
            throw new Exception('Size or number of elements must be defined');
        }
        
        return $this->createCollection($name, $options);
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
    
    /**
     * 
     * @param string $channel name of channel
     * @return \Sokil\Mongo\Queue
     */
    public function getQueue($channel)
    {
        return new Queue($this, $channel);
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
    
    /**
     * @param string|integer $w write concern
     * @param int $timeout timeout in miliseconds
     */
    public function setWriteConcern($w, $timeout = 10000)
    {
        if(!$this->_mongoDB->setWriteConcern($w, (int) $timeout)) {
            throw new Exception('Error setting write concern');
        }
        
        return $this;
    }
    
    /**
     * @param int $timeout timeout in miliseconds
     */
    public function setUnacknowledgedWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern(0, (int) $timeout);
        return $this;
    }
    
    /**
     * @param int $timeout timeout in miliseconds
     */
    public function setMajorityWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern('majority', (int) $timeout);
        return $this;
    }
    
    public function getWriteConcern()
    {
        return $this->_mongoDB->getWriteConcern();
    }
    
    public function executeCommand(array $command, array $options = array())
    {
        return $this->getMongoDB()->command($command, $options);
    }
    
    public function executeJS($code, array $args = array())
    {
        $response = $this->getMongoDB()->execute($code, $args);
        if($response['ok'] == 1.0) {
            return $response['retval'];
        } else {
            throw new Exception('Error #' . $response['code'] . ': ' . $response['errmsg'], $response['code']);
        }
    }
    
    public function stats()
    {
        return $this->executeCommand(array(
            'dbstats' => 1,
        ));
    }
    
    public function disableProfiler()
    {
        return $this->executeCommand(array(
            'profile'   => 0,
        ));
    }
    
    public function profileSlowQueries($slowms = 100)
    {
        return $this->executeCommand(array(
            'profile'   => 1,
            'slowms'    => (int) $slowms
        ));
    }
    
    public function profileAllQueries($slowms = 100)
    {
        return $this->executeCommand(array(
            'profile'   => 2,
            'slowms'    => (int) $slowms
        ));
    }
}
