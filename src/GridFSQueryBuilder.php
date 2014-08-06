<?php

namespace Sokil\Mongo;

class GridFSQueryBuilder extends Cursor
{
    /**
     * Convert find result to object
     * 
     * @param array|\MongoGridFSFile $file file instance or array of metadata
     * @return \Sokil\Mongo\GridFSFile
     */
    protected function toObject($file)
    {
        return new GridFSFile($this->_collection, $file);
    }
}