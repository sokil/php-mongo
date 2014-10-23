<?php

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
        return $this->_collection->getStoredDocumentInstanceFromArray($mongoDocument);
    }
}