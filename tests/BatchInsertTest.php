<?php

namespace Sokil\Mongo;

use PHPUnit\Framework\TestCase;

class BatchInsertTest extends TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp(): void
    {
        // connect to mongo
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);

        // select database
        $database = $client->getDatabase('test');
        $this->collection = $database->getCollection('phpmongo_test_collection');
    }

    public function testEnableValidation()
    {
        $batch = new BatchInsert($this->collection);
        $batch->enableValidation();

        $this->assertTrue($batch->isValidationEnabled());
    }

    public function testDisableValidation()
    {
        $batch = new BatchInsert($this->collection);
        $batch->disableValidation();

        $this->assertFalse($batch->isValidationEnabled());
    }
}