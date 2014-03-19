<?php

namespace Sokil\Mongo;

class Collection
{
    protected $_queryBuliderClass = '\Sokil\Mongo\QueryBuilder';
    
    protected $_queryExpressionClass = '\Sokil\Mongo\Expression';
    
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private $_database;
    
    private $_collectionName;
    
    /**
     *
     * @var \MongoCollection
     */
    private $_mongoCollection;
    
    private $_documentsPool = array();
    
    public function __construct(Database $database, $collectionName)
    {
        $this->_database = $database;
        $this->_collectionName = $collectionName;
        
        $this->_mongoCollection = $database->getMongoDB()->selectCollection($collectionName);
    }
    
    /**
     * 
     * @return MongoCollection
     */
    public function getMongoCollection()
    {
        return $this->_mongoCollection;
    }
    
    /**
     * 
     * @return \Sokil\Mongo\Database
     */
    public function getDatabase()
    {
        return $this->_database;
    }
    
    public function delete() {
        $status = $this->_mongoCollection->drop();
        if($status['ok'] != 1) {
            // check if collection exists
            if('ns not found' !== $status['errmsg']) {
                // collection exist
                throw new Exception('Error deleting collection ' . $this->_mongoCollection->getName());
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
        
        return new $className($this, $data);
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
    
    /**
     * Retrieve a list of distinct values for the given key across a collection.
     * 
     * @param string $selector field selector
     * @param \Sokil\Mongo\Expression $expression expression to search documents
     * @return array distinct values
     */
    public function getDistinct($selector, Expression $expression = null)
    {
        if($expression) {
            $expression = $expression->toArray();
        }
        
        return $this->_mongoCollection->distinct($selector, $expression);
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
                
                $status = $this->_mongoCollection->update(
                    array('_id' => $document->getId()),
                    $updateOperations
                );
                
                if($status['ok'] != 1) {
                    throw new Exception('Update error: ' . $status['err']);
                }
                
                if($document->getOperator()->isReloadRequired()) {
                    $data = $this->_mongoCollection->findOne(array('_id' => $document->getId()));
                    $document->fromArray($data);
                }
                
                $document->getOperator()->reset();
            }
            else {
                $status = $this->_mongoCollection->update(
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
            $status = $this->_mongoCollection->insert($data);
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
        
        $status = $this->_mongoCollection->remove(array(
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
    
    public function insertMultiple($rows)
    {
        $document = $this->createDocument();
        
        foreach($rows as $row) {
            $document->fromArray($row);
            
            if(!$document->isValid()) {
                throw new Exception('Document invalid');
            }
            
            $document->reset();
        }
        
        $result = $this->_mongoCollection->batchInsert($rows);
        if(!$result || $result['ok'] != 1) {
            throw new Exception('Batch insert error: ' . $result['err']);
        }
        
        return $this;
    }
    
    public function updateMultiple(Expression $expression, $updateData)
    {
        if($updateData instanceof Operator) {
            $updateData = $updateData->getAll();
        }
        
        $status = $this->_mongoCollection->update(
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
        return new AggregatePipelines($this);
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
        
        $status = $this->_mongoCollection->aggregate($pipelines);
        
        if($status['ok'] != 1) {
            throw new Exception($status['errmsg']);
        }
        
        return $status['result'];
    }
    
    public function validate($full = false)
    {
        $response = $this->_mongoCollection->validate($full);
        if(!$response || $response['ok'] != 1) {
            throw new Exception($response['errmsg']);
        }
        
        return $response;
    }
    
    public function readPrimaryOnly()
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_PRIMARY);
        return $this;
    }
    
    public function readPrimaryPreferred(array $tags = null)
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readSecondaryOnly(array $tags = null)
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_SECONDARY, $tags);
        return $this;
    }
    
    public function readSecondaryPreferred(array $tags = null)
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED, $tags);
        return $this;
    }
    
    public function readNearest(array $tags = null)
    {
        $this->_mongoCollection->setReadPreference(\MongoClient::RP_NEAREST, $tags);
        return $this;
    }
}
