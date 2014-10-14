<?php

namespace Sokil\Mongo;

abstract class Cursor implements \Iterator, \Countable
{
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private $_client;
    
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    protected $_collection;
    
    private $_fields = array();
    
    /**
     *
     * @var \MongoCursor
     */
    private $_cursor;
    
    private $_skip = 0;
    
    /**
     *
     * @var \Sokil\Mongo\Expression
     */
    private $_expression;
    
    private $_limit = 0;
    
    private $_sort = array();
    
    private $_readPreference = array();
    
    /**
     *
     * @var string If specified in child class - overload config from collection class
     */
    protected $_queryExpressionClass;
    
    /**
     *
     * @var type Return result as array or as Document instance
     */
    protected $_resultAsArray = false;
    
    /**
     *
     * @var boolean results are arrays instead of objects
     */
    private $_options = array(
        'expressionClass'   => '\Sokil\Mongo\Expression'
    );
    
    public function __construct(Collection $collection, array $options = null)
    {
        $this->_collection = $collection;
        
        $this->_client = $this->_collection->getDatabase()->getClient();
        
        if($options) {
            $this->_options = array_merge($this->_options, $options);
        }
        
        // expression
        $this->_expression = $this->expression();
    }
    
    public function __call($name, $arguments) {
        call_user_func_array(array($this->_expression, $name), $arguments);
        return $this;
    }
    
    /**
     * Return only specified fields
     * 
     * @param array $fields
     * @return \Sokil\Mongo\QueryBuilder
     */
    public function fields(array $fields)
    {
        $this->_fields = array_fill_keys($fields, 1);
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
        $this->_fields = array_fill_keys($fields, 0);
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
        $this->_fields[$field] = 1;
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
        $this->_fields[$field] = 0;
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
            $this->_fields[$field] = array('$slice' => array($skip, $limit));
        }
        else {
            $this->_fields[$field] = array('$slice' => $limit);
        }
        
        return $this;
    }
    
    public function query(Expression $expression)
    {
        $this->_expression->merge($expression);
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
            : $this->_options['expressionClass'];
        
        return new $expressionClass;
    }

    /**
     * Get query builder's expression
     *
     * @return Expression
     */
    public function getExpression()
    {
        return $this->_expression;
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

    /**
     * Filter by list of \MongoId
     *
     * @param array $idList list of ids
     * @return \Sokil\Mongo\Cursor
     */
    public function byIdList(array $idList)
    {
        $this->_expression->whereIn('_id', self::mixedToMongoIdList($idList));
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
            $this->_expression->where('_id', $id);
        } else {
            try {
                $this->_expression->where('_id', new \MongoId($id));
            } catch (\MongoException $e) {
                $this->_expression->where('_id', $id);
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
        $this->_skip = (int) $skip;
        
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
        $this->_limit = (int) $limit;
        
        if(null !== $offset) {
            $this->skip($offset);
        }
        
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
        $this->_sort = $sort;
        
        return $this;
    }
    
    /**
     * 
     * @return \MongoCursor
     */
    private function getCursor()
    {
        if($this->_cursor) {
            return $this->_cursor;
        }
        
        $this->_cursor = $this->_collection
            ->getMongoCollection()
            ->find($this->_expression->toArray(), $this->_fields);
        
        
        if($this->_skip) {
            $this->_cursor->skip($this->_skip);
        }
        
        if($this->_limit) {
            $this->_cursor->limit($this->_limit);
        }
        
        if($this->_sort) {
            $this->_cursor->sort($this->_sort);
        }
        
        // log request
        if($this->_client->hasLogger()) {
            $this->_client->getLogger()->debug(get_called_class() . ': ' . json_encode(array(
                'collection'    => $this->_collection->getName(),
                'query'         => $this->_expression->toArray(),
                'project'       => $this->_fields,
                'sort'          => $this->_sort,
            )));
        }
        
        $this->_cursor->rewind();
        
        // define read preferences
        if($this->_readPreference) {
            $this->_cursor->setReadPreference(
                $this->_readPreference['type'],
                $this->_readPreference['tagsets']
            );
        }
        
        return $this->_cursor;
    }
    
    /**
     * Count documents in result without applying limit and offset
     * @return int count
     */
    public function count()
    {
        return (int) $this->_collection
            ->getMongoCollection()
            ->count($this->_expression->toArray());
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
            ->count($this->_expression->toArray(), $this->_limit, $this->_skip);
    }
    
    public function findOne()
    {
        $mongoDocument = $this->_collection
            ->getMongoCollection()
            ->findOne($this->_expression->toArray(), $this->_fields);
        
        if(!$mongoDocument) {
            return null;
        }
        
        if($this->_resultAsArray) {
            return $mongoDocument;
        }
        
        return $this->toObject($mongoDocument);
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
     * 
     * @return array result of searching
     */
    public function findAll()
    {
        return iterator_to_array($this);
    }
    
    /**
     * Return the values from a single field in the result set of documents
     * 
     * @param type $fieldName
     * @return type
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
        return $this->_pluck($fieldName);
    }
    
    private function _pluck($fieldName)
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
            $this->_expression->toArray(),
            null,
            $this->_fields,
            array(
                'remove'    => true,
                'sort'      => $this->_sort, 
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
                $this->_expression->toArray(),
                $operator ? $operator->getAll() : null,
                $this->_fields,
                array(
                    'new'       => $returnUpdated,
                    'sort'      => $this->_sort,
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
    
    public function toArray()
    {
        return $this->_expression->toArray();
    }
    
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
    
    /**
     * Convert find result to object
     *
     * @param array $mongoFindResult array of key-values, received from mongo driver
     * @return \Sokil\Mongo\Document
     */
    abstract protected function toObject($mongoFindResult);
    
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
        $this->_readPreference = array(
            'type'      => \MongoClient::RP_PRIMARY,
            'tagsets'   => array(),
        );

        return $this;
    }
    
    public function readPrimaryPreferred(array $tags = null)
    {
        $this->_readPreference = array(
            'type'      => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets'   => $tags,
        );

        return $this;
    }
    
    public function readSecondaryOnly(array $tags = null)
    {
        $this->_readPreference = array(
            'type'      => \MongoClient::RP_SECONDARY,
            'tagsets'   => $tags,
        );

        return $this;
    }
    
    public function readSecondaryPreferred(array $tags = null)
    {
        $this->_readPreference = array(
            'type'      => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets'   => $tags,
        );

        return $this;
    }
    
    public function readNearest(array $tags = null)
    {
        $this->_readPreference = array(
            'type'      => \MongoClient::RP_NEAREST,
            'tagsets'   => $tags,
        );

        return $this;
    }

    public function getReadPreference()
    {
        if($this->_cursor) {
            return $this->_cursor->getReadPreference();
        }

        return $this->_readPreference;
    }
}
