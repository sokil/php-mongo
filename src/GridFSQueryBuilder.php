<?php

namespace Sokil\Mongo;

class GridFSQueryBuilder extends Cursor
{
    /**
     * Convert find result to object
     * 
     * @param \MongoGridFSFile $file file instance
     * @return \Sokil\Mongo\GridFSFile
     */
    protected function toObject($file)
    {
        if(!($file instanceof \MongoGridFSFile)) {
            throw new \Exception('Must be instance of \MongoGridFSFile');
        }
        
        $fileClassName = $this->_collection->getFileClassName($file);
        
        return new $fileClassName($this->_collection, $file);
    }
}