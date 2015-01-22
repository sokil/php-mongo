<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

/**
 * Representation of GridFS as collection of files
 *
 * @method \MongoGridFS getMongoCollection() Get native mongo GridFS
 * @property \MongoGridFS $_mongoCollection MongoGridFS Instance
 */
class GridFS extends Collection
{
    protected $_queryBuilderClass = '\Sokil\Mongo\GridFSQueryBuilder';
    
    public function __construct(Database $database, $collection = 'fs')
    {
        $this->_database = $database;

        if($collection instanceof \MongoGridFS) {
            $this->_mongoCollection = $collection;
        } else {
           $this->_mongoCollection = new \MongoGridFS($database->getMongoDB(), $collection);
        }
    }
    
    /**
     * Factory method to get document object from array of stored document 
     * @param \MongoGridFSFile $data
     * @return \Sokil\Mongo\GridFsFile
     */
    public function getStoredGridFsFileInstanceFromMongoGridFSFile(\MongoGridFSFile $data, $useDocumentPool = true)
    {        
        $className = $this->getFileClassName($data);
        
        return new $className($this, $data);
    }
    
    /**
     * Override to define class name of file by file data
     * 
     * @param \MongoGridFSFile $fileData
     * @return string Document class data
     */
    public function getFileClassName(\MongoGridFSFile $fileData = null)
    {
        return '\Sokil\Mongo\GridFSFile';
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

    /**
     * Get file instance by id of document
     * Used \MongoGridFS::findOne() instead of \MongoGridFS::get() due to backward compatibility with old mongo extensions
     *
     * @param \MongoId|string|int $id
     * @return \Sokil\Mongo\GridFSFile|null
     */
    public function getFileById($id)
    {
        if($id instanceof \MongoId) {
            $file = $this->_mongoCollection->findOne(array('_id' => $id));
        } else {
            try {
                $file = $this->_mongoCollection->findOne(array('_id' => new \MongoId($id)));
            } catch (\MongoException $e) {
                $file = $this->_mongoCollection->findOne(array('_id' => $id));
            }
        }
        
        if(!$file) {
            return null;
        }
        
        $fileClassName = $this->getFileClassName($file);
        return new $fileClassName($this, $file);
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
        if($id instanceof \MongoId) {
            $result = $this->_mongoCollection->delete($id);
        } else {
            try {
                $result = $this->_mongoCollection->delete(new \MongoId($id));
            } catch (\MongoException $e) {
                $result = $this->_mongoCollection->delete($id);
            }
        }
        if($result['ok'] !== (double) 1) {
            throw new Exception('Error deleting file: ' . $result['err'] . ': ' . $result['errmsg']);
        }
        
        return $this;
    }
}
