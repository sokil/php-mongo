<?php

namespace Sokil\Mongo;

class BatchInsert extends BatchOperation
{
    protected $batchClass = '\MongoInsertBatch';

    public function insert(array $document)
    {
        $this->add($document);
        return $this;
    }
}