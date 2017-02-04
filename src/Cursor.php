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

use Sokil\Mongo\Exception\CursorException;
use Sokil\Mongo\Exception\FeatureNotSupportedException;
use Sokil\Mongo\Exception\WriteException;

/**
 * @mixin Expression
 */
class Cursor implements
    \Iterator,
    \Countable
{
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private $client;

    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    /**
     *
     * @var array
     */
    private $fields = array();

    /**
     *
     * @var \MongoCursor
     */
    private $cursor;
    /**
     *
     * @var Expression
     */
    private $expression;

    /**
     * Offset
     * @var int
     */
    private $skip = 0;

    /**
     * Limit
     * @var int
     */
    private $limit = 0;

    /**
     * Definition of sort
     * @var array
     */
    private $sort = array();

    /**
     * Definition of read preference
     * @var array
     */
    private $readPreference = array();

    /**
     * Return result as array or as Document instance
     * @var boolean
     */
    private $isResultAsArray = false;

    /**
     * Cursor options
     * @var array
     */
    private $options = array(
        'expressionClass' => '\Sokil\Mongo\Expression',
        /**
         * @link http://docs.mongodb.org/manual/reference/method/cursor.batchSize/
         * @var int number of documents to return in each batch of the response from the MongoDB instance
         */
        'batchSize' => null,
        // client timeout
        'clientTimeout' => null,
        // Specifies a cumulative time limit in milliseconds to be allowed by the server for processing operations on the cursor.
        'serverTimeout' => null,
    );

    /**
     * Use document pool to create Document object from array
     * @var bool
     */
    private $isDocumentPoolUsed = true;

    /**
     * Index hinting
     * @param \Sokil\Mongo\Collection $collection
     * @param array $options
     */
    private $hint;

    public function __construct(Collection $collection, array $options = null)
    {
        $this->collection = $collection;

        $this->client = $this->collection->getDatabase()->getClient();

        if (!empty($options)) {
            $this->options = $options + $this->options;
        }

        // expression
        $this->expression = $this->expression();
    }

    public function __call($name, $arguments)
    {
        call_user_func_array(
            array($this->expression, $name),
            $arguments
        );

        return $this;
    }

    /**
     * Get option
     *
     * @param string|int $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * Get result as array
     *
     * @return $this
     */
    public function asArray()
    {
        $this->isResultAsArray = true;
        return $this;
    }

    /**
     * Get result as object
     * @return $this
     */
    public function asObject()
    {
        $this->isResultAsArray = false;
        return $this;
    }

    /**
     * Check if result returned as array
     * @return bool
     */
    public function isResultAsArray()
    {
        return $this->isResultAsArray;
    }

    /**
     * Return only specified fields
     *
     * @param array $fields
     * @return Cursor
     */
    public function fields(array $fields)
    {
        $this->fields = array_fill_keys($fields, 1);

        $this->skipDocumentPool();

        return $this;
    }

    /**
     * Return all fields except specified
     *
     * @param array $fields
     * @return \Sokil\Mongo\Cursor
     */
    public function skipFields(array $fields)
    {
        $this->fields = array_fill_keys($fields, 0);

        $this->skipDocumentPool();

        return $this;
    }

    /**
     * Append field to accept list
     *
     * @param string $field field name
     *
     * @return Cursor
     */
    public function field($field)
    {
        $this->fields[$field] = 1;

        $this->skipDocumentPool();

        return $this;
    }

    /**
     * Append field to skip list
     *
     * @param string $field field name
     * @return Cursor
     */
    public function skipField($field)
    {
        $this->fields[$field] = 0;

        $this->skipDocumentPool();

        return $this;
    }

    /**
     * Paginate list of sub-documents
     *
     * @param string $field
     * @param integer $limit
     * @param integer $skip
     * @return \Sokil\Mongo\Cursor
     * @throws Exception
     */
    public function slice($field, $limit, $skip = null)
    {
        $limit  = (int) $limit;
        $skip   = (int) $skip;

        if ($skip) {
            $this->fields[$field] = array('$slice' => array($skip, $limit));
        } else {
            $this->fields[$field] = array('$slice' => $limit);
        }

        $this->skipDocumentPool();

        return $this;
    }

    /**
     * Merge expression
     * @param \Sokil\Mongo\Expression $expression
     * @return \Sokil\Mongo\Cursor
     */
    public function query(Expression $expression)
    {
        $this->expression->merge($expression);
        return $this;
    }

    /**
     * Helper to create new expression
     *
     * @return \Sokil\Mongo\Expression
     */
    public function expression()
    {
        return new $this->options['expressionClass'];
    }

    /**
     * Filter by list of \MongoId
     *
     * @param array $idList list of ids
     * @return \Sokil\Mongo\Cursor
     */
    public function byIdList(array $idList)
    {
        $this->expression->whereIn('_id', self::mixedToMongoIdList($idList));
        return $this;
    }

    /**
     * Filter by id
     *
     * @param string|\MongoId $id id of document
     * @return \Sokil\Mongo\Cursor
     */
    public function byId($id)
    {
        if ($id instanceof \MongoId) {
            $this->expression->where('_id', $id);
        } else {
            try {
                $this->expression->where('_id', new \MongoId($id));
            } catch (\MongoException $e) {
                $this->expression->where('_id', $id);
            }
        }

        return $this;
    }

    /**
     * Skip defined number of documents
     *
     * @param int $skip number of documents to skip
     * @return \Sokil\Mongo\Cursor
     */
    public function skip($skip)
    {
        $this->skip = (int) $skip;

        return $this;
    }

    /**
     * Limit result set to specified number of elements
     *
     * @param int $limit number of elements in result set
     * @param int|null $offset number of elements to skip
     * @return \Sokil\Mongo\Cursor
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = (int) $limit;

        if (null !== $offset) {
            $this->skip($offset);
        }

        return $this;
    }

    /**
     * Specifies the number of documents to return in each batch of the response from the MongoDB instance.
     *
     * @param int $size number of documents
     * @link http://docs.mongodb.org/manual/reference/method/cursor.batchSize/
     * @return \Sokil\Mongo\Cursor
     */
    public function setBatchSize($size)
    {
        $this->options['batchSize'] = (int) $size;

        return $this;
    }

    /**
     * Instructs the driver to stop waiting for a response and throw a
     * MongoCursorTimeoutException after a set time.
     * A timeout can be set at any time and will affect subsequent queries on
     * the cursor, including fetching more results from the database.
     *
     * @param int $ms
     * @return Cursor
     */
    public function setClientTimeout($ms)
    {
        $this->options['clientTimeout'] = (int) $ms;
        return $this;
    }

    /**
     * Server-side timeout for a query,
     * Specifies a cumulative time limit in milliseconds to be allowed
     * by the server for processing operations on the cursor.
     *
     * @param int $ms
     * @return \Sokil\Mongo\Cursor
     */
    public function setServerTimeout($ms)
    {
        $this->options['serverTimeout'] = (int) $ms;
        return $this;
    }

    /**
     * Sort result by specified keys and directions
     *
     *  An array of fields by which to sort. Each element in the array has as key the field name, and as value either
     * 1 for ascending sort, or -1 for descending sort. Each result is first sorted on the first field in the array,
     * then (if it exists) on the second field in the array, etc. This means that the order of the fields in the
     * fields array is important. See also the examples section.
     *
     * @param array $sort
     * @return Cursor
     */
    public function sort(array $sort)
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     *
     * @return \MongoCursor
     */
    private function getCursor()
    {
        if ($this->cursor) {
            return $this->cursor;
        }

        $this->cursor = $this->collection
            ->getMongoCollection()
            ->find($this->expression->toArray(), $this->fields);

        if ($this->skip) {
            $this->cursor->skip($this->skip);
        }

        if ($this->limit) {
            $this->cursor->limit($this->limit);
        }

        if ($this->options['batchSize']) {
            $this->cursor->batchSize($this->options['batchSize']);
        }

        if ($this->options['clientTimeout']) {
            $this->cursor->timeout($this->options['clientTimeout']);
        }

        if ($this->options['serverTimeout']) {
            $this->cursor->maxTimeMS($this->options['clientTimeout']);
        }

        if (!empty($this->sort)) {
            $this->cursor->sort($this->sort);
        }

        if ($this->hint) {
            $this->cursor->hint($this->hint);
        }

        // log request
        if ($this->client->hasLogger()) {
            $this->client->getLogger()->debug(get_called_class() . ': ' . json_encode(array(
                'collection' => $this->collection->getName(),
                'query' => $this->expression->toArray(),
                'project' => $this->fields,
                'sort' => $this->sort,
            )));
        }

        $this->cursor->rewind();

        // define read preferences
        if (!empty($this->readPreference)) {
            $this->cursor->setReadPreference(
                $this->readPreference['type'],
                $this->readPreference['tagsets']
            );
        }

        return $this->cursor;
    }

    /**
     * Count documents in result without applying limit and offset
     * @return int count
     */
    public function count()
    {
        return (int) $this->collection
            ->getMongoCollection()
            ->count($this->expression->toArray());
    }

    public function explain()
    {
        if (Client::isEmulationMode()) {
            throw new FeatureNotSupportedException('Feature not implemented in emulation mode');
        }

        return $this->getCursor()->explain();
    }

    /**
     * Count documents in result with applying limit and offset
     *
     * ext-mongo:1.0.7	Added limit and skip as second and third parameters, respectively.
     * ext-mongo:1.6.0	The second parameter is now an options array. Passing limit and skip as the second and third
     *                  parameters, respectively, is deprecated.
     *
     * @return int
     */
    public function limitedCount()
    {
        if (version_compare(\MongoClient::VERSION, '1.0.7', '<')) {
            throw new FeatureNotSupportedException('Limit and skip not supported in ext-mongo versions prior to 1.0.7');
        }

        return (int) $this->collection
            ->getMongoCollection()
            ->count(
                $this->expression->toArray(),
                $this->limit,
                $this->skip
            );
    }


    /**
     * Gte list of \MongoId of current search query
     * @return array
     */
    public function getIdList()
    {
        return self::mixedToMongoIdList($this->findAll());
    }

    /**
     * Find one document which correspond to expression
     *
     * @return Document|array|null
     *
     * @throws CursorException
     */
    public function findOne()
    {
        try {
            $mongoDocument = $this->collection
                ->getMongoCollection()
                ->findOne(
                    $this->expression->toArray(),
                    $this->fields
                );
        } catch (\Exception $e) {
            throw new CursorException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        if (empty($mongoDocument)) {
            return null;
        }

        if (true === $this->isResultAsArray) {
            return $mongoDocument;
        }

        return $this->collection->hydrate(
            $mongoDocument,
            $this->isDocumentPoolUsed()
        );
    }

    /**
     *
     * @return array result of searching
     */
    public function findAll()
    {
        return iterator_to_array($this);
    }

    /**
     * Get random document
     * @return
     */
    public function findRandom()
    {
        $count = $this->count();

        if (!$count) {
            return null;
        }

        if (1 === $count) {
            return $this->findOne();
        }

        return $this
            ->skip(mt_rand(0, $count - 1))
            ->limit(1)
            ->current();
    }

    /**
     * Get query builder's expression
     *
     * @return Expression
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Get MongoDB query array
     *
     * @return array
     */
    public function getMongoQuery()
    {
        return $this->expression->toArray();
    }
    
    /**
     * Return the values from a single field in the result set of documents
     *
     * @param string $fieldName
     * @return array
     */
    public function pluck($fieldName)
    {
        $isEmbeddedDocumentField = false !== strpos($fieldName, '.');

        $valueList = array();

        if ($isEmbeddedDocumentField) {
            // get result
            if ($this->isResultAsArray) {
                $cursor = clone $this;
                $documentObjectList = $cursor->asObject()->findAll();
                unset($cursor);
            } else {
                $documentObjectList = $this->findAll();
            }
            // get value of field
            foreach ($documentObjectList as $key => $documentObject) {
                $valueList[$key] = $documentObject->get($fieldName);
            }
        } else {
            // get result
            if ($this->isResultAsArray) {
                $documentArrayList = $this->findAll();
            } else {
                $cursor = clone $this;
                $documentArrayList = $cursor->asArray()->findAll();
                unset($cursor);
            }
            // get values of field
            $valueList = array_column($documentArrayList, $fieldName, '_id');
        }

        return $valueList;
    }

    /**
     * Get document instance and remove it from collection
     *
     * @return \Sokil\Mongo\Document
     */
    public function findAndRemove()
    {
        $mongoDocument = $this->collection->getMongoCollection()->findAndModify(
            $this->expression->toArray(),
            null,
            $this->fields,
            array(
                'remove' => true,
                'sort' => $this->sort,
            )
        );

        if (empty($mongoDocument)) {
            return null;
        }

        return $this->collection->hydrate(
            $mongoDocument,
            $this->isDocumentPoolUsed()
        );
    }

    /**
     * Find first document and update it
     *
     * @param Operator $operator operations with document to update
     * @param bool $upsert if document not found - create
     * @param bool $returnUpdated if true - return updated document
     *
     * @return null|Document
     */
    public function findAndUpdate(Operator $operator, $upsert = false, $returnUpdated = true)
    {
        $mongoDocument = $this->collection
            ->getMongoCollection()
            ->findAndModify(
                $this->expression->toArray(),
                $operator ? $operator->toArray() : null,
                $this->fields,
                array(
                    'new' => $returnUpdated,
                    'sort' => $this->sort,
                    'upsert' => $upsert,
                )
            );

        if (empty($mongoDocument)) {
            return null;
        }

        return $this->collection->hydrate($mongoDocument, $this->isDocumentPoolUsed());
    }

    /**
     * Apply callable to all documents in cursor
     *
     * @param callable $handler
     * @return array
     */
    public function map($handler)
    {
        $result = array();

        foreach ($this as $id => $document) {
            $result[$id] = $handler($document);
        }

        return $result;
    }

    /**
     * Filter documents in cursor by condition in callable
     *
     * @param callable $handler
     * @return array
     */
    public function filter($handler)
    {
        $result = array();

        foreach ($this as $id => $document) {
            if (!$handler($document)) {
                continue;
            }

            $result[$id] = $document;
        }

        return $result;
    }

    /**
     * Get result set of documents.
     *
     * @return \Sokil\Mongo\ResultSet
     */
    public function getResultSet()
    {
        return new ResultSet($this->findAll());
    }

    /**
     * Get paginator
     *
     * @param int $page page number
     * @param int $itemsOnPage number of items on page
     * @return Paginator
     */
    public function paginate($page, $itemsOnPage = 30)
    {
        $paginator = new Paginator($this);

        return $paginator
            ->setCurrentPage($page)
            ->setItemsOnPage($itemsOnPage);
    }

    public function current()
    {
        $mongoDocument = $this->getCursor()->current();
        if (empty($mongoDocument)) {
            return null;
        }

        if ($this->isResultAsArray) {
            return $mongoDocument;
        }

        return $this->collection->hydrate(
            $mongoDocument,
            $this->isDocumentPoolUsed()
        );
    }

    public function key()
    {
        return $this->getCursor()->key();
    }

    public function next()
    {
        $this->getCursor()->next();
        return $this;
    }

    public function rewind()
    {
        $this->getCursor()->rewind();
        return $this;
    }

    public function valid()
    {
        return $this->getCursor()->valid();
    }

    public function readPrimaryOnly()
    {
        $this->readPreference = array(
            'type'      => \MongoClient::RP_PRIMARY,
            'tagsets'   => array(),
        );

        return $this;
    }

    public function readPrimaryPreferred(array $tags = null)
    {
        $this->readPreference = array(
            'type'      => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets'   => $tags,
        );

        return $this;
    }

    public function readSecondaryOnly(array $tags = null)
    {
        $this->readPreference = array(
            'type'      => \MongoClient::RP_SECONDARY,
            'tagsets'   => $tags,
        );

        return $this;
    }

    public function readSecondaryPreferred(array $tags = null)
    {
        $this->readPreference = array(
            'type'      => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets'   => $tags,
        );

        return $this;
    }

    public function readNearest(array $tags = null)
    {
        $this->readPreference = array(
            'type'      => \MongoClient::RP_NEAREST,
            'tagsets'   => $tags,
        );

        return $this;
    }

    /**
     * @return array
     */
    public function getReadPreference()
    {
        if ($this->cursor) {
            return $this->cursor->getReadPreference();
        }

        return $this->readPreference;
    }

    public function isDocumentPoolUsed()
    {
        return $this->isDocumentPoolUsed;
    }

    public function useDocumentPool()
    {
        $this->isDocumentPoolUsed = true;
        return $this;
    }

    public function skipDocumentPool()
    {
        $this->isDocumentPoolUsed = false;
        return $this;
    }

    /**
     * Specify index to use
     *
     * @link http://docs.mongodb.org/manual/reference/operator/meta/hint/
     * @param array|string $specification Specify the index either by the index name or by document
     * @return \Sokil\Mongo\Cursor
     */
    public function hint($specification)
    {
        $this->hint = $specification;
        return $this;
    }

    /**
     * Copy selected documents to another collection
     *
     * @param string $targetCollectionName
     * @param string|null $targetDatabaseName Target database name. If not specified - use current
     *
     * @return Cursor
     *
     * @throws WriteException
     */
    public function copyToCollection($targetCollectionName, $targetDatabaseName = null)
    {
        // target database
        if (!$targetDatabaseName) {
            $database = $this->collection->getDatabase();
        } else {
            $database = $this->client->getDatabase($targetDatabaseName);
        }

        // target collection
        $targetMongoCollection = $database
            ->getCollection($targetCollectionName)
            ->getMongoCollection();

        // cursor
        $cursor = $this->getCursor();

        $batchLimit = 100;
        $inProgress = true;

        // copy data
        while ($inProgress) {
            // get next pack of documents
            $documentList = array();
            for ($i = 0; $i < $batchLimit; $i++) {
                if (!$cursor->valid()) {
                    $inProgress = false;

                    if (!empty($documentList)) {
                        // still need batch insert
                        break;
                    } else {
                        // no documents to insert - just exit
                        break(2);
                    }
                }

                $documentList[] = $cursor->current();
                $cursor->next();
            }

            // insert
            $result = $targetMongoCollection->batchInsert($documentList);

            // With passed write concern, returns an associative array with the status of the inserts ("ok")
            // and any error that may have occurred ("err").
            // Otherwise, returns TRUE if the batch insert was successfully sent, FALSE otherwise.
            if (is_array($result)) {
                if ($result['ok'] != 1) {
                    throw new WriteException('Batch insert error: ' . $result['err']);
                }
            } elseif (false === $result) {
                throw new WriteException('Batch insert error');
            }
        }

        return $this;
    }

    /**
     * Move selected documents to another collection.
     * Documents will be removed from source collection only after
     * copying them to target collection.
     *
     * @param string $targetCollectionName
     * @param string|null $targetDatabaseName Target database name. If not specified - use current
     */
    public function moveToCollection($targetCollectionName, $targetDatabaseName = null)
    {
        // copy to target
        $this->copyToCollection($targetCollectionName, $targetDatabaseName);

        // remove from source
        $this->collection->batchDelete($this->expression);
    }

    /**
     * Used to get hash that uniquely identifies current query
     *
     * @return string
     */
    public function getHash()
    {
        $hash = array();

        // expression
        $hash[] = json_encode($this->expression->toArray());

        // sorts
        if (!empty($this->sort)) {
            $sort = $this->sort;
            ksort($sort);
            $hash[] = implode('', array_merge(array_keys($sort), array_values($sort)));
        }

        // fields
        if (!empty($this->fields)) {
            $fields = $this->fields;
            ksort($fields);
            $hash[] = implode('', array_merge(array_keys($fields), array_values($fields)));
        }

        // skip and limit
        $hash[] = $this->skip;
        $hash[] = $this->limit;

        // get hash
        return md5(implode(':', $hash));
    }

    /**
     * Get list of MongoId objects from array of strings, MongoId's and Document's
     *
     * @param array $list
     * @return array list of \MongoId
     */
    public static function mixedToMongoIdList(array $list)
    {
        return array_map(function ($element) {
            // MongoId
            if ($element instanceof \MongoId) {
                return $element;
            }

            // \Sokil\Mongo\Document
            if ($element instanceof Document) {
                return $element->getId();
            }

            // array with id key
            if (is_array($element)) {
                if (!isset($element['_id'])) {
                    throw new \InvalidArgumentException('Array must have _id key');
                }
                return $element['_id'];
            }

            // string
            if (is_string($element)) {
                try {
                    return new \MongoId($element);
                } catch (\MongoException $e) {
                    return $element;
                }
            }

            // int
            if (is_int($element)) {
                return $element;
            }

            throw new \InvalidArgumentException('Must be \MongoId, \Sokil\Mongo\Document, array with _id key, string or integer');
        }, array_values($list));
    }
}
