<?php

namespace Sokil\Mongo\Collection;

abstract class CrudStrategy
{
    /**
     *
     * @var \MongoCollection
     */
    protected $collection;
    
    public function __construct(\MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    abstract public function findOne(array $query);
    
    abstract public function insert(array $document);

    abstract public function update(array $query, array $operators);

    abstract public function delete(array $query);
}

