<?php

namespace Core\Mongo;

class Search implements \Iterator, \Countable, \Core\Db\SearchInterface
{
    /**
     *
     * @var Core_Mongo_Collection
     */
    private $_collection;
    
    private $_fields = array();
    
    private $_cursor;
    
    private $_query = array();
    
    private $_skip = 0;
    
    private $_limit = 0;
    
    private $_sort = array();
    
    public function __construct(Collection $collection)
    {
        $this->_collection = $collection;
    }
    
    public function fields(array $fields)
    {
        $this->_fileds = $fields;
    }
    
    public function where($field, $condition)
    {
        $this->_query[$field] = $condition;
        
        return $this;
    }
    
    public function whereEmpty($field)
    {
        return $this->where('$or', array(
            array($field => null),
            array($field => ''),
        ));
    }
    
    public function skip($skip)
    {
        $this->_skip = (int) $skip;
        
        return $this;
    }
    
    public function limit($limit, $offset = null)
    {
        $this->_limit = (int) $limit;
        
        if(null !== $offset)
            $this->skip($offset);
        
        return $this;
    }
    
    public function sort(array $sort)
    {
        $this->_sort = $sort;
        
        return $this;
    }
    
    /**
     * 
     * @return MongoCursor
     */
    private function getCursor()
    {
        if($this->_cursor)
            return $this->_cursor;
        
        $this->_cursor = $this->_collection
            ->getNativeCollection()
            ->find($this->_query, $this->_fields);
        
        
        if($this->_skip)
            $this->_cursor->skip($this->_skip);
        
        if($this->_limit)
            $this->_cursor->limit($this->_limit);
        
        if($this->_sort)
            $this->_cursor->sort($this->_sort);
        
        return $this->_cursor;
    }
    
    public function findOne()
    {
        $documentData = $this->_collection->getNativeCollection()->findOne($this->_query, $this->_fields);
        
        $className = $this->_collection->getDocumentClassName();
        
        return new $className($this->_collection, $documentData);
    }
    
    public function fetchCount()
    {
        return (int) $this->_collection
            ->getNativeCollection()
            ->count($this->_query, $this->_limit, $this->_skip);
    }
    
    public function current()
    {
        $documentData = $this->getCursor()->current();
        
        $className = $this->_collection->getDocumentClassName();
        
        return new $className($this->_collection, $documentData);
    }
    
    public function count()
    {
        return $this->fetchCount();
    }
    
    public function key()
    {
        return $this->getCursor()->key();
    }
    
    public function next()
    {
        return $this->getCursor()->next();        
    }
    
    public function rewind()
    {
        return $this->getCursor()->rewind();
    }
    
    public function valid()
    {
        return $this->getCursor()->valid();
    }
    
    public function fetchAll()
    {
        return $this;
    }
    
    public function paginate($currentPage, $itemsPerPage = 50)
    {
        // store pager configuration
        $pager = new \Core\Db\Search\Paginator;
        $pager
                ->setCurrentPage($currentPage)
                ->setItemsPerPage($itemsPerPage)
                ->setSearch($this);

        return $pager;
    }
}