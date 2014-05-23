<?php

namespace Sokil\Mongo;

class Paginator implements \Iterator
{
    private $_currentPage = 1;
    
    private $_itemsOnPage = 30;
    
    private $_totalRowsCount;
    
    /**
     *
     * @var \Sokil\Mongo\QueryBuilder
     */
    private $_queryBuilder;
    
    public function __construct(QueryBuilder $queryBuilder = null)
    {
        if($queryBuilder) {
            $this->setQueryBuilder($queryBuilder);
        }
    }
    
    public function __destruct()
    {
        $this->_queryBuilder = null;
    }
    
    /**
     * 
     * @param int $itemsOnPage
     * @return \Sokil\Mongo\Paginator
     */
    public function setItemsOnPage($itemsOnPage)
    {
        $this->_itemsOnPage = (int) $itemsOnPage;
        
        $this->_queryBuilder->limit($this->_itemsOnPage);
        
        // define offset
        $this->_applyLimits();
        
        return $this;
    }
    
    /**
     * 
     * @param int $currentPage
     * @return \Sokil\Mongo\Paginator
     */
    public function setCurrentPage($currentPage)
    {        
        $this->_currentPage = (int) $currentPage;
        
        // define offset
        $this->_applyLimits();
        
        return $this;
    }
    
    public function getCurrentPage()
    {
        return $this->_currentPage;
    }
    
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->_queryBuilder = clone $queryBuilder;
        
        $this->_applyLimits();
        
        $this->_totalRowsCount = null;
        
        return $this;
    }
    
    public function getTotalRowsCount()
    {
        if($this->_totalRowsCount) {
            return $this->_totalRowsCount;
        }
        
        $this->_totalRowsCount = $this->_queryBuilder->count();
        
        return $this->_totalRowsCount;
    }
    
    public function getTotalPagesCount()
    {
        return (int) ceil($this->getTotalRowsCount() / $this->_itemsOnPage);
    }
    
    private function _applyLimits()
    {
        if(!$this->_queryBuilder) {
            return;
        }
        
        // check if current page number greater than max allowed
        $totalPageCount = $this->getTotalPagesCount();
        
        if($this->_currentPage <= $totalPageCount) {
            $currentPage = $this->_currentPage;
        } else {
            $currentPage = $totalPageCount;
        }
        
        // get page of rows
        $this->_queryBuilder
            ->limit($this->_itemsOnPage)
            ->skip(($currentPage - 1) * $this->_itemsOnPage);
    }
    
    /**
     * @return \Sokil\Mongo\Document
     */
    public function current()
    {
        return $this->_queryBuilder->current();
    }
    
    public function key()
    {
        return $this->_queryBuilder->key();
    }
    
    public function next()
    {
        $this->_queryBuilder->next();
        return $this;
    }
    
    public function rewind()
    {
        $this->_queryBuilder->rewind();
        return $this;
    }
    
    public function valid()
    {
        return $this->_queryBuilder->valid();
    }
}