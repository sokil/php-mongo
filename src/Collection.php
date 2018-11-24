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

use Sokil\Mongo\Document\InvalidDocumentException;
use Sokil\Mongo\Exception\FeatureNotSupportedException;
use Sokil\Mongo\Collection\Definition;
use Sokil\Mongo\Enum\Language;

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
     * @var string fully qualified class name of collection
     */
    protected $mongoCollectionClassName = '\MongoCollection';

    /**
     * @var string expression class. This class may be overloaded to define
     *  own query methods (whereUserAgeGreatedThan(), etc.)
     * @deprecated since 1.13 Use 'expressionClass' declaration in mapping
     */
    protected $_queryExpressionClass;

    /**
     * @deprecated since 1.13 Use 'documentClass' declaration in mapping
     * @var string Default class for document
     */
    private $documentClass;

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
     * @deprecated since 1.13 Use 'index' declaration in mapping
     */
    protected $_index;

    /**
     *
     * @var Database
     */
    private $database;

    /**
     *
     * @var \MongoCollection
     */
    private $collection;

    /**
     * @var string
     */
    private $collectionName;

    /**
     * Implementation of identity map pattern
     *
     * @var array list of cached documents
     */
    private $documentPool = array();

    /**
     * @deprecated since 1.13 Use 'versioning' declaration in mapping
     * @var bool default value of versioning
     */
    protected $versioning;

    /**
     * @var \Sokil\Mongo\Collection\Definition collection options
     */
    private $definition;

    public function __construct(
        Database $database,
        $collection,
        Definition $definition = null
    ) {
        // define db
        $this->database = $database;

        // init mongo collection
        if ($collection instanceof \MongoCollection) {
            $this->collectionName = $collection->getName();
            $this->collection = $collection;
        } else {
            $this->collectionName = $collection;
        }

        // init definition
        $this->definition = $definition ? $definition : new Definition();

        if (!empty($this->documentClass)) {
            $this->definition->setOption('documentClass', $this->documentClass);
        }

        if ($this->versioning !== null) {
            $this->definition->setOption('versioning', $this->versioning);
        }

        if (!empty($this->_index)) {
            $this->definition->setOption('index', $this->_index);
        }

        if (!empty($this->_queryExpressionClass)) {
            $this->definition->setOption('expressionClass', $this->_queryExpressionClass);
        }
    }

    /**
     * Start versioning documents on modify
     *
     * @deprecated since 1.13 Use 'versioning' declaration in mapping
     * @return \Sokil\Mongo\Collection
     */
    public function enableVersioning()
    {
        $this->definition->setOption('versioning', true);
        return $this;
    }

    /**
     * Check if versioning enabled
     *
     * @deprecated since 1.13 Use 'versioning' declaration in mapping
     * @return bool
     */
    public function isVersioningEnabled()
    {
        return $this->definition->getOption('versioning');
    }

    /**
     * Get option
     *
     * @param string|int $name
     * @return mixed
     */
    public function getOption($name)
    {
        return $this->definition->getOption($name);
    }

    public function getOptions()
    {
        return $this->definition->getOptions();
    }

    public function __get($name)
    {
        // support of deprecated property, use selg::getMongoCollection instead
        if ($name === '_mongoCollection') {
            return $this->getMongoCollection();
        }

        return $this->getDocument($name);
    }

    /**
     * Get name of collection
     *
     * @return string name of collection
     */
    public function getName()
    {
        return $this->collectionName;
    }

    /**
     * Get native collection instance of mongo driver
     *
     * @return \MongoCollection
     */
    public function getMongoCollection()
    {
        if (empty($this->collection)) {
            $mongoCollectionClassName = $this->mongoCollectionClassName;
            $this->collection = new $mongoCollectionClassName(
                $this->database->getMongoDB(),
                $this->collectionName
            );
        }

        return $this->collection;
    }

    /**
     *
     * @return \Sokil\Mongo\Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Delete collection
     *
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     */
    public function delete()
    {
        $status = $this->getMongoCollection()->drop();

        if ($status['ok'] != 1) {
            // check if collection exists
            if ('ns not found' !== $status['errmsg']) {
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
        $documentClass = $this->definition->getOption('documentClass');

        if (is_callable($documentClass)) {
            return call_user_func($documentClass, $documentData);
        }

        if (class_exists($documentClass)) {
            return $documentClass;
        }

        throw new Exception('Property "documentClass" must be callable or valid name of class');
    }

    /**
     * Factory method to get not stored Document instance from array
     *
     * @param array $data
     *
     * @return Document
     *
     * @throws Exception
     */
    public function createDocument(array $data = null)
    {
        $className = $this->getDocumentClassName($data);

        /* @var $document \Sokil\Mongo\Document */
        $document = new $className(
            $this,
            $data,
            array('stored' => false) + $this->definition->getOptions()
        );

        // store document to identity map
        if ($this->isDocumentPoolEnabled()) {
            $collection = $this;
            $document->onAfterInsert(function (\Sokil\Mongo\Event $event) use ($collection) {
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
    public function hydrate($data, $useDocumentPool = true)
    {
        if (!is_array($data) || !isset($data['_id'])) {
            throw new Exception('Document must be stored and has _id key');
        }

        // if document already in pool - return it
        if ($useDocumentPool && $this->isDocumentPoolEnabled() && $this->isDocumentInDocumentPool($data['_id'])) {
            return $this
                ->getDocumentFromDocumentPool($data['_id'])
                ->mergeUnmodified($data);
        }

        // init document instance
        $className = $this->getDocumentClassName($data);
        $document = new $className(
            $this,
            $data,
            array('stored' => true) + $this->definition->getOptions()
        );

        // store document in cache
        if ($useDocumentPool && $this->isDocumentPoolEnabled()) {
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
     * @param array|callable|\Sokil\Mongo\Expression $expression expression to search documents
     * @return array distinct values
     */
    public function getDistinct($selector, $expression = null)
    {
        if ($expression) {
            return $this->getMongoCollection()->distinct(
                $selector,
                Expression::convertToArray($expression)
            );
        }

        return $this->getMongoCollection()->distinct($selector);
    }

    /**
     * Create new Expression instance to use in query builder or update operations
     *
     * @return \Sokil\Mongo\Expression
     */
    public function expression()
    {
        $className = $this->definition->getExpressionClass();
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
     *
     * @return Cursor
     */
    public function find($callable = null)
    {
        /** @var Cursor $cursor */
        $cursor = new Cursor($this, array(
            'expressionClass'   => $this->definition->getExpressionClass(),
            'batchSize'         => $this->definition->getOption('batchSize'),
            'clientTimeout'     => $this->definition->getOption('cursorClientTimeout'),
            'serverTimeout'     => $this->definition->getOption('cursorServerTimeout'),
        ));

        if (is_callable($callable)) {
            $callable($cursor->getExpression());
        }

        return $cursor;
    }

    /**
     * Create document query builder
     *
     * @param callable|null $callable
     *
     * @return \Sokil\Mongo\Cursor
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
        $this->definition->setOption('documentPool', false);
        return $this;
    }

    /**
     * Start storing found documents to pool
     *
     * @return \Sokil\Mongo\Collection
     */
    public function enableDocumentPool()
    {
        $this->definition->setOption('documentPool', true);
        return $this;
    }

    /**
     * Check if document pool enabled and requested documents store to it
     *
     * @return bool
     */
    public function isDocumentPoolEnabled()
    {
        return $this->definition->getOption('documentPool');
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

        if (!isset($this->documentPool[$documentId])) {
            $this->documentPool[$documentId] = $document;
        } else {
            // merging because document after
            // load and before getting in second place may be changed
            // and this changes must be preserved:
            //
            // 1. Document loads and modifies in current session
            // 2. Document loads modified in another session
            // 3. Document loads once again in current session. Changes from stage 2 merges as unmodified

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
        foreach ($documents as $document) {
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
        if (!$ids) {
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
        if ($document instanceof Document) {
            $document = $document->getId();
        }

        return isset($this->documentPool[(string) $document]);
    }

    /**
     * Get document by id
     * If callable specified, document always loaded directly omitting document pool.
     * Method may return document as array if cursor configured through Cursor::asArray()
     *
     * @param string|\MongoId $id
     * @param callable $callable cursor callable used to configure cursor
     * @return \Sokil\Mongo\Document|array|null
     */
    public function getDocument($id, $callable = null)
    {
        if (!$this->isDocumentPoolEnabled()) {
            return $this->getDocumentDirectly($id, $callable);
        }

        if (!$callable && $this->isDocumentInDocumentPool($id)) {
            return $this->getDocumentFromDocumentPool($id);
        }

        $document = $this->getDocumentDirectly($id, $callable);

        // if callable configure cursor to return document as array,
        // than it can't be stored to document pool
        if ($document instanceof Document) {
            $this->addDocumentToDocumentPool($document);
        }

        return $document;
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
        $documentArray = $this->getMongoCollection()->getDBRef($ref);
        if (null === $documentArray) {
            return null;
        }

        return $this->hydrate($documentArray, $useDocumentPool);
    }

    /**
     * Get document by id directly omitting cache
     * Method may return document as array if cursor configured through Cursor::asArray()
     *
     * @param string|\MongoId $id
     * @param callable $callable cursor callable used to configure cursor
     * @return \Sokil\Mongo\Document|array|null
     */
    public function getDocumentDirectly($id, $callable = null)
    {
        $cursor = $this->find();

        if (is_callable($callable)) {
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
     * @param Document $document
     *
     * @return bool
     */
    public function hasDocument(Document $document)
    {
        $documentCollection = $document->getCollection();
        $documentDatabase = $documentCollection->getDatabase();

        // check connection
        if ($documentDatabase->getClient()->getDsn() !== $this->getDatabase()->getClient()->getDsn()) {
            return false;
        }

        // check database
        if ($documentDatabase->getName() !== $this->getDatabase()->getName()) {
            return false;
        }

        // check collection
        return $documentCollection->getName() == $this->getName();
    }

    /**
     * Get documents by list of id
     *
     * @param array $idList list of ids
     * @param callable $callable cursor callable used to configure cursor
     *
     * @return Document[]
     */
    public function getDocuments(array $idList, $callable = null)
    {
        $idListToFindDirectly = $idList;

        // try to egt document from pool if enabled
        $documentsInDocumentPool = array();
        if ($this->isDocumentPoolEnabled() && !$callable) {
            $documentsInDocumentPool = $this->getDocumentsFromDocumentPool($idList);
            if (count($documentsInDocumentPool) === count($idList)) {
                return $documentsInDocumentPool;
            }

            // skip ids already found in pool
            $idListToFindDirectly = array_diff_key(
                array_map('strval', $idList),
                array_keys($documentsInDocumentPool)
            );
        }

        // get documents directly
        $cursor = $this->find();

        if (is_callable($callable)) {
            call_user_func($callable, $cursor);
        }

        $documentsGettingDirectly = $cursor->byIdList($idListToFindDirectly)->findAll();
        if (empty($documentsGettingDirectly)) {
            return $documentsInDocumentPool ? $documentsInDocumentPool : array();
        }

        if ($this->isDocumentPoolEnabled()) {
            $this->addDocumentsToDocumentPool($documentsGettingDirectly);
        }

        return $documentsGettingDirectly + $documentsInDocumentPool;
    }

    /**
     * Creates batch insert operation handler
     * @param int|string $writeConcern Write concern. Default is 1 (Acknowledged)
     * @param int $timeout Timeout for write concern. Default is 10000 milliseconds
     * @param bool $ordered Determins if MongoDB must apply this batch in order (sequentally,
     *   one item at a time) or can rearrange it. Defaults to TRUE
     * @return BatchInsert
     */
    public function createBatchInsert($writeConcern = null, $timeout = null, $ordered = null)
    {
        return new BatchInsert(
            $this,
            $writeConcern,
            $timeout,
            $ordered
        );
    }

    /**
     * Creates batch update operation handler
     * @param int|string $writeConcern Write concern. Default is 1 (Acknowledged)
     * @param int $timeout Timeout for write concern. Default is 10000 milliseconds
     * @param bool $ordered Determins if MongoDB must apply this batch in order (sequentally,
     *   one item at a time) or can rearrange it. Defaults to TRUE
     * @return BatchUpdate
     */
    public function createBatchUpdate($writeConcern = null, $timeout = null, $ordered = null)
    {
        return new BatchUpdate(
            $this,
            $writeConcern,
            $timeout,
            $ordered
        );
    }

    /**
     * Creates batch delete operation handler
     * @param int|string $writeConcern Write concern. Default is 1 (Acknowledged)
     * @param int $timeout Timeout for write concern. Default is 10000 milliseconds
     * @param bool $ordered Determins if MongoDB must apply this batch in order (sequentally,
     *   one item at a time) or can rearrange it. Defaults to TRUE
     * @return BatchDelete
     */
    public function createBatchDelete($writeConcern = null, $timeout = null, $ordered = null)
    {
        return new BatchDelete(
            $this,
            $writeConcern,
            $timeout,
            $ordered
        );
    }

    /**
     * @deprecated since 1.13. Use Document::delete()
     * @param \Sokil\Mongo\Document $document
     * @return \Sokil\Mongo\Collection
     */
    public function deleteDocument(Document $document)
    {
        $document->delete();
        return $this;
    }

    /**
     * Delete documents by expression
     *
     * @param Expression|callable|array $expression
     *
     * @return Collection
     *
     * @throws Exception
     */
    public function batchDelete($expression)
    {
        // remove
        $result = $this->getMongoCollection()->remove(
            Expression::convertToArray($expression)
        );

        // check result
        if (true !== $result && $result['ok'] != 1) {
            throw new Exception('Error removing documents from collection: ' . $result['err']);
        }

        return $this;
    }

    /**
     * @deprecated since 1.13. Use Collection::batchDelete();
     *
     * @param Expression|callable|array $expression
     *
     * @return Collection
     *
     * @throws Exception
     *
     */
    public function deleteDocuments($expression = array())
    {
        return $this->batchDelete($expression);
    }

    /**
     * Insert multiple documents defined as arrays
     *
     * Prior to version 1.5.0 of the driver it was possible to use MongoCollection::batchInsert(),
     * however, as of 1.5.0 that method is now discouraged.
     *
     * You can use Collection::createBatchInsert()
     *
     * @param array $rows list of documents to insert, defined as arrays
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Document\InvalidDocumentException
     * @throws \Sokil\Mongo\Exception
     */
    public function batchInsert($rows, $validate = true)
    {
        if ($validate) {
            $document = $this->createDocument();
            foreach ($rows as $row) {
                $document->merge($row);

                if (!$document->isValid()) {
                    throw new InvalidDocumentException('Document is invalid on batch insert');
                }

                $document->reset();
            }
        }

        $result = $this->getMongoCollection()->batchInsert($rows);

        // If the w parameter is set to acknowledge the write,
        // returns an associative array with the status of the inserts ("ok")
        // and any error that may have occurred ("err").
        if (is_array($result)) {
            if ($result['ok'] != 1) {
                throw new Exception('Batch insert error: ' . $result['err']);
            }

            return $this;
        }

        // Otherwise, returns TRUE if the batch insert was successfully sent,
        // FALSE otherwise.
        if (!$result) {
            throw new Exception('Batch insert error');
        }

        return $this;
    }

    /**
     * @deprecated since 1.13 Use Collection::batchInsert()
     */
    public function insertMultiple($rows, $validate = true)
    {
        return $this->batchInsert($rows, $validate);
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
        $result = $this->getMongoCollection()->insert($document);

        // if write concern acknowledged
        if (is_array($result)) {
            if ($result['ok'] != 1) {
                throw new Exception('Insert error: ' . $result['err'] . ': ' . $result['errmsg']);
            }

            return $this;
        }

        // if write concern unacknowledged
        if (!$result) {
            throw new Exception('Insert error');
        }

        return $this;
    }

    /**
     * Update multiple documents
     *
     * @param \Sokil\Mongo\Expression|array|callable $expression expression to define
     *  which documents will change.
     * @param \Sokil\Mongo\Operator|array|callable $updateData new data or operators to update
     * @param array $options update options, see http://php.net/manual/ru/mongocollection.update.php
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     */
    public function update($expression, $updateData, array $options = array())
    {
        // execute update operator
        $result = $this->getMongoCollection()->update(
            Expression::convertToArray($expression),
            Operator::convertToArray($updateData),
            $options
        );

        // if write concern acknowledged
        if (is_array($result)) {
            if ($result['ok'] != 1) {
                throw new Exception(sprintf('Update error: %s: %s', $result['err'], $result['errmsg']));
            }
            return $this;
        }

        // if write concern unacknowledged
        if (!$result) {
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
    public function batchUpdate($expression, $updateData)
    {
        return $this->update($expression, $updateData, array(
            'multiple'  => true,
        ));
    }

    /**
     * @deprecated since 1.13 Use Collection::batchUpdate()
     */
    public function updateMultiple($expression, $updateData)
    {
        return $this->batchUpdate($expression, $updateData);
    }

    /**
     * Update all documents
     *
     * @deprecated since 1.13. Use Collection::batchUpdate([])
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
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/
     *
     * @param callable|array|Pipeline $pipeline list of pipeline stages
     * @param array aggregate options
     * @param bool $asCursor return result as cursor
     *
     * @throws \Sokil\Mongo\Exception
     * @return array result of aggregation
     */
    public function aggregate(
        $pipeline,
        array $options = array(),
        $asCursor = false
    ) {
        // configure through callable
        if (is_callable($pipeline)) {
            $pipelineConfiguratorCallable = $pipeline;
            $pipeline = $this->createAggregator();
            call_user_func($pipelineConfiguratorCallable, $pipeline);
        }

        // get aggregation array
        if ($pipeline instanceof Pipeline) {
            if (!empty($options)) {
                $options = array_merge($pipeline->getOptions(), $options);
            } else {
                $options = $pipeline->getOptions();
            }
            $pipeline = $pipeline->toArray();
        } elseif (!is_array($pipeline)) {
            throw new Exception('Wrong pipeline specified');
        }

        // Check options for supporting by database
        if (!empty($options)) {
            $this->validateAggregationOptions($options);
        }

        // return result as cursor
        if ($asCursor) {
            if (version_compare(\MongoClient::VERSION, '1.5.0', '>=')) {
                return $this->getMongoCollection()->aggregateCursor($pipeline, $options);
            } else {
                throw new FeatureNotSupportedException('Aggregate cursor supported from driver version 1.5');
            }
        }

        // prepare command
        $command = array(
            'aggregate' => $this->getName(),
            'pipeline'  => $pipeline,
        );

        // add options
        if (!empty($options)) {
            $command += $options;
        }

        // aggregate
        $status = $this->database->executeCommand($command);
        if ($status['ok'] != 1) {
            throw new Exception('Aggregate error: ' . $status['errmsg']);
        }

        // explain response
        if (!empty($command['explain'])) {
            return $status['stages'];
        }

        // result response
        return $status['result'];
    }

    /**
     * Check if aggragator options supported by database
     *
     * @param array $options
     * @throws FeatureNotSupportedException
     */
    private function validateAggregationOptions(array $options)
    {
        // get db version
        $client = $this->getDatabase()->getClient();
        $dbVersion = $client->getDbVersion();

        // check options for db < 2.6
        if (version_compare($dbVersion, '2.6.0', '<')) {
            if (!empty($options['explain'])) {
                throw new FeatureNotSupportedException(
                    'Explain of aggregation implemented only from 2.6.0'
                );
            }

            if (!empty($options['allowDiskUse'])) {
                throw new FeatureNotSupportedException(
                    'Option allowDiskUse of aggregation implemented only from 2.6.0'
                );
            }

            if (!empty($options['cursor'])) {
                throw new FeatureNotSupportedException(
                    'Option cursor of aggregation implemented only from 2.6.0'
                );
            }
        }

        // check options for db < 3.2
        if (version_compare($dbVersion, '3.2.0', '<')) {
            if (!empty($options['bypassDocumentValidation'])) {
                throw new FeatureNotSupportedException(
                    'Option bypassDocumentValidation of aggregation implemented only from 3.2.0'
                );
            }

            if (!empty($options['readConcern'])) {
                throw new FeatureNotSupportedException(
                    'Option readConcern of aggregation implemented only from 3.2.0'
                );
            }
        }
    }

    /**
     * Explain aggregation
     *
     * @deprecated use pipeline option 'explain' in Collection::aggregate() or method Pipeline::explain()
     * @param array|Pipeline $pipeline
     * @return array result
     * @throws Exception
     */
    public function explainAggregate($pipeline)
    {
        if (version_compare($this->getDatabase()->getClient()->getDbVersion(), '2.6.0', '<')) {
            throw new Exception('Explain of aggregation implemented only from 2.6.0');
        }

        if ($pipeline instanceof Pipeline) {
            $pipeline = $pipeline->toArray();
        } elseif (!is_array($pipeline)) {
            throw new Exception('Wrong pipeline specified');
        }

        // aggregate
        return $this->database->executeCommand(array(
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
        $response = $this->getMongoCollection()->validate($full);
        if (empty($response) || $response['ok'] != 1) {
            throw new Exception($response['errmsg']);
        }

        return $response;
    }

    /**
     * Create index
     *
     * @deprecated since 1.19 Use self::createIndex()
     * @param array $key
     * @param array $options see @link http://php.net/manual/en/mongocollection.ensureindex.php
     * @return \Sokil\Mongo\Collection
     */
    public function ensureIndex(array $key, array $options = array())
    {
        return $this->createIndex($key, $options);
    }

    /**
     * Create index
     *
     * @param array $key
     * @param array $options see @link http://php.net/manual/en/mongocollection.ensureindex.php
     * @return \Sokil\Mongo\Collection
     */
    public function createIndex(array $key, array $options = array())
    {
        $this->getMongoCollection()->createIndex($key, $options);
        return $this;
    }

    /**
     * Delete index
     *
     * @param array $key
     * @return \Sokil\Mongo\Collection
     */
    public function deleteIndex(array $key)
    {
        $this->getMongoCollection()->deleteIndex($key);
        return $this;
    }

    /**
     * Create unique index
     *
     * @param array $key
     * @param boolean $dropDups
     * @return \Sokil\Mongo\Collection
     */
    public function ensureUniqueIndex(array $key, $dropDups = false)
    {
        $this->getMongoCollection()->createIndex($key, array(
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
     * @return Collection
     */
    public function ensureSparseIndex(array $key)
    {
        $this->getMongoCollection()->createIndex(
            $key,
            array(
                'sparse'    => true,
            )
        );

        return $this;
    }

    /**
     * Create TTL index
     *
     * @link http://docs.mongodb.org/manual/tutorial/expire-data/
     *
     * If seconds not specified then document expired at specified time, as described at
     * @link http://docs.mongodb.org/manual/tutorial/expire-data/#expire-documents-at-a-certain-clock-time
     *
     * @param string|array $key key must be date to use TTL
     * @param int $seconds
     * @return \Sokil\Mongo\Collection
     */
    public function ensureTTLIndex(array $key, $seconds = 0)
    {
        $this->getMongoCollection()->createIndex($key, array(
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
        if (is_array($field)) {
            $keys = array_fill_keys($field, '2dsphere');
        } else {
            $keys = array(
                $field => '2dsphere',
            );
        }

        $this->getMongoCollection()->createIndex($keys);

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
        if (is_array($field)) {
            $keys = array_fill_keys($field, '2d');
        } else {
            $keys = array(
                $field => '2d',
            );
        }

        $this->getMongoCollection()->createIndex($keys);

        return $this;
    }

    /**
     * Create fulltext index
     *
     * @link https://docs.mongodb.org/manual/core/index-text/
     * @link https://docs.mongodb.org/manual/tutorial/specify-language-for-text-index/
     *
     * If a collection contains documents or embedded documents that are in different languages,
     * include a field named language in the documents or embedded documents and specify as its value the language
     * for that document or embedded document.
     *
     * The specified language in the document overrides the default language for the text index.
     * The specified language in an embedded document override the language specified in an enclosing document or
     * the default language for the index.
     *
     * @param   array|string    $field              definition of fields where full text index ensured. May be
     *                                              string to ensure index on one field, array of fields  to
     *                                              create full text index on few fields, and * widdcard '$**' to
     *                                              create index on all fields of collection. Default value is '$**'
     *
     * @param   array           $weights            For a text index, the weight of an indexed field denotes the
     *                                              significance of the field relative to the other indexed fields
     *                                              in terms of the text search score.
     *
     * @param   string          $defaultLanguage    Default language associated with the indexed data determines
     *                                              the rules to parse word roots (i.e. stemming) and ignore stop
     *                                              words. The default language for the indexed data is english.
     *
     * @param   string          $languageOverride   To use a field with a name other than language, include the
     *                                              language_override option when creating the index.
     *
     * @return Collection
     */
    public function ensureFulltextIndex(
        $field = '$**',
        array $weights = null,
        $defaultLanguage = Language::ENGLISH,
        $languageOverride = null
    ) {
        // keys
        if (is_array($field)) {
            $keys = array_fill_keys($field, 'text');
        } else {
            $keys = array(
                $field => 'text',
            );
        }

        // options
        $options = array(
            'default_language' => $defaultLanguage,
        );

        if (!empty($weights)) {
            $options['weights'] = $weights;
        }

        if (!empty($languageOverride)) {
            $options['language_override'] = $languageOverride;
        }

        // create index
        $this->getMongoCollection()->createIndex($keys, $options);

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
        $indexDefinition = $this->definition->getOption('index');

        // ensure indexes
        foreach ($indexDefinition as $options) {
            if (empty($options['keys'])) {
                throw new Exception('Keys not specified');
            }

            $keys = $options['keys'];
            unset($options['keys']);

            if (is_string($keys)) {
                $keys = array($keys => 1);
            }

            $this->getMongoCollection()->createIndex($keys, $options);
        }

        return $this;
    }

    /**
     * Get index info
     * @return array
     */
    public function getIndexes()
    {
        return $this->getMongoCollection()->getIndexInfo();
    }

    public function readPrimaryOnly()
    {
        $this->getMongoCollection()->setReadPreference(\MongoClient::RP_PRIMARY);
        return $this;
    }

    public function readPrimaryPreferred(array $tags = null)
    {
        $this->getMongoCollection()->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);
        return $this;
    }

    public function readSecondaryOnly(array $tags = null)
    {
        $this->getMongoCollection()->setReadPreference(\MongoClient::RP_SECONDARY, $tags);
        return $this;
    }

    public function readSecondaryPreferred(array $tags = null)
    {
        $this->getMongoCollection()->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);
        return $this;
    }

    public function readNearest(array $tags = null)
    {
        $this->getMongoCollection()->setReadPreference(\MongoClient::RP_NEAREST, $tags);
        return $this;
    }

    public function getReadPreference()
    {
        return $this->getMongoCollection()->getReadPreference();
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
        if (!$this->getMongoCollection()->setWriteConcern($w, (int) $timeout)) {
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
        return $this->getMongoCollection()->getWriteConcern();
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

    /**
     * Rename collection
     * Note: if target collection exists, then command will fail
     *
     * @param string $target The name of target db and collection ex.: "testdb.newcollection"
     *
     * @return Collection
     *
     * @throws Exception
     */
    public function renameCollection($target)
    {
        return $this->replaceRenameCollection($target, false);
    }

    /**
     * Rename collection x to y, with dropping previously created target
     *
     * @param  string $target   The name of target db and collection ex.: "testdb.newcollection"
     *
     * @return Collection
     *
     * @throws Exception
     */
    public function replaceCollection($target)
    {
        return $this->replaceRenameCollection($target, true);
    }

    /**
     * Rename collection with optionally dropping previously created target collection
     *
     * @param string $newName The name of target db and collection ex.: "testdb.newcollection"
     * @param bool $dropTarget Whether to drop target or not
     *
     * @return Collection
     *
     * @throws Exception
     */
    private function replaceRenameCollection($newName, $dropTarget)
    {
        // create admin db instance
        $adminDb = $this->getDatabase()->getClient()->getDatabase('admin');

        list($targetDatabaseName, $targetCollectionName) = $this->getCompleteCollectionNamespace($newName);

        // rename current collection to target
        $response = $adminDb->executeCommand(array(
            'renameCollection' => $this->getDatabase()->getName() . '.' . $this->getName(),
            'to' => $targetDatabaseName . '.' . $targetCollectionName,
            'dropTarget' => $dropTarget,
        ));

        if ($response['ok'] !== 1.0) {
            throw new Exception('Error: #' . $response['code'] . ': ' . $response['errmsg'], $response['code']);
        }

        // re-init to new db and collection
        $this->database = $this->getDatabase()->getClient()->getDatabase($targetDatabaseName);
        $this->collection = $this->database->getCollection($targetCollectionName)->getMongoCollection();
        $this->collectionName = $this->collection->getName();

        return $this;
    }

    /**
     * Parse database and collection with dot between them
     * if there is no dot, then we assume - $target as collection
     * and getting db from this object
     *
     * @param string $target
     *
     * @return array
     */
    private function getCompleteCollectionNamespace($target)
    {
        if (mb_strpos($target, '.') !== false) {
            return explode('.', $target);
        }

        return [$this->getDatabase()->getName(), $target];
    }
}
