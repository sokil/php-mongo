<?php

namespace Sokil\Mongo;

use Sokil\Mongo\Collection;

class AggregatePipelines
{
    private $_pipelines = array();
    
    /**
     * @var \Sokil\Mongo\Collection
     */
    private $_collection;
    
    public function __construct(Collection $collection)
    {
        $this->_collection = $collection;
    }
    
    private function _add($operator, $pipeline) {
        $lastIndex = count($this->_pipelines) - 1;
        
        if(!$this->_pipelines || !isset($this->_pipelines[$lastIndex][$operator]) || $operator == '$group') {
            $this->_pipelines[] = array($operator => $pipeline);
        }
        else {
            $this->_pipelines[$lastIndex][$operator] = array_merge($this->_pipelines[$lastIndex][$operator], $pipeline);
        }
    }
    
    public function match(array $pipeline) {
        $this->_add('$match', $pipeline);
        return $this;
    }
    
    public function project(array $pipeline) {
        $this->_add('$project', $pipeline);
        return $this;
    }
    
    public function group(array $pipeline) {
        
        if(!isset($pipeline['_id'])) {
            throw new Exception('Group field in _id key must be specified');
        }
        
        $this->_add('$group', $pipeline);
        return $this;
    }
    
    public function sort(array $pipeline) {
        $this->_add('$sort', $pipeline);
        return $this;
    }
    
    public function toArray() {
        return $this->_pipelines;
    }
    
    public function limit($limit) {
        $this->_add('$limit', (int) $limit);
        return $this;
    }
    
    public function skip($skip) {
        $this->_add('$skip', (int) $skip);
        return $this;
    }
    
    public function aggregate()
    {
        return $this->_collection->aggregate($this);
    }
}
