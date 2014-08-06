<?php

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
     * @param \MongoGridFSFile $gridFS instance of GridFS
     * @param array|\MongoGridFSFile $file instance of File or metadata array
     */
    public function __construct(GridFS $gridFS, $file = null)
    {
        $this->_gridFS = $gridFS;
        
        if($file) {
            if($file instanceof \MongoGridFSFile) {
                $this->_file = $file;
            } elseif(is_array($file)) {
                $this->_file = new \MongoGridFSFile($gridFS, $file);
            } else {
                throw new Exception('Wrong file data specified');
            }
            
            $this->_data = &$file->file;
        }
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
    
    public function delete()
    {
        $this->_gridFS->deleteFileById($this->get('_id'));
    }
}