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
 * @property \MongoGridFS $collection MongoGridFS Instance
 */
class GridFS extends Collection
{
    protected $mongoCollectionClassName = '\MongoGridFS';
    
    /**
     * Factory method to get document object from array of stored document
     * @param \MongoGridFSFile $data
     * @return \Sokil\Mongo\GridFsFile
     */
    public function hydrate($data, $useDocumentPool = true)
    {
        if (($data instanceof \MongoGridFSFile) === false) {
            throw new Exception('Must be \MongoGridFSFile');
        }
        
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
        return $this->getMongoCollection()->storeFile($filename, $metadata);
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
        return $this->getMongoCollection()->storeBytes($bytes, $metadata);
    }

    /**
     * Get file instance by id of document
     * Used \MongoGridFS::findOne() instead of \MongoGridFS::get() due
     * to backward compatibility with old mongo extensions
     *
     * @param \MongoId|string|int $id
     * @return \Sokil\Mongo\GridFSFile|null
     */
    public function getFileById($id)
    {
        if ($id instanceof \MongoId) {
            $file = $this->getMongoCollection()->findOne(array('_id' => $id));
        } else {
            try {
                $file = $this->getMongoCollection()->findOne(array('_id' => new \MongoId($id)));
            } catch (\MongoException $e) {
                $file = $this->getMongoCollection()->findOne(array('_id' => $id));
            }
        }
        
        if (!$file) {
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
        if ($id instanceof \MongoId) {
            $result = $this->getMongoCollection()->delete($id);
        } else {
            try {
                $result = $this->getMongoCollection()->delete(new \MongoId($id));
            } catch (\MongoException $e) {
                $result = $this->getMongoCollection()->delete($id);
            }
        }
        if ($result['ok'] !== (double) 1) {
            throw new Exception('Error deleting file: ' . $result['err'] . ': ' . $result['errmsg']);
        }
        
        return $this;
    }
}
