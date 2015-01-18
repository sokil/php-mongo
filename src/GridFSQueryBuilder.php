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
        return $this
            ->_collection
            ->getStoredGridFsFileInstanceFromMongoGridFSFile(
                $file,
                $this->isDocumentPoolUsed()
            );
    }
}
