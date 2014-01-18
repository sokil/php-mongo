<?php

namespace Sokil\Mongo;

class Collection
{
    protected $_queryBuliderClass = '\Sokil\Mongo\QueryBuilder';
    
    protected $_queryExpressionClass = '\Sokil\Mongo\Expression';
    
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
    
    public function delete() {
        $status = $this->_collection->drop();
        if($status['ok'] != 1) {
            // check if collection exists
            if('ns not found' !== $status['errmsg']) {
                // collection exist
                throw new Exception('Error deleting collection ' . $this->_collection->getName());
            }
        }
        
        return $this;
    }
    
    /**
     * Override to define classname of document by document data
     * 
     * @param array $documentData
     * @return string Document class data
     */
    public function getDocumentClassName(array $documentData = null)
    {
        return '\Sokil\Mongo\Document';
    }
    
    /**
     * 
     * @param array $data
     * @return \Sokil\Mongo\Document
     */
    public function createDocument(array $data = null)
    {
        $className = $this->getDocumentClassName($data);
        
        return new $className($data);
    }
    
    /**
     * Create document query builder
     * 
     * @return \Sokil\Mongo\Search
     */
    public function find()
    {
        return new $this->_queryBuliderClass($this, array(
            'expressionClass'   => $this->_queryExpressionClass,
        ));
    }
    
    public function expression()
    {        
        return new $this->_queryExpressionClass;
    }
    
    /**
     * Create document query builder
     * 
     * @return \Sokil\Mongo\Search
     */
    public function findAsArray()
    {
        return new $this->_queryBuliderClass($this, array(
            'expressionClass'   => $this->_queryExpressionClass,
            'arrayResult' => true
        ));
    }
    
    /**
     * Get document by id
     * 
     * @param string|MongoId $id
     * @return \Sokil\Mongo\_docClass|null
     */
    public function getDocument($id)
    {
        return $this->find()->byId($id)->findOne();
    }
    
    /**
     * Get document by id
     * 
     * @param string|MongoId $id
     * @return \Sokil\Mongo\_docClass|null
     */
    public function getDocuments(array $idList)
    {
        return $this->find()->byIdList($idList)->findAll();
    }
    
    /**
     * 
     * @param \Sokil\Mongo\Document $document
     * @return \Sokil\Mongo\Collection
     * @throws \Sokil\Mongo\Exception
     * @throws \Sokil\Mongo\Document\Exception\Validate
     */
    public function saveDocument(Document $document, $validate = true)
    {
        if($validate) {
            $document->validate();
        }
        
        $data = $document->toArray();
        
        // handle beforeSave event
        $document->triggerEvent('beforeSave');
        
        // update
        if($document->getId()) {
            
            $document->triggerEvent('beforeUpdate');
            
            if($document->hasUpdateOperations()) {
                
                $updateOperations = $document->getUpdateOperations();
                
                $status = $this->_collection->update(
                    array('_id' => $document->getId()),
                    $updateOperations
                );
                
                if($status['ok'] != 1) {
                    throw new Exception($status['err']);
                }
                
                $document->resetUpdateOperations();
                
                // get updated data if some field incremented
                if(isset($updateOperations['$inc'])) {
                    $data = $this->_collection->findOne(array('_id' => $document->getId()));
                    $document->fromArray($data);
                }
            }
            else {
                $status = $this->_collection->save($document->toArray());
                if($status['ok'] != 1) {
                    throw new Exception($status['err']);
                }
            }

            $document->triggerEvent('afterUpdate');
        }
        // insert
        else {
            
            $document->triggerEvent('beforeInsert');
            
            // save data
            $status = $this->_collection->save($data);
            if($status['ok'] != 1) {
                throw new Exception($status['err']);
            }
            
            $document->triggerEvent('afterInsert');
        }
        
        // handle afterSave event
        $document->triggerEvent('afterSave');
        
        // set id
        $document->setId($data['_id']);
        
        return $this;
    }
    
    public function deleteDocument(Document $document)
    {        
        $document->triggerEvent('beforeDelete');
        
        $status = $this->_collection->remove(array(
            '_id'   => $document->getId()
        ));
        
        $document->triggerEvent('afterDelete');
        
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
    
    public function readPrimaryOnly()
    {
        $this->_collection->setReadPreference(\MongoClient::RP_PRIMARY);
        return $this;
    }
    
    public function readPrimaryPreferred(array $tags = null)
    {
        $this->_collection->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readSecondaryOnly(array $tags = null)
    {
        $this->_collection->setReadPreference(\MongoClient::RP_SECONDARY, $tags);
        return $this;
    }
    
    public function readSecondaryPreferred(array $tags = null)
    {
        $this->_collection->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readNearest(array $tags = null)
    {
        $this->_collection->setReadPreference(\MongoClient::RP_NEAREST, $tags);
        return $this;
    }
}
