<?php

namespace Sokil\Mongo;

class AggregatePipelinesExpressionTest extends \PHPUnit_Framework_TestCase
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

    public function testAdd_Literal()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\AggregatePipelines\Expression */
                    $expression->add(array(
                        '$amount',
                        3.15
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$add":["$amount",3.15]}}}}]',
            (string) $pipelines
        );
    }

    public function testAdd_Array()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\AggregatePipelines\Expression */
                    $expression->add(array(
                        array('$multiply' => array('$amount', 0.95)),
                        3.15
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$add":[{"$multiply":["$amount",0.95]},3.15]}}}}]',
            (string) $pipelines
        );
    }

    public function testAdd_Expression()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\AggregatePipelines\Expression */
                    $expression->add(array(
                        function($expression) { $expression->multiply('$amount', 0.95); },
                        3.15
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$add":[{"$multiply":["$amount",0.95]},3.15]}}}}]',
            (string) $pipelines
        );
    }

    public function testDivide_Literal()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\AggregatePipelines\Expression */
                    $expression->divide('$amount', 0.95);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$divide":["$amount",0.95]}}}}]',
            (string) $pipelines
        );
    }

    public function testDivide_Array()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\AggregatePipelines\Expression */
                    $expression->divide(
                        array('$multiply' => array('$amount', 3.15)),
                        0.95
                    );
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$divide":[{"$multiply":["$amount",3.15]},0.95]}}}}]',
            (string) $pipelines
        );
    }

    public function testDivide_Expression()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\AggregatePipelines\Expression */
                    $expression->divide(
                        function($expression) { $expression->multiply('$amount', 3.15); },
                        0.95
                    );
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$divide":[{"$multiply":["$amount",3.15]},0.95]}}}}]',
            (string) $pipelines
        );
    }
    
    public function testMultiply_Literal()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\AggregatePipelines\Expression */
                    $expression->multiply(array(
                        '$amount',
                        '$discount',
                        0.95
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$multiply":["$amount","$discount",0.95]}}}}]',
            (string) $pipelines
        );
    }

    public function testMultiply_Array()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\AggregatePipelines\Expression */
                    $expression->multiply(array(
                        array('$add' => array('$amount', 5)),
                        '$discount',
                        0.95
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$multiply":[{"$add":["$amount",5]},"$discount",0.95]}}}}]',
            (string) $pipelines
        );
    }

    public function testMultiply_Expression()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\AggregatePipelines\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\AggregatePipelines\Expression */
                    $expression->multiply(array(
                        function($expression) { $expression->add('$amount', 5); },
                        '$discount',
                        0.95
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$multiply":[{"$add":["$amount",5]},"$discount",0.95]}}}}]',
            (string) $pipelines
        );
    }
}