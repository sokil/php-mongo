<?php

namespace Sokil\Mongo;

class QueryBuilder implements \Iterator, \Countable
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
    private $_collection;
    
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
    
    private $_readPreferences = array();
    
    /**
     *
     * @var If specified in child class - overload config from collection class
     */
    protected $_queryExpressionClass;
    
    /**
     *
     * @var boolean results are arrays instead of objects
     */
    private $_options = array(
        'arrayResult'       => false,
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
     * @return \\Sokil\Mongo\QueryBuilder
     */
    public function fields(array $fields)
    {
        $this->_fields = array_fill_keys($fields, 1);
        return $this;
    }
    
    /**
     * Return all fields except specified
     * @param array $fields
     */
    public function skipFields(array $fields)
    {
        $this->_fields = array_fill_keys($fields, 0);
        return $this;
    }
    
    /**
     * Append field to accept list
     * @param type $field
     * @return \\Sokil\Mongo\QueryBuilder
     */
    public function field($field)
    {
        $this->_fields[$field] = 1;
        return $this;
    }
    
    /**
     * Append field to skip list
     * 
     * @param type $field
     * @return \\Sokil\Mongo\QueryBuilder
     */
    public function skipField($field)
    {
        $this->_fields[$field] = 0;
        return $this;
    }
    
    public function slice($field, $limit, $skip = null)
    {
        $limit  = (int) $limit;
        $skip   = (int) $skip;
        
        if($skip) {
            if(!$limit) {
                throw new Exception('Limit must be specified');
            }
            
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
    
    public function getExpression()
    {
        return $this->_expression;
    }
    
    /**
     * get list of MongoId objects from array of strings, MongoId's and Document's
     * 
     * @param array $list
     * @return type
     */
    public function getIdList(array $list)
    {
        return array_map(function($element) {
            if($element instanceof \MongoId) {
                return $element;
            }
            
            if($element instanceof Document) {
                return $element->getId();
            }
            
            return new \MongoId($element);
        }, $list);
    }
    
    public function byIdList(array $idList)
    {
        $this->_expression->whereIn('_id', $this->getIdList($idList));
        return $this;
    }
    
    public function byId($id)
    {
        if(!($id instanceof \MongoId)) {
            $id = new \MongoId($id);
        }
        
        $this->_expression->where('_id', $id);
        
        return $this;
    }
    
    public function skip($skip)
    {
        $this->_skip = (int) $skip;
        
        return $this;
    }
    
    public function limit($limit, $offset = null)
    {
        $this->_limit = (int) $limit;
        
        if(null !== $offset) {
            $this->skip($offset);
        }
        
        return $this;
    }
    
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
        if($this->_readPreferences) {
            foreach($this->_readPreferences as $readPreference => $tags) {
                $this->_cursor->setReadPreference($readPreference, $tags);
            }
        }
        
        return $this->_cursor;
    }
    
    public function count()
    {
        return (int) $this->_collection
            ->getMongoCollection()
            ->count($this->_expression->toArray(), $this->_limit, $this->_skip);
    }
    
    public function findOne()
    {
        $documentData = $this->_collection
            ->getMongoCollection()
            ->findOne($this->_expression->toArray(), $this->_fields);
        
        if(!$documentData) {
            return null;
        }
        
        if($this->_options['arrayResult']) {
            return $documentData;
        }
        
        $className = $this->_collection
            ->getDocumentClassName($documentData);
        
        return new $className($this->_collection, $documentData);
    }
    
    /**
     * 
     * @return array result of searching
     */
    public function findAll()
    {
        return iterator_to_array($this);
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
    
    public function toArray()
    {
        return $this->_expression->toArray();
    }
    
    public function current()
    {
        $documentData = $this->getCursor()->current();
        if(!$documentData) {
            return null;
        }
        
        if($this->_options['arrayResult']) {
            return $documentData;
        }
        
        $className = $this->_collection->getDocumentClassName($documentData);
        return new $className($this->_collection, $documentData);
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
        $this->_readPreferences[\MongoClient::RP_PRIMARY] = null;
        return $this;
    }
    
    public function readPrimaryPreferred(array $tags = null)
    {
        $this->_readPreferences[\MongoClient::RP_PRIMARY_PREFERRED] = $tags;
        return $this;
    }
    
    public function readSecondaryOnly(array $tags = null)
    {
        $this->_readPreferences[\MongoClient::RP_SECONDARY] = $tags;
        return $this;
    }
    
    public function readSecondaryPreferred(array $tags = null)
    {
        $this->_readPreferences[\MongoClient::RP_SECONDARY_PREFERRED] = $tags;
        return $this;
    }
    
    public function readNearest(array $tags = null)
    {
        $this->_readPreferences[\MongoClient::RP_NEAREST] = $tags;
        return $this;
    }
}
