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
    
    private $_documentsPool = array();
    
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
     * @return \Sokil\Mongo\QueryBuilder|\Sokil\Mongo\Expression
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
     * 
     * @return \Sokil\Mongo\Operator
     */
    public function operator()
    {
        return new Operator;
    }
    
    /**
     * Create document query builder
     * 
     * @return \Sokil\Mongo\QueryBuilder
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
     * @return \Sokil\Mongo\Document|null
     */
    public function getDocument($id)
    {
        if(!isset($this->_documentsPool[(string) $id])) {
            $this->_documentsPool[(string) $id] = $this->getDocumentDirectly($id);
        }
        
        return $this->_documentsPool[(string) $id];
    }
    
    /**
     * Get document by id directly omiting cache
     * 
     * @param type $id
     * @return \Sokil\Mongo\Document|null
     */
    public function getDocumentDirectly($id)
    {
        return $this->find()->byId($id)->findOne();
    }
    
    /**
     * Get document by id
     * 
     * @param string|MongoId $id
     * @return \Sokil\Mongo\Document|null
     */
    public function getDocuments(array $idList)
    {
        $documents = $this->find()->byIdList($idList)->findAll();
        if(!$documents) {
            return array();
        }
        
        $this->_documentsPool = array_merge(
            $this->_documentsPool,
            $documents
        );
        
        return $documents;
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
        
        // handle beforeSave event
        $document->triggerEvent('beforeSave');
        
        // update
        if($document->isStored()) {
            
            $document->triggerEvent('beforeUpdate');
            
            if($document->getOperator()->isDefined()) {
                
                $updateOperations = $document->getOperator()->getAll();
                
                $status = $this->_collection->update(
                    array('_id' => $document->getId()),
                    $updateOperations
                );
                
                if($status['ok'] != 1) {
                    throw new Exception('Update error: ' . $status['err']);
                }
                
                if($document->getOperator()->isReloadRequired()) {
                    $data = $this->_collection->findOne(array('_id' => $document->getId()));
                    $document->fromArray($data);
                }
                
                $document->getOperator()->reset();
            }
            else {
                $status = $this->_collection->update(
                    array('_id' => $document->getId()),
                    $document->toArray()
                );
                
                if($status['ok'] != 1) {
                    throw new Exception('Update error: ' . $status['err']);
                }
            }

            $document->triggerEvent('afterUpdate');
        }
        // insert
        else {
            
            $document->triggerEvent('beforeInsert');
            
            $data = $document->toArray();
            
            // save data
            $status = $this->_collection->insert($data);
            if($status['ok'] != 1) {
                throw new Exception('Insert error: ' . $status['err']);
            }

            // set id
            $document->defineId($data['_id']);
            
            // store to document's bool 
            $this->_documentsPool[(string) $data['_id']] = $document;
            
            // event
            $document->triggerEvent('afterInsert');
        }
        
        // handle afterSave event
        $document->triggerEvent('afterSave');
        
        // set document as not modified
        $document->setNotModified();
        
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
            throw new Exception('Delete error: ' . $status['err']);
        }
        
        // drop from document's pool
        unset($this->_documentsPool[(string) $document->getId()]);
        
        return $this;
    }
    
    public function updateMultiple(Expression $expression, $updateData)
    {
        if($updateData instanceof Operator) {
            $updateData = $updateData->getAll();
        }
        
        $status = $this->_collection->update(
            $expression->toArray(), 
            $updateData,
            array(
                'multiple'  => true,
            )
        );
        
        if(1 != $status['ok']) {
            throw new Exception('Multiple update error: ' . $status['err']);
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
