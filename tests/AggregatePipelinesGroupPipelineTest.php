<?php

namespace Sokil\Mongo;

class AggregatePipelinesGroupPipelineTest extends \PHPUnit_Framework_TestCase
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
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":"$amount"}}}]',
            (string) $pipelines
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testConfigure_Array_EmptyId()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(array(
            'field' => 'value'
        ));
    }
}