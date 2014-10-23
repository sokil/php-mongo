<?php

namespace Sokil\Mongo;

/**
 * Searching among files in GridFS
 *
 * @property \Sokil\Mongo\GridFS $_collection Link to GridFs instance
 */
class GridFSQueryBuilder extends Cursor
{
    /**
     * Convert find result to object
     * 
     * @param \MongoGridFSFile $file file instance
     * @return \Sokil\Mongo\GridFSFile
     * @throws \Sokil\Mongo\Exception
     */
    protected function toObject($file)
    {
        return $this->_collection->getStoredGridFsFileInstanceFromMongoGridFSFile($file);
    }
}