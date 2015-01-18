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

class QueryBuilder extends Cursor
{
    /**
     * Convert find result to object
     * 
     * @param array $mongoDocument
     * @return \Sokil\Mongo\Document
     */
    protected function toObject($mongoDocument)
    {
        return $this->_collection->getStoredDocumentInstanceFromArray(
            $mongoDocument, 
            $this->isDocumentPoolUsed()
        );
    }
}
