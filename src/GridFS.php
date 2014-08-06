<?php

namespace Sokil\Mongo;

class GridFS extends Collection
{
    protected $_queryBuliderClass = '\Sokil\Mongo\GridFSQueryBuilder';
    
    public function __construct(Database $database, $prefix = 'fs')
    {
        $this->_database = $database;

        $this->_mongoCollection = new \MongoGridFS($database->getMongoDB(), $prefix);
    }
    
    /**
     * Create file in GridFS from file in filesystem
     * 
     * @param string $filename name of source file
     * @param array $metadata metadata stored with file
     * @return \MongoId Id of stored file
     */
    public function storeFile($filename, $metadata = array())
    {
        return $this->_mongoCollection->storeFile($filename, $metadata);
    }
    
    /**
     * Create file in GridFS from binary data
     * 
     * @param string $bytes binary data to store in GridFS
     * @param array $metadata metadata stored with file
     * @return \MongoId Id of stored file
     */
    public function storeBytes($bytes, $metadata = array())
    {
        return $this->_mongoCollection->storeBytes($bytes, $metadata);
    }
    
    public function getFileById($id)
    {
        if($id instanceof \MongoId) {
            $file = $this->_mongoCollection->get($id);
        } else {
            try {
                $file = $this->_mongoCollection->get(new \MongoId($id));
            } catch (\MongoException $e) {
                $file = $this->_mongoCollection->get($id);
            }
        }
        
        if(!$file) {
            return null;
        }
        
        return new GridFSFile($this, $file);
    }
    
    /**
     * Delete file by id
     * 
     * @param string|\MongoId $id id of file's document
     * @return \Sokil\Mongo\GridFS
     * @throws Exception
     */
    public function deleteFileById($id)
    {
        $result = $this->_mongoCollection->delete($id);
        if($result['ok'] !== (double) 1) {
            throw new Exception('Error deleting file');
        }
        
        return $this;
    }
}