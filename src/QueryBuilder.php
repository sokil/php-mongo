<?php

namespace Sokil\Mongo;

class QueryBuilder extends Cursor
{
    /**
     * Convert find result to object
     * 
     * @param array $mongoDocument
     * @return \Sokil\Mongo\className
     */
    protected function toObject($mongoDocument)
    {
        $className = $this->_collection->getDocumentClassName($mongoDocument);
        return new $className($this->_collection, $mongoDocument, array(
            'stored' => true
        ));
    }
}