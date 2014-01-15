<?php

namespace Sokil\Mongo;

class Search extends Query implements \Iterator, \Countable
{
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
    
    private $_limit = 0;
    
    private $_sort = array();
    
    private $_readPreferences = array();
    
    /**
     *
     * @var boolean results are arrays instead of objects
     */
    private $_options = array(
        'arrayResult'   => false,
    );
    
    public function __construct(Collection $collection, array $options = null)
    {
        $this->_collection = $collection;
        
        if($options) {
            $this->_options = array_merge($this->_options, $options);
        }
    }
    
    /**
     * Return only specified fields
     * 
     * @param array $fields
     * @return \Sokil\Mongo\Search
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
     * @return \Sokil\Mongo\Search
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
     * @return \Sokil\Mongo\Search
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
    
    public function whereOr(Query $query1, Query $query2)
    {
        return $this->where('$or', array($query1->toArray(), $query2->toArray()));
    }
    
    public function whereAnd(Query $query1, Query $query2)
    {
        return $this->where('$and', array($query1->toArray(), $query2->toArray()));
    }
    
    /**
     * Selects the documents that fail all the query expressions in the array
     * @param Query $query Instance of query
     * @param Query $query ...
     */
    public function whereNor()
    {
        return $this->where('$nor', array_map(function(Query $query) {
            return $query->toArray();
        }, func_get_args()));
    }
    
    public function whereNot(Query $query)
    {
        foreach($query->toArray() as $field => $value) {
            // $not acceptable only for operators-expressions
            if(is_array($value) && is_string(key($value))) {
                $this->where($field, array('$not' => $value));
            }
            // for single values use $ne
            else {
                $this->whereNotEqual($field, $value);
            }
        }
        
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
    
    public function byId($id)
    {
        if(!($id instanceof \MongoId)) {
            $id = new \MongoId($id);
        }
        
        return $this->where('_id', $id);
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
        return $this->whereIn('_id', $this->getIdList($idList));
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
            ->getNativeCollection()
            ->find($this->_query, $this->_fields);
        
        
        if($this->_skip) {
            $this->_cursor->skip($this->_skip);
        }
        
        if($this->_limit) {
            $this->_cursor->limit($this->_limit);
        }
        
        if($this->_sort) {
            $this->_cursor->sort($this->_sort);
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
            ->getNativeCollection()
            ->count($this->_query, $this->_limit, $this->_skip);
    }
    
    public function findOne()
    {
        $documentData = $this->_collection
            ->getNativeCollection()
            ->findOne($this->_query, $this->_fields);
        
        if(!$documentData) {
            return null;
        }
        
        if($this->_options['arrayResult']) {
            return $documentData;
        }
        
        $className = $this->_collection
            ->getDocumentClassName($documentData);
        
        return new $className($documentData);
    }
    
    /**
     * 
     * @return array result of searching
     */
    public function findAll()
    {
        return iterator_to_array($this);
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
        return new $className($documentData);
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
