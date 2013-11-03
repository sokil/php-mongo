<?php

namespace Sokil\Mongo;

class Collection
{    
    protected $_docClass = '\Sokil\Mongo\Document';
    
    protected $_searchClass = '\Sokil\Mongo\Search';
    
    /**
     *
     * @var \MongoCollection
     */
    private $_collection;
    
    public function __construct(\MongoCollection $collection)
    {
        $this->_collection = $collection;
    }
    
    /**
     * 
     * @return MongoCollection
     */
    public function getNativeCollection()
    {
        return $this->_collection;
    }
    
    public function getDocumentClassName()
    {
        return $this->_docClass;
    }
    
    /**
     * 
     * @param array $data
     * @return \Sokil\Mongo\Document
     */
    public function create(array $data = null)
    {
        return new $this->_docClass($data);
    }
    
        /**
     * 
     * @return \Sokil\Mongo\Search
     */
    public function find()
    {
        return new $this->_searchClass($this);
    }
    
    public function findById($id)
    {
        if(!($id instanceof \MongoId)) {
            $id = new \MongoId($id);
        }
        
        $data = $this->_collection->findOne(array(
            '_id'   => $id
        ));
        
        if(!$data) {
            return null;
        }
        
        return new $this->_docClass($data);
    }
    
    public function save(Document $document)
    {
        $data = $document->toArray();
        
        $status = $this->_collection->save($data);
        if($status['ok'] != 1) {
            throw new Exception($status['err']);
        }
        
        $document->setId($data['_id']);
        
        return $this;
    }
    
    public function delete($document)
    {
        if($document instanceof Document) {
            $document = $document->getId();
            
            if(!$document) {
                throw new Exception('Document not saved');
            }
        }
        
        elseif(!($document instanceof \MongoId)) {
            $document = new \MongoId($document->getId());
        }
        
        $status = $this->_collection->remove(array(
            '_id'   => $document
        ));
        
        if($status['ok'] != 1) {
            throw new Exception($status['err']);
        }
        
        return $this;
    }
    
    /**
     * Create Aggregator pipelines instance
     * 
     * @return \Sokil\Mongo\AggregatePipelines
     */
    public function createPipeline() {
        return new AggregatePipelines;
    }
    
    /**
     * Aggregate using pipelines
     * 
     * @param type $pipelines
     * @return array result of aggregation
     * @throws Exception
     */
    public function aggregate($pipelines) {
        
        if($pipelines instanceof AggregatePipelines) {
            $pipelines = $pipelines->toArray();
        }
        elseif(!is_array($pipelines)) {
            throw new Exception('Wrong pipelines specified');
        }
        
        $status = $this->_collection->aggregate($pipelines);
        
        if($status['ok'] != 1) {
            throw new Exception($status['errmsg']);
        }
        
        return $status['result'];
    }
}
