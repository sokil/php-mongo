<?php
declare(strict_types=1);

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    
    /**
     *
     * @var \MongoDb\Client
     */
    private $nativeMongoClient;

    /**
     * @var array
     */
    private $databasePool = array();
    
    /**
     * @var array Database to class mapping
     */
    private $mapping = array();

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var string
     */
    private $currentDatabaseName;

    /**
     *
     * @var string version of MongoDb
     */
    private $dbVersion;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @see https://php.net/manual/en/mongodb-driver-manager.construct.php
     *
     * @param string $dsn Data Source Name
     * @param array $uriOptions
     * @param array $driverOptions
     */
    public function __construct(
        string $dsn = self::DEFAULT_DSN,
        array $uriOptions = [],
        array $driverOptions = []
    ) {
        $this->nativeMongoClient = new \MongoDB\Client(
            $dsn,
            $uriOptions,
            $driverOptions
        );
    }

    /**
     * Get database instance by name
     *
     * @param string $name
     *
     * @return Database
     *
     * @throws Exception
     */
    public function __get($name)
    {
        return $this->getDatabase($name);
    }
    
    /**
     *
     * @return string Version of PHP driver
     */
    public function getDriverVersion() : string
    {
        throw new \Exception('Not implemented');
    }

    /**
     *
     * @return string version of mongo database
     */
    public function getDbVersion() : string
    {
        if ($this->dbVersion) {
            return $this->dbVersion;
        }

        $buildInfo = $this
            ->getDatabase('admin')
            ->executeCommand(array('buildinfo' => 1));
        
        $this->dbVersion = $buildInfo['version'];

        return $this->dbVersion;
    }
    
    /**
     * Map database and collection name to class.
     *
     * Collection name -> array definition:
     *  ['acmeDatabaseName' => ['acmeCollectionName' => ['class' => '\Acme\Collection\SomeCollectionClass']]]
     * Collection name -> collection class name (deprecated: use definition array):
     *  ['acmeDatabaseName' => ['acmeCollectionName' => '\Acme\Collection\SomeCollectionClass']]
     * Collection's class namespace (deprecated: use definition array):
     *  ['acmeDatabaseName' => '\Acme\Collection']
     *
     * @param array $mapping classpath or class prefix
     * @return Client
     */
    public function map(array $mapping) : Client
    {
        $this->mapping = $mapping;
        
        return $this;
    }

    /**
     * Get database instance
     *
     * @param string $name database name
     *
     * @return Database
     *
     * @throws Exception
     */
    public function getDatabase(string $name = null) : Database
    {
        if (empty($name)) {
            $name = $this->getCurrentDatabaseName();
        }

        if (!isset($this->databasePool[$name])) {
            // init db
            $database = new Database($this, $name);
            if (isset($this->mapping[$name])) {
                $database->map($this->mapping[$name]);
            }

            // configure db
            $this->databasePool[$name] = $database;
        }
        
        return $this->databasePool[$name];
    }
    
    /**
     * Select database
     *
     * @param string $name
     *
     * @return Client
     */
    public function useDatabase(string $name) : Client
    {
        $this->currentDatabaseName = $name;

        return $this;
    }

    /**
     * Get name of current database
     *
     * @return string
     *
     * @throws Exception
     */
    public function getCurrentDatabaseName() : string
    {
        if (!$this->currentDatabaseName) {
            throw new Exception('Database not selected');
        }

        return $this->currentDatabaseName;
    }
    
    /**
     * Get collection from previously selected database by self::useDatabase()
     *
     * @param string $name
     *
     * @return Collection
     *
     * @throws Exception
     */
    public function getCollection($name) : Collection
    {
        return $this
            ->getDatabase($this->getCurrentDatabaseName())
            ->getCollection($name);
    }

    /**
     * @return Client
     */
    public function readPrimaryOnly() : Client
    {
        $this->nativeMongoClient->setReadPreference(\MongoClient::RP_PRIMARY);

        return $this;
    }

    /**
     * @param array|null $tags
     *
     * @return Client
     */
    public function readPrimaryPreferred(array $tags = null) : Client
    {
        $this->nativeMongoClient->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);

        return $this;
    }

    /**
     * @param array|null $tags
     *
     * @return Client
     */
    public function readSecondaryOnly(array $tags = null) : Client
    {
        $this->nativeMongoClient->setReadPreference(\MongoClient::RP_SECONDARY, $tags);

        return $this;
    }

    /**
     * @param array|null $tags
     *
     * @return Client
     */
    public function readSecondaryPreferred(array $tags = null) : Client
    {
        $this->nativeMongoClient->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);

        return $this;
    }

    /**
     * @param array|null $tags
     *
     * @return Client
     */
    public function readNearest(array $tags = null) : Client
    {
        $this->nativeMongoClient->setReadPreference(\MongoClient::RP_NEAREST, $tags);

        return $this;
    }

    /**
     * @return array
     */
    public function getReadPreference() : array
    {
        return $this->nativeMongoClient->getReadPreference();
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return Client
     */
    public function setLogger(LoggerInterface $logger) : Client
    {
        $this->logger = $logger;

        return $this;
    }
    
    /**
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Check if logger defined
     *
     * @return bool
     */
    public function hasLogger()
    {
        return (bool) $this->logger;
    }

    /**
     * Remove logger
     *
     * @return Client
     */
    public function removeLogger()
    {
        $this->logger = null;

        return $this;
    }

    /**
     * Enable or disable debug mode
     */
    public function debug($enabled = true)
    {
        $this->debug = (bool) $enabled;

        return $this;
    }

    /**
     * Check state of debug mode
     */
    public function isDebugEnabled() : bool
    {
        return $this->debug;
    }

    /**
     * Define write concern on whole requests
     *
     * @param string|integer $w write concern
     * @param int $timeout timeout in milliseconds
     *
     * @return Client
     *
     * @throws Exception
     */
    public function setWriteConcern($w, int $timeout = 10000)
    {
        if (!$this->nativeMongoClient->setWriteConcern($w, $timeout)) {
            throw new Exception('Error setting write concern');
        }
        
        return $this;
    }
    
    /**
     * Define unacknowledged write concern on whole requests
     *
     * @param int $timeout timeout in milliseconds
     * @return Client
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
     *
     * @return Client
     */
    public function setMajorityWriteConcern($timeout = 10000) : Client
    {
        $this->setWriteConcern('majority', (int) $timeout);

        return $this;
    }

    /**
     * Get currently active write concern on connection level
     *
     * @return array
     */
    public function getWriteConcern() : array
    {
        return $this->nativeMongoClient->getWriteConcern();
    }

    /**
     * Create new persistence manager
     *
     * @return Persistence
     */
    public function createPersistence()
    {
        // operations of same type and in same collection executed at once
        if (version_compare($this->getVersion(), '1.5', '>=') && version_compare($this->getDbVersion(), '2.6', '>=')) {
            return new Persistence();
        }

        // all operations executed separately
        return new PersistenceLegacy();
    }
}
