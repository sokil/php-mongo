<?php

namespace Sokil\Mongo;

class Search implements \Iterator, \Countable
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
    
    private $_query = array();
    
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
    
    public function fields(array $fields)
    {
        $this->_fields = $fields;
    }
    
    public function where($field, $value)
    {
        if(!isset($this->_query[$field]) || !is_array($value) || !is_array($this->_query[$field])) {
            $this->_query[$field] = $value;
        }
        else {
            $this->_query[$field] = array_merge($this->_query[$field], $value);
        }
        
        return $this;
    }
    
    public function whereNot($field, $value)
    {
        return $this->where($field, array('$ne' => $value));
    }
    
    public function whereEmpty($field)
    {
        return $this->where('$or', array(
            array($field => null),
            array($field => ''),
        ));
    }
    
    public function whereIn($field, array $values)
    {
        return $this->where($field, array('$in' => $values));
    }
    
    public function whereNotIn($field, array $values)
    {
        return $this->where($field, array('$nin' => $values));
    }
    
    public function whereLike($field, $regex, $caseInsensitive = true)
    {
        // regex
        $query = array(
            '$regex'    => $regex,
        );
        
        // options
        $options = '';
        
        if($caseInsensitive) {
            $options .= 'i';
        }
        
        $query['$options'] = $options;
        
        // query
        return $this->where($field, $query);
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
