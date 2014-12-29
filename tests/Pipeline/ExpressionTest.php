<?php

namespace Sokil\Mongo\Pipeline;

use Sokil\Mongo\Client;
use Sokil\Mongo\Pipeline;

class ExpressionTest extends \PHPUnit_Framework_TestCase
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
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add(array(
                        '$amount',
                        3.15
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$add":["$amount",3.15]}}}}]',
            (string) $pipeline
        );
    }

    public function testAdd_Array()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add(array(
                        array('$multiply' => array('$amount', 0.95)),
                        3.15
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$add":[{"$multiply":["$amount",0.95]},3.15]}}}}]',
            (string) $pipeline
        );
    }

    public function testAdd_Expression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add(array(
                        function($expression) { $expression->multiply('$amount', 0.95); },
                        3.15
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$add":[{"$multiply":["$amount",0.95]},3.15]}}}}]',
            (string) $pipeline
        );
    }

    public function testDivide_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->divide('$amount', 0.95);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$divide":["$amount",0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testDivide_Array()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->divide(
                        array('$multiply' => array('$amount', 3.15)),
                        0.95
                    );
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$divide":[{"$multiply":["$amount",3.15]},0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testDivide_Expression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->divide(
                        function($expression) { $expression->multiply('$amount', 3.15); },
                        0.95
                    );
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$divide":[{"$multiply":["$amount",3.15]},0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testMod_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->mod('$amount', 0.95);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$mod":["$amount",0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testMod_Array()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->mod(
                        array('$multiply' => array('$amount', 3.15)),
                        0.95
                    );
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$mod":[{"$multiply":["$amount",3.15]},0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testMod_Expression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->mod(
                        function($expression) { $expression->multiply('$amount', 3.15); },
                        0.95
                    );
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$mod":[{"$multiply":["$amount",3.15]},0.95]}}}}]',
            (string) $pipeline
        );
    }
    
    public function testMultiply_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->multiply(array(
                        '$amount',
                        '$discount',
                        0.95
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$multiply":["$amount","$discount",0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testMultiply_Array()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->multiply(array(
                        array('$add' => array('$amount', 5)),
                        '$discount',
                        0.95
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$multiply":[{"$add":["$amount",5]},"$discount",0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testMultiply_Expression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->multiply(array(
                        function($expression) { $expression->add('$amount', 5); },
                        '$discount',
                        0.95
                    ));
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$multiply":[{"$add":["$amount",5]},"$discount",0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testSubtract_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->subtract('$amount', 0.95);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$subtract":["$amount",0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testSubtract_Array()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->subtract(
                        array('$multiply' => array('$amount', 3.15)),
                        0.95
                    );
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$subtract":[{"$multiply":["$amount",3.15]},0.95]}}}}]',
            (string) $pipeline
        );
    }

    public function testSubtract_Expression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupPipeline */
            $pipeline
                ->setId('user.id')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->subtract(
                        function($expression) { $expression->multiply('$amount', 3.15); },
                        0.95
                    );
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"user.id","totalAmount":{"$sum":{"$subtract":[{"$multiply":["$amount",3.15]},0.95]}}}}]',
            (string) $pipeline
        );
    }
}