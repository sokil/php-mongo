<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

use Sokil\Mongo\Collection\Definition;

class Database
{
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private $client;

    /**
     * @var \MongoDB
     */
    private $database;

    /**
     * @var string
     */
    private $databaseName;

    /**
     * @var array map collection name to class
     */
    private $mapping = array();

    /**
     * @var array map regexp pattern of collection name to class
     */
    private $regexpMapping = array();

    /**
     * @var array pool of initialised collections
     */
    private $collectionPool = array();

    /**
     *
     * @var bool is collection pool enabled
     */
    private $collectionPoolEnabled = true;

    /**
     * @param Client $client
     * @param \MongoDB|string $database
     */
    public function __construct(Client $client, $database)
    {
        $this->client = $client;

        if ($database instanceof \MongoDB) {
            $this->database = $database;
            $this->databaseName = $database->__toString();
        } else {
            $this->databaseName = $database;
        }
    }

    /**
     *
     * @param string $username
     * @param string $password
     */
    public function authenticate($username, $password)
    {
        $this->getMongoDB()->authenticate($username, $password);
    }

    public function logout()
    {
        $this->executeCommand(array(
            'logout' => 1,
        ));
    }

    public function __get($name)
    {
        return $this->getCollection($name);
    }

    /**
     * @return string get name of database
     */
    public function getName()
    {
        return $this->databaseName;
    }

    /**
     *
     * @return \MongoDB
     */
    public function getMongoDB()
    {
        if (empty($this->database)) {
            $this->database = $this
                ->client
                ->getMongoClient()
                ->selectDB($this->databaseName);
        }

        return $this->database;
    }

    /**
     *
     * @return \Sokil\Mongo\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    public function disableCollectionPool()
    {
        $this->collectionPoolEnabled = false;
        return $this;
    }

    public function enableCollectionPool()
    {
        $this->collectionPoolEnabled = true;
        return $this;
    }

    public function isCollectionPoolEnabled()
    {
        return $this->collectionPoolEnabled;
    }

    public function clearCollectionPool()
    {
        $this->collectionPool = array();
        return $this;
    }

    public function isCollectionPoolEmpty()
    {
        return !$this->collectionPool;
    }

    /**
     * Reset specified mapping
     *
     * @return \Sokil\Mongo\Client
     */
    public function resetMapping()
    {
        $this->mapping = array();

        return $this;
    }

    /**
     * Map collection name to class
     *
     * @param string|array $name collection name or array like [collectionName => collectionClass, ...]
     * @param string|array|Definition|null $classDefinition if $name is string, then full class name or array with parameters, else omitted
     *
     * @return Database
     *
     * @throws Exception
     */
    public function map($name, $classDefinition = null)
    {
        // map collection to class
        if ($classDefinition) {
            return $this->defineCollection($name, $classDefinition);
        }

        // map collections to classes
        if (is_array($name)) {
            foreach ($name as $collectionName => $classDefinition) {
                $this->defineCollection($collectionName, $classDefinition);
            }
            return $this;
        }

        // define class prefix
        // deprecated: use class definition
        $this->defineCollection('*', array(
            'class' => $name,
        ));

        return $this;
    }

    /**
     * Define collection through array or Definition instance
     *
     * @param string $name collection name
     * @param Definition|array|string $definition collection definition
     *
     * @return Database
     *
     * @throws Exception
     */
    private function defineCollection($name, $definition)
    {
        // prepare definition object
        if (false === ($definition instanceof Definition)) {
            if (is_string($definition)) {
                $definition = new Definition(array('class' => $definition));
            } elseif (is_array($definition)) {
                $definition = new Definition($definition);
            } else {
                throw new Exception(sprintf('Wrong definition passed for collection %s', $name));
            }
        }

        // set definition
        if ('/' !== substr($name, 0, 1)) {
            $this->mapping[$name] = $definition;
        } else {
            $this->regexpMapping[$name] = $definition;
        }

        return $this;
    }

    /**
     * Get class name mapped to collection
     *
     * @param string        $name               name of collection
     * @param array         $defaultDefinition  definition used when no definition found for defined class
     * @throws Exception
     * @return string|array                     name of class or array of class definition
     */
    private function getCollectionDefinition($name, array $defaultDefinition = null)
    {
        if (isset($this->mapping[$name])) {
            $classDefinition = $this->mapping[$name];
        } elseif ($this->regexpMapping) {
            foreach ($this->regexpMapping as $collectionNamePattern => $regexpMappingClassDefinition) {
                if (preg_match($collectionNamePattern, $name, $matches)) {
                    $classDefinition = clone $regexpMappingClassDefinition;
                    $classDefinition->setOption('regexp', $matches);
                    break;
                }
            }
        }

        // mapping not configured - use default
        if (!isset($classDefinition)) {
            if (!empty($this->mapping['*'])) {
                $classDefinition = clone $this->mapping['*'];
                $collectionClass = $classDefinition->getClass()
                    . '\\'
                    . implode('\\', array_map('ucfirst', explode('.', $name)));
                $classDefinition->setClass($collectionClass);
            } else {
                $classDefinition = new Definition();
                if ($defaultDefinition) {
                    $classDefinition->merge($defaultDefinition);
                }
            }
        }

        // check if class exists
        if (!class_exists($classDefinition->getClass())) {
            throw new Exception(
                'Class ' . $classDefinition->getClass() . ' not found while map collection name to class'
            );
        }

        return $classDefinition;
    }

    /**
     * Create collection
     *
     * @param string $name name of collection
     * @param array|null $options array of options
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     */
    public function createCollection($name, array $options = null)
    {
        $classDefinition = $this->getCollectionDefinition($name);
        
        if (!empty($options)) {
            $classDefinition->merge($options);
        }

        $mongoCollection = $this->getMongoDB()->createCollection(
            $name,
            $classDefinition->getMongoCollectionOptions()
        );

        // create collection
        $className = $classDefinition->getClass();
        return new $className(
            $this,
            $mongoCollection,
            $classDefinition
        );
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

        if (!$options['size'] && !$options['max']) {
            throw new Exception('Size or number of elements must be defined');
        }

        return $this->createCollection($name, $options);
    }

    /**
     *
     * @param string $name name of collection
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     */
    public function getCollection($name)
    {
        // return from pool
        if ($this->collectionPoolEnabled && isset($this->collectionPool[$name])) {
            return $this->collectionPool[$name];
        }

        // no object in pool - init new
        $classDefinition = $this->getCollectionDefinition($name);
        $className = $classDefinition->getClass();

        // create collection class
        $collection = new $className($this, $name, $classDefinition);
        if (!$collection instanceof Collection) {
            throw new Exception('Must be instance of \Sokil\Mongo\Collection');
        }

        // store to pool
        if ($this->collectionPoolEnabled) {
            $this->collectionPool[$name] = $collection;
        }

        // return
        return $collection;
    }

    /**
     * Get Document instance by it's reference
     *
     * @param array $ref reference to document
     * @param bool  $useDocumentPool try to get document from pool or fetch document from database
     *
     * @return Document|null
     */
    public function getDocumentByReference(array $ref, $useDocumentPool = true)
    {
        $documentArray = $this->getMongoDB()->getDBRef($ref);
        if (null === $documentArray) {
            return null;
        }

        return $this->getCollection($ref['$ref'])->hydrate($documentArray, $useDocumentPool);
    }

    /**
     * Get instance of GridFS
     *
     * @param string $name prefix of files and chunks collection
     * @return \Sokil\Mongo\GridFS
     * @throws \Sokil\Mongo\Exception
     */
    public function getGridFS($name = 'fs')
    {
        // return from pool
        if ($this->collectionPoolEnabled && isset($this->collectionPool[$name])) {
            return $this->collectionPool[$name];
        }

        // no object in pool - init new
        $classDefinition = $this->getCollectionDefinition($name, array('gridfs' => true));
        $className = $classDefinition->getClass();

        // create collection class
        $collection = new $className($this, $name, $classDefinition);
        if (!$collection instanceof \Sokil\Mongo\GridFS) {
            throw new Exception('Must be instance of \Sokil\Mongo\GridFS');
        }

        // store to pool
        if ($this->collectionPoolEnabled) {
            $this->collectionPool[$name] = $collection;
        }

        // return
        return $collection;
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

    /**
     * Get cache
     *
     * @param string $namespace name of collection to be created in database
     *
     * @return Cache
     */
    public function getCache($namespace)
    {
        return new Cache($this, $namespace);
    }

    public function readPrimaryOnly()
    {
        $this->getMongoDB()->setReadPreference(\MongoClient::RP_PRIMARY);
        return $this;
    }

    public function readPrimaryPreferred(array $tags = null)
    {
        $this->getMongoDB()->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);
        return $this;
    }

    public function readSecondaryOnly(array $tags = null)
    {
        $this->getMongoDB()->setReadPreference(\MongoClient::RP_SECONDARY, $tags);
        return $this;
    }

    public function readSecondaryPreferred(array $tags = null)
    {
        $this->getMongoDB()->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);
        return $this;
    }

    public function readNearest(array $tags = null)
    {
        $this->getMongoDB()->setReadPreference(\MongoClient::RP_NEAREST, $tags);
        return $this;
    }

    public function getReadPreference()
    {
        return $this->getMongoDB()->getReadPreference();
    }

    /**
     * Define write concern.
     * May be used only if mongo extension version >=1.5
     *
     * @param string|integer $w write concern
     * @param int $timeout timeout in milliseconds
     * @return \Sokil\Mongo\Database
     * @throws \Sokil\Mongo\Exception
     */
    public function setWriteConcern($w, $timeout = 10000)
    {
        if (!$this->getMongoDB()->setWriteConcern($w, (int) $timeout)) {
            throw new Exception('Error setting write concern');
        }

        return $this;
    }

    /**
     * Define unacknowledged write concern.
     * May be used only if mongo extension version >=1.5
     *
     * @param int $timeout timeout in milliseconds
     * @return \Sokil\Mongo\Database
     */
    public function setUnacknowledgedWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern(0, (int) $timeout);
        return $this;
    }

    /**
     * Define majority write concern.
     * May be used only if mongo extension version >=1.5
     *
     * @param int $timeout timeout in milliseconds
     * @return \Sokil\Mongo\Database
     */
    public function setMajorityWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern('majority', (int) $timeout);
        return $this;
    }

    /**
     * Get current write concern
     * May be used only if mongo extension version >=1.5
     *
     * @return mixed
     */
    public function getWriteConcern()
    {
        return $this->getMongoDB()->getWriteConcern();
    }

    /**
     * Execute Mongo command
     *
     * @param array $command
     * @param array $options
     * @return array
     */
    public function executeCommand(array $command, array $options = array())
    {
        return $this->getMongoDB()->command($command, $options);
    }

    public function executeJS($code, array $args = array())
    {
        $response = $this->getMongoDB()->execute($code, $args);
        if ($response['ok'] == 1.0) {
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

    public function getLastError()
    {
        return $this->getMongoDB()->lastError();
    }

    public function getProfilerParams()
    {
        return $this->executeCommand(array(
            'profile'   => -1,
        ));
    }

    public function getProfilerLevel()
    {
        $params = $this->getProfilerParams();
        return $params['was'];
    }

    public function getProfilerSlowMs()
    {
        $params = $this->getProfilerParams();
        return $params['slowms'];
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

    public function profileAllQueries($slowms = null)
    {
        $command = array(
            'profile'   => 2,
        );

        if ($slowms) {
            $command['slowms'] = (int) $slowms;
        }

        return $this->executeCommand($command);
    }

    /**
     *
     * @return \Sokil\Mongo\Cursor
     */
    public function findProfilerRows()
    {
        return $this
            ->getCollection('system.profile')
            ->find()
            ->asArray();
    }
}
