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

abstract class BatchOperation implements \Countable
{
    /**
     * Batch operation class name. Must be override in child classes
     * @var string
     */
    protected $batchClass;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * Batch operation instance
     * @var \MongoWriteBatch
     */
    private $batch;

    /**
     * Amount of operations in batch operation
     * @var int
     */
    private $counter = 0;

    /**
     * Result of executed batch operation
     * @var array
     */
    protected $result;

    /**
     * @param Collection    $collection
     * @param int|string    $writeConcern  Write concern. Default is 1 (Acknowledged).
     *                                     More info at http://php.net/manual/ru/mongo.writeconcerns.php
     * @param int           $timeout       Timeout for write concern. Default is 10000 milliseconds
     * @param bool          $ordered       Set to true if MongoDB must apply this batch in order (sequentially,
     *                                     one item at a time) or can rearrange it. Defaults to TRUE
     */
    public function __construct(
        Collection $collection,
        $writeConcern = null,
        $timeout = null,
        $ordered = null
    ) {
        $this->collection = $collection;

        $writeOptions = array();

        if (null !== $writeConcern) {
            $writeOptions['w'] = $writeConcern;
        }

        if (null !== $timeout && is_numeric($timeout)) {
            if (version_compare(\MongoClient::VERSION, '1.5', '<=')) {
                $writeOptions['wtimeout'] = (int) $timeout;
            } else {
                $writeOptions['wTimeoutMS'] = (int) $timeout;
            }
        }

        if (true === $ordered) {
            $writeOptions['ordered'] = true;
        }

        $className = $this->batchClass;
        $this->batch = new $className(
            $this->collection->getMongoCollection(),
            $writeOptions
        );

        $this->init();
    }

    protected function init() {}

    protected function add($data)
    {
        $this->batch->add($data);
        $this->counter++;
        return $this;
    }

    public function execute($writeConcern = null, $timeout = null, $ordered = null)
    {
        $writeOptions = array();

        if (null !== $writeConcern) {
            $writeOptions['w'] = $writeConcern;
        }

        if ($timeout && is_numeric($timeout)) {
            $writeOptions['wtimeout'] = (int) $timeout;
        }

        if (true === $ordered) {
            $writeOptions['ordered'] = true;
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
