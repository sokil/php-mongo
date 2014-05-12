<?php

namespace Sokil\Mongo;

class Collection implements \Countable
{
    protected $_queryBuliderClass = '\Sokil\Mongo\QueryBuilder';
    
    protected $_queryExpressionClass = '\Sokil\Mongo\Expression';
    
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private $_database;
    
    /**
     *
     * @var \MongoCollection
     */
    private $_mongoCollection;
    
    private $_documentsPool = array();
    
    protected $_documentPoolEnabled = false;
    
    public function __construct(Database $database, $collection)
    {
        $this->_database = $database;
        
        if($collection instanceof \MongoCollection) {
            $this->_mongoCollection = $collection;
        } else {
            $this->_mongoCollection = $database->getMongoDB()->selectCollection($collection);
        }
        
    }
    
    public function __get($name)
    {
        return $this->getDocument($name);
    }
    
    /**
     * Get name of collection
     * @return string name of collection
     */
    public function getName()
    {
        return $this->_mongoCollection->getName();
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
                throw new Exception('Error deleting collection ' . $this->getName());
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
    
    public function count()
    {
        return $this->find()->count();
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
    
    /**
     * 
     * @return \Sokil\Mongo\Expression
     */
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
    
    public function disableDocumentPool()
    {
        $this->_documentPoolEnabled = false;
        return $this;
    }
    
    public function enableDocumentPool()
    {
        $this->_documentPoolEnabled = true;
        return $this;
    }
    
    /**
     * Get document by id
     * 
     * @param string|MongoId $id
     * @return \Sokil\Mongo\Document|null
     */
    public function getDocument($id)
    {
        if(!$this->_documentPoolEnabled) {
            return $this->getDocumentDirectly($id);
        }
        
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
        
        if($this->_documentPoolEnabled) {
            $this->_documentsPool = array_merge(
                $this->_documentsPool,
                $documents
            );
        }
        
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
        // if document already in db and not modified - skip this method
        if(!$document->isSaveRequired()) {
            return $this;
        }
        
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
            if($this->_documentPoolEnabled) {
                $this->_documentsPool[(string) $data['_id']] = $document;
            }
            
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
        
        // log
        $client = $this->_database->getClient();
        if($client->hasLogger()) {
            $client->getLogger()->debug(get_called_class() . ': ' . json_encode($pipelines));
        }
        
        // aggregate
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
    
    public function ensureIndex($key, array $options = array())
    {
        $this->_mongoCollection->ensureIndex($key, $options);
        return $this;
    }
    
    public function ensureUniqueIndex($key, $dropDups = false)
    {
        $this->_mongoCollection->ensureIndex($key, array(
            'unique'    => true,
            'dropDups'  => (bool) $dropDups,
        ));
        
        return $this;
    }
    
    public function ensureSparseIndex($key)
    {
        $this->_mongoCollection->ensureIndex($key, array(
            'sparse'    => true,
        ));
        
        return $this;
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
    
    /**
     * @param string|integer $w write concern
     * @param int $timeout timeout in miliseconds
     */
    public function setWriteConcern($w, $timeout = 10000)
    {
        if(!$this->_mongoCollection->setWriteConcern($w, (int) $timeout)) {
            throw new Exception('Error setting write concern');
        }
        
        return $this;
    }
    
    /**
     * @param int $timeout timeout in miliseconds
     */
    public function setUnacknowledgedWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern(0, (int) $timeout);
        return $this;
    }
    
    /**
     * @param int $timeout timeout in miliseconds
     */
    public function setMajorityWriteConcern($timeout = 10000)
    {
        $this->setWriteConcern('majority', (int) $timeout);
        return $this;
    }
    
    public function getWriteConcern()
    {
        return $this->_mongoCollection->getWriteConcern();
    }
    
    public function stats()
    {
        return $this->getDatabase()->executeCommand(array(
            'collstats' => $this->getName(),
        ));
    }
}
