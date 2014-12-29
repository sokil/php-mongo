<?php

namespace Sokil\Mongo\Pipeline;

use Sokil\Mongo\Client;
use Sokil\Mongo\Pipeline;

class GroupStageTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp()
    {
        // connect to mongo
        $client = new Client();

        // select database
        $database = $client->getDatabase('test');

        // select collection
        $this->collection = $database->getCollection('phpmongo_test_collection');
    }

    public function tearDown()
    {
        $this->collection->delete();
    }

    public function testConfigure_Callable()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":"$amount"}}}]',
            (string) $pipeline
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testConfigure_Array_EmptyId()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(array(
            'field' => 'value'
        ));
    }
}