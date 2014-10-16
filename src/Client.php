<?php

namespace Sokil\Mongo;

use \Psr\Log\LoggerInterface;

/**
 * Connection manager and factory to get database and collection instances.
 * 
 * @link https://github.com/sokil/php-mongo#connecting Connecting
 * @link https://github.com/sokil/php-mongo#selecting-database-and-collection Get database and collection instance
 */
class Client
{
    const DEFAULT_DSN = 'mongodb://127.0.0.1';
    
    private $_dsn = self::DEFAULT_DSN;
    
    private $_connectOptions = array();
    
    /**
     *
     * @var \MongoClient
     */
    private $_mongoClient;
    
    private $_databasePool = array();
    
    /**
     * @var array Database to class mapping
     */
    protected $_mapping = array();
    
        
    private $_logger;
    
    private $_currentDatabaseName;
    
    /**
     * 
     * @param string $dsn Data Source Name
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
    
    public function __get($name)
    {
        return $this->getDatabase($name);
    }
    
    /**
     * 
     * @return string Version of PHP driver
     */
    public function getVersion()
    {
        return \MongoClient::VERSION;
    }
    
    public function getDbVersion()
    {
        return $this->getDatabase('test')->executeJS('version();');
    }
    
    public function setDsn($dsn)
    {
        $this->_dsn = $dsn;
        return $this;
    }
    
    public function getDsn()
    {
        return $this->_dsn;
    }
    
    public function setConnectOptions(array $options)
    {
        $this->_connectOptions = $options;
        return $this;
    }

    public function getConnectOptions()
    {
        return $this->_connectOptions;
    }
    
    /**
     * Set mongo's client
     * 
     * @deprecated since v.1.8.0 use Client::setMongoClient()
     * @param \MongoClient $client
     * @return \Sokil\Mongo\Client
     */
    public function setConnection(\MongoClient $client)
    {
        $this->setMongoClient($client);
        
        return $this;
    }
    
    /**
     * Set mongo's client
     * 
     * @param \MongoClient $client
     * @return \Sokil\Mongo\Client
     */
    public function setMongoClient(\MongoClient $client)
    {
        $this->_mongoClient = $client;
        return $this;
    }

    /**
     * Get mongo connection instance
     *
     * @deprecated since v.1.8.0 use Client::getMongoClient()
     * @return \MongoClient
     * @throws \Sokil\Mongo\Exception
     */
    public function getConnection()
    {
        return $this->getMongoClient();
    }
    
    /**
     * Get mongo connection instance
     *
     * @return \MongoClient
     * @throws \Sokil\Mongo\Exception
     */
    public function getMongoClient()
    {
        if($this->_mongoClient) {
            return $this->_mongoClient;
        }
            
        if(!$this->_dsn) {
            throw new Exception('DSN not specified');
        }

        $this->_mongoClient = new \MongoClient($this->_dsn, $this->_connectOptions);
        
        return $this->_mongoClient;
    }
    
    /**
     * Get list of all active connections through this client
     * 
     * @return type
     */
    public function getConnections()
    {
        return $this->_mongoClient->getConnections();
    }
    
    /**
     * Map database and collection name to class
     * 
     * @param array $mapping classpath or class prefix
     * Classpath:
     *  [dbname => [collectionName => collectionClass, ...], ...]
     * Class prefix:
     *  [dbname => classPrefix]
     * 
     * @return \Sokil\Mongo\Client
     */
    public function map(array $mapping) {
        $this->_mapping = $mapping;
        
        return $this;
    }
    
    /**
     * 
     * @param string $name database name
     * @return \Sokil\Mongo\Database
     */
    public function getDatabase($name = null) {
        
        if(!$name) {
            $name = $this->getCurrentDatabaseName();
        }

        if(!isset($this->_databasePool[$name])) {
            // init db
            $database = new Database($this, $name);
            if(isset($this->_mapping[$name])) {
                $database->map($this->_mapping[$name]);
            }

            // configure db
            $this->_databasePool[$name] = $database;
        }
        
        return $this->_databasePool[$name];
    }
    
    /**
     * Select database
     * 
     * @param string $name
     * @return \Sokil\Mongo\Client
     */
    public function useDatabase($name)
    {
        $this->_currentDatabaseName = $name;
        return $this;
    }
    
    public function getCurrentDatabaseName()
    {
        if(!$this->_currentDatabaseName) {
            throw new Exception('Database not selected');
        }

        return $this->_currentDatabaseName;
    }
    
    /**
     * Get collection from previously selected database by self::useDatabase()
     * 
     * @param string $name
     * @return \Sokil\Mongo\Collection
     * @throws Exception
     */
    public function getCollection($name)
    {        
        return $this
            ->getDatabase($this->getCurrentDatabaseName())
            ->getCollection($name);
    }
    
    public function readPrimaryOnly()
    {
        $this->getMongoClient()->setReadPreference(\MongoClient::RP_PRIMARY);
        return $this;
    }
    
    public function readPrimaryPreferred(array $tags = null)
    {
        $this->getMongoClient()->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readSecondaryOnly(array $tags = null)
    {
        $this->getMongoClient()->setReadPreference(\MongoClient::RP_SECONDARY, $tags);
        return $this;
    }
    
    public function readSecondaryPreferred(array $tags = null)
    {
        $this->getMongoClient()->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readNearest(array $tags = null)
    {
        $this->getMongoClient()->setReadPreference(\MongoClient::RP_NEAREST, $tags);
        return $this;
    }

    public function getReadPreference()
    {
        return $this->getMongoClient()->getReadPreference();
    }
    
    public function setLogger(LoggerInterface $logger)
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

    /**
     * Check if logger defined
     *
     * @return bool
     */
    public function hasLogger()
    {
        return (bool) $this->_logger;
    }

    /**
     * Remove logger
     *
     * @return \Sokil\Mongo\Client
     */
    public function removeLogger()
    {
        $this->_logger = null;
        return $this;
    }
    
    /**
     * Define write concern on whole requests
     *
     * @param string|integer $w write concern
     * @param int $timeout timeout in milliseconds
     * @return \Sokil\Mongo\Client
     *
     * @throws \Sokil\Mongo\Exception
     */
    public function setWriteConcern($w, $timeout = 10000)
    {
        if(!$this->getMongoClient()->setWriteConcern($w, (int) $timeout)) {
            throw new Exception('Error setting write concern');
        }
        
        return $this;
    }
    
    /**
     * Define unacknowledged write concern on whole requests
     *
     * @param int $timeout timeout in milliseconds
     * @return \Sokil\Mongo\Client
     */
    public function setUnacknowledgedWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern(0, (int) $timeout);
        return $this;
    }
    
    /**
     * Define majority write concern on whole requests
     *
     * @param int $timeout timeout in milliseconds
     * @return \Sokil\Mongo\Client
     */
    public function setMajorityWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern('majority', (int) $timeout);
        return $this;
    }

    /**
     * Get currently active write concern on connection level
     *
     * @return string|int
     */
    public function getWriteConcern()
    {
        return $this->getMongoClient()->getWriteConcern();
    }

    /**
     * Create new persistence manager
     * @return \Sokil\Mongo\Persistence
     */
    public function createPersistence()
    {
        return new Persistence($this);
    }
}
