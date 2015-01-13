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

use \Sokil\Mongo\Document\InvalidDocumentException;
/**
 * Instance of this class is a representation of mongo collection.
 * It aggregates \MongoCollection instance.
 *
 * @link https://github.com/sokil/php-mongo#selecting-database-and-collection Selecting collection
 * @link https://github.com/sokil/php-mongo#querying-documents Querying documents
 * @link https://github.com/sokil/php-mongo#update-few-documents Update few documents
 * @link https://github.com/sokil/php-mongo#deleting-collections-and-documents Deleting collection
 *
 * @author Dmytro Sokil <dmytro.sokil@gmail.com>
 */
class Collection implements \Countable
{
    /**
     * @deprecated Overloading of query builder is deprecated.
     * In future releases this property will be removed.
     * Overload _queryExpressionClass instead
     *
     * @var string query builder class
     */
    protected $_queryBuilderClass = '\Sokil\Mongo\QueryBuilder';

    /**
     * @var string expression class. This class may be overloaded to define
     *  own query methods (whereUserAgeGreatedThan(), etc.)
     */
    protected $_queryExpressionClass = '\Sokil\Mongo\Expression';

    /**
     *
     * @var string Default class for document
     */
    private $_documentClass = '\Sokil\Mongo\Document';

    /**
     * List of arrays, where each item array is an index definition.
     * Every index definition must contain key 'keys' with list of fields and orders,
     * and optional options from @link http://php.net/manual/en/mongocollection.createindex.php:
     *
     * Example:
     * array(
     *     array(
     *         'keys' => array('field1' => 1, 'field2' => -1),
     *         'unique' => true
     *     ),
     *     ...
     * )
     * @var array list of indexes
     */
    protected $_index;

    /**
     *
     * @var \Sokil\Mongo\Database
     */
    protected $_database;

    /**
     *
     * @var \MongoCollection
     */
    protected $_mongoCollection;

    /**
     * Implementation of identity map pattern
     *
     * @var array list of cached documents
     */
    private $documentPool = array();

    /**
     *
     * @var bool cache or not documents
     */
    private $isDocumentPoolEnabled = true;

    /**
     *
     * @var bool default value of versioning
     */
    protected $versioning = false;

    /**
     *
     * @var array collection options
     *
     * Allowed options:
     * 
     * documentClass: May be fully qualified class name or callable that return fully qualified class name of document
     * expressionClass: Fully qualified class name of expression
     * versioning: is versioning enabled for documents
     * index: list of collection indexes
     * behaviors: list of document behaviors
     */
    private $_options = array();

    public function __construct(Database $database, $collection, array $options = null)
    {
        // define db
        $this->_database = $database;

        // init mongo collection
        if($collection instanceof \MongoCollection) {
            $this->_mongoCollection = $collection;
        } else {
            $this->_mongoCollection = $database->getMongoDB()->selectCollection($collection);
        }

        // set options
        if($options) {
            $this->setOptions($options);
        }

    }

    /**
     * Configure object in constructor
     *
     * @param array $options
     * @return \Sokil\Mongo\Collection
     * @throws Exception
     */
    private function setOptions(array $options)
    {
        $this->_options = $options + $this->_options;

        return $this;
    }

    /**
     * Start versioning documents on modify
     * 
     * @return \Sokil\Mongo\Collection
     */
    public function enableVersioning()
    {
        $this->_options['versioning'] = true;
        return $this;
    }

    /**
     * Check if versioning enabled
     * 
     * @return bool
     */
    public function isVersioningEnabled()
    {
        return $this->getOption('versioning', $this->versioning);
    }

    /**
     * Get fully qualified document class name or callable that return fully qualified class name
     * @return string
     */
    public function getDefaultDocumentClass()
    {
        $class = $this->getOption('documentClass', $this->_documentClass);

        // May be fully qualified class name or callable that return fully qualified class name
        if(!is_callable($class) && !class_exists($class)) {
            throw new Exception('Property "documentClass" must be callable or valid name of class');
        }

        return $class;
    }

    /**
     * Get all options
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Get option
     *
     * @param string|int $name
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return isset($this->_options[$name]) ? $this->_options[$name] : $default;
    }

    public function __get($name)
    {
        return $this->getDocument($name);
    }

    /**
     * Get name of collection
     * 
     * @return string name of collection
     */
    public function getName()
    {
        return $this->_mongoCollection->getName();
    }

    /**
     * Get native collection instance of mongo driver
     * 
     * @return \MongoCollection
     */
    public function getMongoCollection()
    {
        return $this->_mongoCollection;
    }

    /**
     *
     * @return \Sokil\Mongo\Database
     */
    public function getDatabase()
    {
        return $this->_database;
    }

    /**
     * Delete collection
     * 
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     */
    public function delete() {
        $status = $this->_mongoCollection->drop();
        if($status['ok'] != 1) {
            // check if collection exists
            if('ns not found' !== $status['errmsg']) {
                // collection exist
                throw new Exception('Error deleting collection ' . $this->getName() . ': ' . $status['errmsg']);
            }
        }

        return $this;
    }

    /**
     * Override to define class name of document by document data
     *
     * @param array $documentData
     * @return string Document class data
     */
    public function getDocumentClassName(array $documentData = null)
    {
        $defaultDocumentClass = $this->getDefaultDocumentClass();

        if(is_callable($defaultDocumentClass)) {
            $className = call_user_func($defaultDocumentClass, $documentData);
        } else {
            $className = $defaultDocumentClass;
        }

        if(!class_exists($className)) {
            throw new Exception('Class ' . $className . ' not found');
        }

        return $className;
    }

    /**
     * Factory method to get not stored Document instance from array
     * @param array $data
     * @return \Sokil\Mongo\Document
     */
    public function createDocument(array $data = null)
    {
        $className = $this->getDocumentClassName($data);

        /* @var $document \Sokil\Mongo\Document */
        $document = new $className($this, $data, array(
            'stored'        => false,
            'versioning'    => $this->isVersioningEnabled(),
            'behaviors'     => $this->getOption('behaviors'),
        ));

        // store document to identity map
        if($this->isDocumentPoolEnabled()) {
            $collection = $this;
            $document->onAfterInsert(function(\Sokil\Mongo\Event $event) use($collection) {
                $collection->addDocumentToDocumentPool($event->getTarget());
            });
        }

        return $document;
    }

    /**
     * Factory method to get document object from array of stored document
     *
     * @param array $data
     * @return \Sokil\Mongo\Document
     */
    public function getStoredDocumentInstanceFromArray(array $data, $useDocumentPool = true)
    {
        if(!isset($data['_id'])) {
            throw new Exception('Document must be stored and has _id key');
        }

        // if document already in pool - return it
        if($useDocumentPool && $this->isDocumentPoolEnabled() && $this->isDocumentInDocumentPool($data['_id'])) {
            return $this
                ->getDocumentFromDocumentPool($data['_id'])
                ->mergeUnmodified($data);
        }

        // init document instance
        $className = $this->getDocumentClassName($data);
        $document = new $className($this, $data, array(
            'stored'        => true,
            'versioning'    => $this->isVersioningEnabled(),
            'behaviors'     => $this->getOption('behaviors'),
        ));

        // store document in cache
        if($useDocumentPool && $this->isDocumentPoolEnabled()) {
            $this->addDocumentToDocumentPool($document);
        }

        return $document;
    }

    /**
     * Total count of documents in collection
     * 
     * @return int 
     */
    public function count()
    {
        return $this->find()->count();
    }

    /**
     * Retrieve a list of distinct values for the given key across a collection.
     *
     * @param string $selector field selector
     * @param \Sokil\Mongo\Expression $expression expression to search documents
     * @return array distinct values
     */
    public function getDistinct($selector, Expression $expression = null)
    {
        if($expression) {
            return $this->_mongoCollection->distinct($selector, $expression->toArray());
        } else {
            return $this->_mongoCollection->distinct($selector);
        }


    }

    /**
     * Get fully qualified class name of expression for current collection instance
     * 
     * @return string
     */
    private function getExpressionClass()
    {
        return $this->getOption('expressionClass', $this->_queryExpressionClass);
    }

    /**
     * Create new Expression instance to use in query builder or update operations
     * 
     * @return \Sokil\Mongo\Expression
     */
    public function expression()
    {
        $className = $this->getExpressionClass();
        return new $className;
    }

    /**
     * Create Operator instance to use in update operations
     * 
     * @return \Sokil\Mongo\Operator
     */
    public function operator()
    {
        return new Operator();
    }

    /**
     * Create document query builder
     *
     * @param $callable callable|null Function to configure query builder&
     * @return \Sokil\Mongo\QueryBuilder|\Sokil\Mongo\Expression
     */
    public function find($callable = null)
    {
        /** @var \Sokil\Mongo\Cursor $queryBuilder */
        $queryBuilder = new $this->_queryBuilderClass($this, array(
            'expressionClass'   => $this->getExpressionClass(),
        ));

        if(is_callable($callable)) {
            $callable($queryBuilder->getExpression());
        }

        return $queryBuilder;
    }

    /**
     * Create document query builder
     *
     * @return \Sokil\Mongo\QueryBuilder
     */
    public function findAsArray($callable = null)
    {
        return $this
            ->find($callable)
            ->asArray();
    }

    /**
     * Stop storing found documents to pool
     *
     * @return \Sokil\Mongo\Collection
     */
    public function disableDocumentPool()
    {
        $this->isDocumentPoolEnabled = false;
        return $this;
    }

    /**
     * Start storing found documents to pool
     *
     * @return \Sokil\Mongo\Collection
     */
    public function enableDocumentPool()
    {
        $this->isDocumentPoolEnabled = true;
        return $this;
    }

    /**
     * Check if document pool enabled and requested documents store to it
     *
     * @return bool
     */
    public function isDocumentPoolEnabled()
    {
        return $this->isDocumentPoolEnabled;
    }

    public function clearDocumentPool()
    {
        $this->documentPool = array();
        return $this;
    }

    /**
     * Check if documents are in pool
     *
     * @return bool
     */
    public function isDocumentPoolEmpty()
    {
        return !$this->documentPool;
    }

    /**
     * Store document to pool
     *
     * @param array $document
     * @return \Sokil\Mongo\Collection
     */
    public function addDocumentToDocumentPool(Document $document)
    {
        $documentId = (string) $document->getId();

        if(!isset($this->documentPool[$documentId])) {
            $this->documentPool[$documentId] = $document;
        } else {
            // merging because document after
            // load and before getting in second place may be changed
            // and this changes must be preserved:
            //
            // 1. Here document loads and modifies
            // $document = $collection->getDocument()->set('field', 'value');
            //
            // 2. Here document modified in another session
            //
            // 3. Here document loads once again.
            //    Changes from stage 2 merges as unmodified
            // $collection->find();

            $this->documentPool[$documentId]->mergeUnmodified($document->toArray());
        }

        return $this;
    }

    /**
     * Store documents to identity map
     *
     * @param array $documents list of Document instances
     * @return \Sokil\Mongo\Collection
     */
    public function addDocumentsToDocumentPool(array $documents)
    {
        foreach($documents as $document) {
            $this->addDocumentToDocumentPool($document);
        }

        return $this;
    }

    /**
     * Remove document instance from identity map
     *
     * @param \Sokil\Mongo\Document $document
     * @return \Sokil\Mongo\Collection
     */
    public function removeDocumentFromDocumentPool(Document $document)
    {
        unset($this->documentPool[(string) $document]);
        return $this;
    }

    /**
     * Get document from identity map by it's id
     *
     * @param string|int|\MongoId $id
     * @return \Sokil\Mongo\Document
     */
    public function getDocumentFromDocumentPool($id)
    {
        return $this->documentPool[(string) $id];
    }

    /**
     * Get documents from pool if they stored
     *
     * @param array $ids
     */
    public function getDocumentsFromDocumentPool(array $ids = null)
    {
        if(!$ids) {
            return $this->documentPool;
        }

        return array_intersect_key(
            $this->documentPool,
            array_flip(array_map('strval', $ids))
        );
    }

    /**
     * Get number of documents in document pool
     *
     * @return int
     */
    public function documentPoolCount()
    {
        return count($this->documentPool);
    }

    /**
     * Check if document exists in identity map
     *
     * @param \Sokil\Mongo\Document|\MongoId|int|string $document Document instance or it's id
     * @return boolean
     */
    public function isDocumentInDocumentPool($document)
    {
        if($document instanceof Document) {
            $document = $document->getId();
        }

        return isset($this->documentPool[(string) $document]);
    }

    /**
     * Get document by id
     *
     * @param string|\MongoId $id
     * @param callable $callable cursor callable used to configure cursor
     * @return \Sokil\Mongo\Document|null
     */
    public function getDocument($id, $callable = null)
    {
        if(!$this->isDocumentPoolEnabled) {
            return $this->getDocumentDirectly($id, $callable);
        }

        if($this->isDocumentInDocumentPool($id)) {
            return $this->getDocumentFromDocumentPool($id);
        }

        $document = $this->getDocumentDirectly($id, $callable);

        if($document) {
            $this->addDocumentToDocumentPool($document);
        }

        return $document;
    }


    /**
     * Get document by id directly omitting cache
     *
     * @param string|\MongoId $id
     * @param callable $callable cursor callable used to configure cursor
     * @return \Sokil\Mongo\Document|null
     */
    public function getDocumentDirectly($id, $callable = null)
    {
        $cursor = $this->find();

        if(is_callable($callable)) {
            call_user_func($callable, $cursor);
        }

        return $cursor
            ->byId($id)
            ->skipDocumentPool()
            ->findOne();
    }

    /**
     * Check if document belongs to collection
     *
     * @param \Sokil\Mongo\Document $document
     * @return type
     */
    public function hasDocument(Document $document)
    {
        return (bool) $this->getDocument($document->getId());
    }

    /**
     * Get documents by list of id
     *
     * @param array $idList list of ids
     * @param callable $callable cursor callable used to configure cursor
     * @return array|null
     */
    public function getDocuments(array $idList, $callable = null)
    {
        $cursor = $this->find();

        if(is_callable($callable)) {
            call_user_func($callable, $cursor);
        }
        
        $documents = $cursor->byIdList($idList)->findAll();
        if(!$documents) {
            return array();
        }

        if($this->isDocumentPoolEnabled) {
            $this->addDocumentsToDocumentPool($documents);
        }

        return $documents;
    }

    /**
     * Save document
     *
     * @deprecated since v.1.8.0 use Document::save() method
     * @param \Sokil\Mongo\Document $document
     * @param bool $validate validate or not before save
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     * @throws \Sokil\Mongo\Document\InvalidDocumentException
     */
    public function saveDocument(Document $document, $validate = true)
    {
        $document->save($validate);
        return $this;
    }

    public function deleteDocument(Document $document)
    {
        if($document->triggerEvent('beforeDelete')->isCancelled()) {
            return $this;
        }

        $status = $this->_mongoCollection->remove(array(
            '_id'   => $document->getId()
        ));

        $document->triggerEvent('afterDelete');

        if(true !== $status && $status['ok'] != 1) {
            throw new Exception('Delete document error: ' . $status['err']);
        }

        // drop from document's pool
        $this->removeDocumentFromDocumentPool($document);

        return $this;
    }

    /**
     * Delete documents by expression
     * 
     * @param callable|array|\Sokil\Mongo\Expression $expression
     * @return \Sokil\Mongo\Collection
     * @throws Exception
     */
    public function deleteDocuments($expression)
    {
        // get expression from callable
        if(is_callable($expression)) {
            $expression = call_user_func($expression, $this->expression());
        }

        // get array from Expression object
        if($expression instanceof Expression) {
            $expression = $expression->toArray();
        } elseif(!is_array($expression)) {
            throw new Exception('Wrong expression specified');
        }

        // remove
        $result = $this->_mongoCollection->remove($expression);

        // check result
        if(true !== $result && $result['ok'] != 1) {
            throw new Exception('Error removing documents from collection: ' . $result['err']);
        }

        return $this;
    }

    /**
     * Insert bultiple documents defined as arrays
     *
     * @param array $rows list of documents to insert, defined as arrays
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Document\InvalidDocumentException
     * @throws \Sokil\Mongo\Exception
     */
    public function insertMultiple($rows, $validate = true)
    {
        if($validate) {
            $document = $this->createDocument();
            foreach($rows as $row) {
                $document->merge($row);

                if(!$document->isValid()) {
                    throw new InvalidDocumentException('Document is invalid on batch insert');
                }

                $document->reset();
            }
        }

        $result = $this->_mongoCollection->batchInsert($rows);

        // If the w parameter is set to acknowledge the write,
        // returns an associative array with the status of the inserts ("ok")
        // and any error that may have occurred ("err").
        if(is_array($result)) {
            if($result['ok'] != 1) {
                throw new Exception('Batch insert error: ' . $result['err']);
            }

            return $this;
        }

        // Otherwise, returns TRUE if the batch insert was successfully sent,
        // FALSE otherwise.
        if(!$result) {
            throw new Exception('Batch insert error');
        }

        return $this;
    }

    /**
     * Direct insert of array to MongoDB without creating document object and validation
     *
     * @param array $document
     * @return \Sokil\Mongo\Collection
     * @throws Exception
     */
    public function insert(array $document)
    {
        $result = $this->_mongoCollection->insert($document);

        // if write concern acknowledged
        if(is_array($result)) {
            if($result['ok'] != 1) {
                throw new Exception('Insert error: ' . $result['err'] . ': ' . $result['errmsg']);
            }

            return $this;
        }

        // if write concern unacknowledged
        if(!$result) {
            throw new Exception('Insert error');
        }

        return $this;
    }

    /**
     * Update multiple documents
     *
     * @param \Sokil\Mongo\Expression|array|callable $expression expression to define
     *  which documents will change.
     * @param \Sokil\Mongo\Operator|array|callable $updateData new data or operators
     *  to update
     * @param array $options update options, see http://php.net/manual/ru/mongocollection.update.php
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     */
    public function update($expression, $updateData, array $options = array())
    {
        // get expression from callable
        if(is_callable($expression)) {
            $expression = call_user_func($expression, new Expression);
        }

        // get expression array
        if($expression instanceof Expression) {
            $expression = $expression->toArray();
        } elseif(!is_array($expression)) {
            $expression = array();
        }

        // get operator from callable
        if(is_callable($updateData)) {
            $updateData = call_user_func($updateData, new Operator);
        } elseif (is_array($updateData)) {
            $updateDataOperators = new Operator();
            foreach ($updateData as $fieldName => $value) {
                $updateDataOperators->set($fieldName, $value);
            }
            $updateData = $updateDataOperators;
        }

        // get operator as array
        if($updateData instanceof Operator) {
            $updateData = $updateData->getAll();
        } else {
            throw new Exception('Operator must be instance of Operator or callable');
        }

        // execute update operator
        $result = $this->_mongoCollection->update(
            $expression,
            $updateData,
            $options
        );

        // if write concern acknowledged
        if(is_array($result)) {
            if($result['ok'] != 1) {
                throw new Exception('Update error: ' . $result['err'] . ': ' . $result['errmsg']);
            }

            return $this;
        }

        // if write concern unacknowledged
        if(!$result) {
            throw new Exception('Update error');
        }

        return $this;
    }

    /**
     * Update multiple documents
     *
     * @param \Sokil\Mongo\Expression|array|callable $expression expression to define
     *  which documents will change.
     * @param \Sokil\Mongo\Operator|array|callable $updateData new data or operators
     *  to update
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     */
    public function updateMultiple($expression, $updateData)
    {
        return $this->update($expression, $updateData, array(
            'multiple'  => true,
        ));
    }

    /**
     * Update all documents
     * 
     * @param \Sokil\Mongo\Operator|array|callable $updateData new data or operators
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     */
    public function updateAll($updateData)
    {
        return $this->update(array(), $updateData, array(
            'multiple'  => true,
        ));
    }

    /**
     * Create aggregator pipeline instance
     *
     * @return \Sokil\Mongo\Pipeline
     * @deprecated since 1.10.10, use Collection::createAggregator() or callable in Collection::aggregate()
     */
    public function createPipeline()
    {
        return $this->createAggregator();
    }

    /**
     * Start aggregation
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/
     * @return \Sokil\Mongo\Pipeline
     */
    public function createAggregator()
    {
        return new Pipeline($this);
    }

    /**
     * Aggregate using pipeline
     *
     * @param callable|array|\Sokil\Mongo\Pipeline $pipeline list of pipeline stages
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/
     * @return array result of aggregation
     * @throws \Sokil\Mongo\Exception
     */
    public function aggregate($pipeline)
    {
        // configure through callable
        if (is_callable($pipeline)) {
            $pipelineConfiguratorCallable = $pipeline;
            $pipeline = $this->createAggregator();
            call_user_func($pipelineConfiguratorCallable, $pipeline);
        }

        // get aggregation array
        if ($pipeline instanceof Pipeline) {
            $pipeline = $pipeline->toArray();
        } elseif (!is_array($pipeline)) {
            throw new Exception('Wrong pipeline specified');
        }

        // log
        $client = $this->_database->getClient();
        if($client->hasLogger()) {
            $client->getLogger()->debug(
                get_called_class() . ':<br><b>Pipeline</b>:<br>' .
                json_encode($pipeline));
        }

        // aggregate
        $status = $this->_database->executeCommand(array(
            'aggregate' => $this->getName(),
            'pipeline'  => $pipeline
        ));

        if($status['ok'] != 1) {
            throw new Exception('Aggregate error: ' . $status['errmsg']);
        }

        return $status['result'];
    }

    public function explainAggregate($pipeline)
    {
        if(version_compare($this->getDatabase()->getClient()->getDbVersion(), '2.6.0', '<')) {
            throw new Exception('Explain of aggregation implemented only from 2.6.0');
        }

        if($pipeline instanceof Pipeline) {
            $pipeline = $pipeline->toArray();
        }
        elseif(!is_array($pipeline)) {
            throw new Exception('Wrong pipeline specified');
        }

        // aggregate
        return $this->_database->executeCommand(array(
            'aggregate' => $this->getName(),
            'pipeline'  => $pipeline,
            'explain'   => true
        ));
    }

    /**
     * Validates a collection. The method scans a collection’s data structures
     * for correctness and returns a single document that describes the
     * relationship between the logical collection and the physical
     * representation of the data.
     *
     * @link http://docs.mongodb.org/manual/reference/method/db.collection.validate/
     * @param bool $full Specify true to enable a full validation and to return
     *      full statistics. MongoDB disables full validation by default because it
     *      is a potentially resource-intensive operation.
     * @return array
     * @throws Exception
     */
    public function validate($full = false)
    {
        $response = $this->_mongoCollection->validate($full);
        if(!$response || $response['ok'] != 1) {
            throw new Exception($response['errmsg']);
        }

        return $response;
    }

    /**
     * Create index
     *
     * @param array $key
     * @param array $options see @link http://php.net/manual/en/mongocollection.ensureindex.php
     * @return \Sokil\Mongo\Collection
     */
    public function ensureIndex($key, array $options = array())
    {
        $this->_mongoCollection->ensureIndex($key, $options);
        return $this;
    }

    /**
     * Create unique index
     *
     * @param array $key
     * @param boolean $dropDups
     * @return \Sokil\Mongo\Collection
     */
    public function ensureUniqueIndex($key, $dropDups = false)
    {
        $this->_mongoCollection->ensureIndex($key, array(
            'unique'    => true,
            'dropDups'  => (bool) $dropDups,
        ));

        return $this;
    }

    /**
     * Create sparse index.
     *
     * Sparse indexes only contain entries for documents that have the indexed
     * field, even if the index field contains a null value. The index skips
     * over any document that is missing the indexed field.
     *
     * @link http://docs.mongodb.org/manual/core/index-sparse/
     *
     * @param string|array $key An array specifying the index's fields as its
     *  keys. For each field, the value is either the index direction or index
     *  type. If specifying direction, specify 1 for ascending or -1
     *  for descending.
     *
     * @return \Sokil\Mongo\Collection
     */
    public function ensureSparseIndex($key)
    {
        $this->_mongoCollection->ensureIndex($key, array(
            'sparse'    => true,
        ));

        return $this;
    }

    /**
     * Create TTL index
     *
     * @link http://docs.mongodb.org/manual/tutorial/expire-data/
     *
     * If seconds not specified then document expired at specified time, as
     * described at @link http://docs.mongodb.org/manual/tutorial/expire-data/#expire-documents-at-a-certain-clock-time
     *
     * @param string|array $key key must be date to use TTL
     * @param int $seconds
     * @return \Sokil\Mongo\Collection
     */
    public function ensureTTLIndex($key, $seconds = 0)
    {
        $this->_mongoCollection->ensureIndex($key, array(
            'expireAfterSeconds' => $seconds,
        ));

        return $this;
    }

    /**
     * Create geo index 2dsphere
     *
     * @link http://docs.mongodb.org/manual/tutorial/build-a-2dsphere-index/
     *
     * @param string $field
     * @return \Sokil\Mongo\Collection
     */
    public function ensure2dSphereIndex($field)
    {
        $this->_mongoCollection->ensureIndex(array(
            $field => '2dsphere',
        ));

        return $this;
    }

    /**
     * Create geo index 2dsphere
     *
     * @link http://docs.mongodb.org/manual/tutorial/build-a-2d-index/
     *
     * @param string $field
     * @return \Sokil\Mongo\Collection
     */
    public function ensure2dIndex($field)
    {
        $this->_mongoCollection->ensureIndex(array(
            $field => '2d',
        ));

        return $this;
    }

    /**
     * Create indexes based on self::$_index metadata
     *
     * @return \Sokil\Mongo\Collection
     * @throws \Exception
     */
    public function initIndexes()
    {
        // read index definition from collection options
        // if not specified - use defined in property
        $indexDefinition = $this->getOption('index', $this->_index);

        // ensure indexes
        foreach($indexDefinition as $options) {

            if(empty($options['keys'])) {
                throw new Exception('Keys not specified');
            }

            $keys = $options['keys'];
            unset($options['keys']);

            $this->_mongoCollection->ensureIndex($keys, $options);
        }

        return $this;
    }

    /**
     * Get index info
     * @return array
     */
    public function getIndexes()
    {
        return $this->_mongoCollection->getIndexInfo();
    }

    public function readPrimaryOnly()
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_PRIMARY);
        return $this;
    }

    public function readPrimaryPreferred(array $tags = null)
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);
        return $this;
    }

    public function readSecondaryOnly(array $tags = null)
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_SECONDARY, $tags);
        return $this;
    }

    public function readSecondaryPreferred(array $tags = null)
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);
        return $this;
    }

    public function readNearest(array $tags = null)
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_NEAREST, $tags);
        return $this;
    }

    public function getReadPreference()
    {
        return $this->_mongoCollection->getReadPreference();
    }

    /**
     * Define write concern for all requests to current collection
     *
     * @param string|integer $w write concern
     * @param int $timeout timeout in milliseconds
     * @throws \Sokil\Mongo\Exception
     * @return \Sokil\Mongo\Collection
     */
    public function setWriteConcern($w, $timeout = 10000)
    {
        if(!$this->_mongoCollection->setWriteConcern($w, (int) $timeout)) {
            throw new Exception('Error setting write concern');
        }

        return $this;
    }

    /**
     * Define unacknowledged write concern for all requests to current collection
     *
     * @param int $timeout timeout in milliseconds
     * @throws \Sokil\Mongo\Exception
     * @return \Sokil\Mongo\Collection
     */
    public function setUnacknowledgedWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern(0, (int) $timeout);
        return $this;
    }

    /**
     * Define majority write concern for all requests to current collection
     *
     * @param int $timeout timeout in milliseconds
     * @throws \Sokil\Mongo\Exception
     * @return \Sokil\Mongo\Collection
     */
    public function setMajorityWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern('majority', (int) $timeout);
        return $this;
    }

    /**
     * Get currently active write concern on all requests to collection
     *
     * @return int|string write concern
     */
    public function getWriteConcern()
    {
        return $this->_mongoCollection->getWriteConcern();
    }

    /**
     * Get collection stat
     *
     * @return array collection stat
     */
    public function stats()
    {
        return $this->getDatabase()->executeCommand(array(
            'collstats' => $this->getName(),
        ));
    }
}
