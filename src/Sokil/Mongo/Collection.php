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
     * @return \Core\Mongo\Document
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
        
        return new $this->_docClass($this, $data);
    }
    
    public function save(Document $document)
    {
        $this->_collection->save($document->toArray());
        
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
