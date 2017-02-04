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
    private $gridFS;
    
    /**
     * @var \MongoGridFSFile
     */
    private $file;
        
    /**
     *
     * @param \Sokil\Mongo\GridFS $gridFS instance of GridFS
     * @param array|\MongoGridFSFile $file instance of File or metadata array
     * @throws \Sokil\Mongo\Exception
     */
    public function __construct(GridFS $gridFS, $file = null)
    {
        $this->gridFS = $gridFS;
        
        if (!$file) {
            return;
        }

        if (is_array($file)) {
            $file = new \MongoGridFSFile($gridFS->getMongoCollection(), $file);
        } elseif (!($file instanceof \MongoGridFSFile)) {
            throw new Exception('Wrong file data specified');
        }

        $this->file = $file;
        $this->setDataReference($file->file);
    }
    
    /**
     * Get instance of native mongo file
     *
     * @return \MongoGridFSFile
     */
    public function getMongoGridFsFile()
    {
        return $this->file;
    }
    
    public function getFilename()
    {
        return $this->file->getFilename();
    }
    
    public function count()
    {
        return $this->file->getSize();
    }
    
    public function getMd5Checksum()
    {
        return $this->file->file['md5'];
    }
    
    public function save()
    {
        $this->gridFS->getMongoCollection()->save($this->file->file);
    }
    
    public function dump($filename)
    {
        $this->file->write($filename);
    }
    
    public function getBytes()
    {
        return $this->file->getBytes();
    }
    
    public function getResource()
    {
        return $this->file->getResource();
    }
    
    public function delete()
    {
        $this->gridFS->deleteFileById($this->get('_id'));
    }
}
