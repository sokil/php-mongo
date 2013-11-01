<?php

namespace Sokil\Mongo;

class Collection
{
    protected $_name;
    
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
        $document = new $this->_docClass($data);
        
        return $document;
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
    
    public function delete(Document $document)
    {
        $status = $this->_collection->remove(array(
            '_id'   => new \MongoId($document->getId()),
        ));
        
        if($status['ok'] != 1) {
            throw new Exception($status['err']);
        }
        
        return $this;
    }
}
