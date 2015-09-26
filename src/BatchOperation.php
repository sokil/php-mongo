<?php

namespace Sokil\Mongo;

abstract class BatchOperation implements \Countable
{
    protected $batchClass;

    /**
     * @var \Collection
     */
    private $collection;

    private $batch;

    private $counter = 0;

    protected $result;

    /**
     * @param Collection $collection
     * @param int|string $writeConcern Write concern. Default is 1 (Acknowledged)
     * @param int $timeout Timeout for write concern. Default is 10000 milliseconds
     * @param bool $ordered Determins if MongoDB must apply this batch in order (sequentally,
     *   one item at a time) or can rearrange it. Defaults to TRUE
     *
     * @limk http://php.net/manual/ru/mongo.writeconcerns.php
     */
    public function __construct(
        Collection $collection,
        $writeConcern = null,
        $timeout = null,
        $ordered = null
    ) {
        $this->collection = $collection;

        $writeOptions = array();

        if ($writeConcern) {
            $writeOptions['w'] = $writeConcern;
        }

        if ($timeout && is_numeric($timeout)) {
            $writeOptions['wtimeout '] = (int) $timeout;
        }

        if ($ordered) {
            $writeOptions['ordered'] = (bool) $ordered;
        }

        $className = $this->batchClass;
        $this->batch = new $className(
            $this->collection->getMongoCollection(),
            $writeOptions
        );
    }

    protected function add($data)
    {
        $this->batch->add($data);
        $this->counter++;
        return $this;
    }

    public function execute($writeConcern = null, $timeout = null, $ordered = null)
    {
        $writeOptions = array();

        if ($writeConcern) {
            $writeOptions['w'] = $writeConcern;
        }

        if ($timeout && is_numeric($timeout)) {
            $writeOptions['wtimeout '] = (int) $timeout;
        }

        if ($ordered) {
            $writeOptions['ordered'] = (bool) $ordered;
        }

        $this->result = $this->batch->execute($writeOptions);

        return $this;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function count()
    {
        return $this->counter;
    }
}
