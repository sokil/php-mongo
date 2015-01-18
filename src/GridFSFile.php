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

class GridFSFile extends Structure implements \Countable
{
    /**
     *
     * @var \Sokil\Mongo\GridFS
     */
    private $_gridFS;
    
    /**
     * @var \MongoGridFSFile 
     */
    private $_file;
        
    /**
     * 
     * @param \Sokil\Mongo\GridFS $gridFS instance of GridFS
     * @param array|\MongoGridFSFile $file instance of File or metadata array
     * @throws \Sokil\Mongo\Exception
     */
    public function __construct(GridFS $gridFS, $file = null)
    {
        $this->_gridFS = $gridFS;
        
        if(!$file) {
            return;
        }

        if(is_array($file)) {
            $file = new \MongoGridFSFile($gridFS->getMongoCollection(), $file);
        } elseif(!($file instanceof \MongoGridFSFile)) {
            throw new Exception('Wrong file data specified');
        }

        $this->_file = $file;
        $this->_data = &$file->file;
    }
    
    /**
     * Get instance of native mongo file
     * 
     * @return \MongoGridFSFile 
     */
    public function getMongoGridFsFile()
    {
        return $this->_file;
    }
    
    public function getFilename()
    {
        return $this->_file->getFilename();
    }
    
    public function count()
    {
        return $this->_file->getSize();
    }
    
    public function getMd5Checksum()
    {
        return $this->_file->file['md5'];
    }
    
    public function save()
    {
        $this->_gridFS->getMongoCollection()->save($this->_file->file);
    }
    
    public function dump($filename)
    {
        $this->_file->write($filename);
    }
    
    public function getBytes()
    {
        return $this->_file->getBytes();
    }
    
    public function getResource()
    {
        return $this->_file->getResource();
    }
    
    public function delete()
    {
        $this->_gridFS->deleteFileById($this->get('_id'));
    }
}
