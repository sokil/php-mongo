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

    public function testConfigure_Array()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(array(
            '_id' => '$userId',
            'totalAmount' => array(
                '$sum' => '$amount',
            )
        ));

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$sum":"$amount"}}}]',
            (string) $pipeline
        );
    }
    public function testConfigure_Callable()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->sum('totalAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$sum":"$amount"}}}]',
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

    public function testSum_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->sum('totalAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$sum":"$amount"}}}]',
            (string) $pipeline
        );
    }

    public function testSum_ArrayExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->sum('totalAmount', array(
                    '$add' => ['$amount', 12]
                ));
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$sum":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testSum_CallableExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->sum('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add('$amount', 12);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$sum":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testAddToSet_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->addToSet('totalAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$addToSet":"$amount"}}}]',
            (string) $pipeline
        );
    }

    public function testAddToSet_ArrayExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->addToSet('totalAmount', array(
                    '$add' => ['$amount', 12]
                ));
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$addToSet":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testAddToSet_CallableExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->addToSet('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add('$amount', 12);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$addToSet":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testAvg_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->avg('totalAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$avg":"$amount"}}}]',
            (string) $pipeline
        );
    }

    public function testAvg_ArrayExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->avg('totalAmount', array(
                    '$add' => ['$amount', 12]
                ));
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$avg":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testAvg_CallableExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->avg('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add('$amount', 12);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$avg":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testFirst_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->first('totalAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$first":"$amount"}}}]',
            (string) $pipeline
        );
    }

    public function testFirst_ArrayExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->first('totalAmount', array(
                    '$add' => ['$amount', 12]
                ));
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$first":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testFirst_CallableExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->first('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add('$amount', 12);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$first":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testLast_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->last('totalAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$last":"$amount"}}}]',
            (string) $pipeline
        );
    }

    public function testLast_ArrayExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->last('totalAmount', array(
                    '$add' => ['$amount', 12]
                ));
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$last":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testLast_CallableExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->last('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add('$amount', 12);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$last":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }
}