<?php

namespace Sokil\Mongo;

class GridFS
{
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private $_database;
    
    /**
     * Prefix of files and chunks collection
     * @var string
     */
    private $_prefix;
    
    /**
     *
     * @var \MongoGridFS
     */
    private $_mongoGridFS;
    
    public function __construct(Database $database, $prefix = 'fs')
    {
        $this->_database = $database;
        
        $this->_prefix = $prefix;
        
        $this->_mongoGridFS = new \MongoGridFS($database->getMongoDB(), $prefix);
    }
    
    /**
     * 
     * @return \MongoGridFS
     */
    public function getMongoGridFS()
    {
        return $this->_mongoGridFS;
    }
    
    /**
     * Create file in GridFS from file in filesystem
     * 
     * @param string $filename name of source file
     * @param array $metadata metadata stored with file
     * @return \MongoId Id of stored file
     */
    public function createFromFile($filename, $metadata = array())
    {
        return $this->_mongoGridFS->storeFile($filename, $metadata);
    }
    
    /**
     * Create file in GridFS from binary data
     * 
     * @param string $binary binary data to store in GridFS
     * @param array $metadata metadata stored with file
     * @return \MongoId Id of stored file
     */
    public function createFromBinary($binary, $metadata = array())
    {
        return $this->_mongoGridFS->storeBytes($binary, $metadata);
    }
    
    public function get($id)
    {
        if($id instanceof \MongoId) {
            $file = $this->_mongoGridFS->get($id);
        } else {
            try {
                $file = $this->_mongoGridFS->get(new \MongoId($id));
            } catch (\MongoException $e) {
                $file = $this->_mongoGridFS->get($id);
            }
        }
        
        if(!$file) {
            return null;
        }
        
        return new GridFSFile($this, $file);
    }
    
    /**
     * 
     * @return \Sokil\Mongo\Expression
     */
    public function expression()
    {        
        return new Expression;
    }
    
    /**
     * Find list of files in GridFS
     * 
     * @return \MongoGridFSCursor
     */
    public function find(Expression $expression)
    {
        return $this->getMongoGridFS()->find($expression->toArray());
    }
    
    /**
     * Update existed file
     * 
     * @param \Sokil\Mongo\GridFSFile $file instance of File
     */
    public function save(GridFSFile $file)
    {
        $this->getMongoGridFS()->save($file->toArray());
    }
    
    /**
     * Delete file by id
     * 
     * @param string|\MongoId $id id of file's document
     * @return \Sokil\Mongo\GridFS
     * @throws Exception
     */
    public function delete($id)
    {
        $result = $this->getMongoGridFS()->delete($id);
        if($result['ok'] !== (double) 1) {
            throw new Exception('Error deleting file');
        }
        
        return $this;
    }
    
    /**
     * Delete entire grid fs
     * @return \Sokil\Mongo\GridFS
     * @throws \Exception
     */
    public function deleteAll()
    {
        $result = $this->_mongoGridFS->drop();
        if($result['ok'] !== (double) 1) {
            throw new \Exception('Error deleting GridFs');
        }
        
        return $this;
    }
}