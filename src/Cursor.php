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

abstract class Cursor implements \Iterator, \Countable
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
    protected $_collection;

    private $fields = array();

    /**
     *
     * @var \MongoCursor
     */
    private $cursor;
    /**
     *
     * @var \Sokil\Mongo\Expression
     */
    private $expression;

    private $skip = 0;

    private $limit = 0;


    private $sort = array();

    private $readPreference = array();

    /**
     * If specified in child class - overload config from collection class
     * @var string
     */
    protected $_queryExpressionClass;

    /**
     * Return result as array or as Document instance
     * @var boolean 
     */
    protected $_resultAsArray = false;

    /**
     * Cursor options
     * @var array
     */
    private $options = array(
        'expressionClass'   => '\Sokil\Mongo\Expression',
        /**
         * @link http://docs.mongodb.org/manual/reference/method/cursor.batchSize/
         * @var int number of documents to return in each batch of the response from the MongoDB instance
         */
        'batchSize' => null,
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
        $this->_collection = $collection;

        $this->client = $this->_collection->getDatabase()->getClient();

        if($options) {
            $this->options = $options + $this->options;
        }

        // expression
        $this->expression = $this->expression();
    }

    public function __call($name, $arguments) {
        call_user_func_array(array($this->expression, $name), $arguments);
        return $this;
    }

    /**
     * Get option
     *
     * @param string|int $name
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function asArray()
    {
        $this->_resultAsArray = true;
        return $this;
    }

    public function asObject()
    {
        $this->_resultAsArray = false;
        return $this;
    }

    /**
     * Check if result returned as array
     *
     * @return bool
     */
    public function isResultAsArray()
    {
        return $this->_resultAsArray;
    }

    /**
     * Return only specified fields
     *
     * @param array $fields
     * @return \Sokil\Mongo\QueryBuilder
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
     * @return \Sokil\Mongo\QueryBuilder
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
     * @return \Sokil\Mongo\QueryBuilder
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
     * @return \Sokil\Mongo\QueryBuilder
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
     * @return \Sokil\Mongo\QueryBuilder
     * @throws Exception
     */
    public function slice($field, $limit, $skip = null)
    {
        $limit  = (int) $limit;
        $skip   = (int) $skip;

        if($skip) {
            $this->fields[$field] = array('$slice' => array($skip, $limit));
        }
        else {
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
        $expressionClass = $this->_queryExpressionClass
            ? $this->_queryExpressionClass
            : $this->options['expressionClass'];

        return new $expressionClass;
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
        if($id instanceof \MongoId) {
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

        if(null !== $offset) {
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
     * Sort result by specified keys and directions
     *
     * @param array $sort
     * @return \Sokil\Mongo\Cursor
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
        if($this->cursor) {
            return $this->cursor;
        }

        $this->cursor = $this->_collection
            ->getMongoCollection()
            ->find($this->expression->toArray(), $this->fields);

        if($this->skip) {
            $this->cursor->skip($this->skip);
        }

        if($this->limit) {
            $this->cursor->limit($this->limit);
        }

        if($this->options['batchSize']) {
            $this->cursor->batchSize($this->options['batchSize']);
        }

        if($this->sort) {
            $this->cursor->sort($this->sort);
        }

        if($this->hint) {
            $this->cursor->hint($this->hint);
        }

        // log request
        if($this->client->hasLogger()) {
            $this->client->getLogger()->debug(get_called_class() . ': ' . json_encode(array(
                'collection'    => $this->_collection->getName(),
                'query'         => $this->expression->toArray(),
                'project'       => $this->fields,
                'sort'          => $this->sort,
            )));
        }

        $this->cursor->rewind();

        // define read preferences
        if($this->readPreference) {
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
        return (int) $this->_collection
            ->getMongoCollection()
            ->count($this->expression->toArray());
    }

    public function explain()
    {
        return $this->getCursor()->explain();
    }

    /**
     * Count documents in result with applying limit and offset
     * @return int count
     */
    public function limitedCount()
    {
        return (int) $this->_collection
            ->getMongoCollection()
            ->count($this->expression->toArray(), $this->limit, $this->skip);
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
     * @return \Sokil\Mongo\Document|array|null
     */
    public function findOne()
    {
        $mongoDocument = $this->_collection
            ->getMongoCollection()
            ->findOne($this->expression->toArray(), $this->fields);

        if(!$mongoDocument) {
            return null;
        }

        if($this->_resultAsArray) {
            return $mongoDocument;
        }

        return $this->toObject($mongoDocument);
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

        if(!$count) {
            return null;
        }

        if(1 === $count) {
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
     * @deprecated since 1.11.8. Use Cursor::getMongoQuery()
     * @return array expression
     */
    public function toArray()
    {
        return $this->getMongoQuery();
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
        // use native php function if field without subdocument
        if(false === strpos($fieldName, '.') && function_exists('array_column')) {
            if($this->isResultAsArray()) {
                $result = $this->findAll();
            } else {
                $queryBuilder = clone $this;
                $result = $queryBuilder->asArray()->findAll();
                unset($queryBuilder);
            }

            return array_column($result, $fieldName, '_id');
        }

        // if field with subdocument or native php function not exists
        return $this->pluckDotNoteted($fieldName);
    }

    /**
     * Pluck by dot-notated field name
     * 
     * @param string $fieldName field name
     * @return array
     */
    private function pluckDotNoteted($fieldName)
    {
        if($this->isResultAsArray()) {
            $queryBuilder = clone $this;
            $result = $queryBuilder->asObject()->findAll();
            unset($queryBuilder);
        } else {
            $result = $this->findAll();
        }

        $list = array();
        foreach($result as $key => $document) {
            $list[$key] = $document->get($fieldName);
        }

        return $list;
    }

    /**
     * Get document instance and remove it from collection
     *
     * @return \Sokil\Mongo\Document
     */
    public function findAndRemove()
    {
        $mongoDocument = $this->_collection->getMongoCollection()->findAndModify(
            $this->expression->toArray(),
            null,
            $this->fields,
            array(
                'remove'    => true,
                'sort'      => $this->sort,
            )
        );

        if(!$mongoDocument) {
            return null;
        }

        return $this->toObject($mongoDocument);
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
        $mongoDocument = $this->_collection
            ->getMongoCollection()
            ->findAndModify(
                $this->expression->toArray(),
                $operator ? $operator->getAll() : null,
                $this->fields,
                array(
                    'new'       => $returnUpdated,
                    'sort'      => $this->sort,
                    'upsert'    => $upsert,
                )
            );

        if(!$mongoDocument) {
            return null;
        }

        return $this->toObject($mongoDocument);
    }

    public function map($handler)
    {
        $result = array();

        foreach($this as $id => $document) {
            $result[$id] = $handler($document);
        }

        return $result;
    }

    public function filter($handler)
    {
        $result = array();

        foreach($this as $id => $document) {
            if(!$handler($document)) {
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
     * @return \Sokil\Mongo\Paginator
     */
    public function paginate($page, $itemsOnPage = 30)
    {
        $paginator = new Paginator($this);

        return $paginator
            ->setCurrentPage($page)
            ->setItemsOnPage($itemsOnPage);

    }

    /**
     * Convert find result to object
     *
     * @param array $mongoFindResult array of key-values, received from mongo driver
     * @return \Sokil\Mongo\Document
     */
    abstract protected function toObject($mongoFindResult);

    public function current()
    {
        $mongoDocument = $this->getCursor()->current();
        if(!$mongoDocument) {
            return null;
        }

        if($this->_resultAsArray) {
            return $mongoDocument;
        }

        return $this->toObject($mongoDocument);
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

    public function getReadPreference()
    {
        if($this->cursor) {
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
     * @param type $targetCollectionName
     * @param type $targetDatabaseName Target database name. If not specified - use current
     */
    public function copyToCollection($targetCollectionName, $targetDatabaseName = null)
    {
        // target database
        if(!$targetDatabaseName) {
            $database = $this->_collection->getDatabase();
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
        while($inProgress) {
            // get next pack of documents
            $documentList = array();
            for($i = 0; $i < $batchLimit; $i++) {
                if(!$cursor->valid()) {
                    $inProgress = false;

                    if($documentList) {
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

            // check result
            if(is_array($result)) {
                if($result['ok'] != 1) {
                    throw new Exception('Batch insert error: ' . $result['err']);
                }
            } elseif(!$result) {
                throw new Exception('Batch insert error');
            }
        }

        return $this;
    }

    /**
     * Move selected documents to another collection.
     * Dociuments will be removed from source collection only after
     * copying them to target collection.
     *
     * @param type $targetCollectionName
     * @param type $targetDatabaseName Target database name. If not specified - use current
     */
    public function moveToCollection($targetCollectionName, $targetDatabaseName = null)
    {
        // copy to target
        $this->copyToCollection($targetCollectionName, $targetDatabaseName);

        // remove from source
        $this->_collection->deleteDocuments($this->expression);
    }

    /**
     * Used to get hash that uniquely identifies current query
     */
    public function getHash()
    {
        $hash = array();

        // expression
        $hash[] = json_encode($this->expression->toArray());

        // sorts
        if($this->sort) {
            $sort = $this->sort;
            ksort($sort);
            $hash[] = implode('', array_merge(array_keys($sort), array_values($sort)));
        }

        // fields
        if($this->fields) {
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
        return array_map(function($element) {
            // MongoId
            if($element instanceof \MongoId) {
                return $element;
            }

            // \Sokil\Mongo\Document
            if($element instanceof Document) {
                return $element->getId();
            }

            // array with id key
            if(is_array($element)) {
                if(!isset($element['_id'])) {
                    throw new \InvalidArgumentException('Array must have _id key');
                }
                return $element['_id'];
            }

            // string
            if(is_string($element)) {
                try {
                    return new \MongoId($element);
                } catch (\MongoException $e) {
                    return $element;
                }
            }

            // int
            if(is_int($element)) {
                return $element;
            }

            throw new \InvalidArgumentException('Must be \MongoId, \Sokil\Mongo\Document, array with _id key, string or integer');

        }, array_values($list));
    }
}
