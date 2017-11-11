<?php

namespace Sokil\Mongo\Pipeline;

use Sokil\Mongo\Client;
use Sokil\Mongo\Pipeline;
use PHPUnit\Framework\TestCase;

class GroupStageTest extends TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp()
    {
        // connect to mongo
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);

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
                    '$add' => array('$amount', 12),
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
                    '$add' => array('$amount', 12),
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
                    '$add' => array('$amount', 12),
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
                    '$add' => array('$amount', 12),
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
                    '$add' => array('$amount', 12),
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

    public function testMin_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->min('totalAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$min":"$amount"}}}]',
            (string) $pipeline
        );
    }

    public function testMin_ArrayExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->min('totalAmount', array(
                    '$add' => array('$amount', 12),
                ));
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$min":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testMin_CallableExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->min('totalAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add('$amount', 12);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","totalAmount":{"$min":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testMax_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->max('maxAmount', '$amount');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","maxAmount":{"$max":"$amount"}}}]',
            (string) $pipeline
        );
    }

    public function testMax_ArrayExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->max('maxAmount', array(
                    '$add' => array('$amount', 12),
                ));
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","maxAmount":{"$max":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testMax_CallableExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->max('maxAmount', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add('$amount', 12);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","maxAmount":{"$max":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testPush_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->push('itemsSold', '$item');
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","itemsSold":{"$push":"$item"}}}]',
            (string) $pipeline
        );
    }

    public function testPush_ArrayExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $pipeline \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->push('itemsSold', array(
                    '$add' => array('$amount', 12),
                ));
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","itemsSold":{"$push":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testPush_CallableExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$userId')
                ->push('itemsSold', function($expression) {
                    /* @var $expression \Sokil\Mongo\Pipeline\Expression */
                    $expression->add('$amount', 12);
                });
        });

        $this->assertEquals(
            '[{"$group":{"_id":"$userId","itemsSold":{"$push":{"$add":["$amount",12]}}}}]',
            (string) $pipeline
        );
    }

    public function testPush()
    {
        date_default_timezone_set('Europe/Kiev');
        $this->collection->batchInsert(array(
            array("_id" => 1, "item" => "abc", "price" => 10, "quantity" => 2, "date" => new \MongoDate(strtotime("2014-01-01T08:00:00Z")) ),
            array("_id" => 2, "item" => "jkl", "price" => 20, "quantity" => 1, "date" => new \MongoDate(strtotime("2014-02-03T09:00:00Z")) ),
            array("_id" => 3, "item" => "xyz", "price" => 5, "quantity" => 5, "date" => new \MongoDate(strtotime("2014-02-03T09:05:00Z")) ),
            array("_id" => 4, "item" => "abc", "price" => 10, "quantity" => 10, "date" => new \MongoDate(strtotime("2014-02-15T08:00:00Z")) ),
            array("_id" => 5, "item" => "xyz", "price" => 5, "quantity" => 10, "date" => new \MongoDate(strtotime("2014-02-15T09:05:00Z")) ),
            array("_id" => 6, "item" => "xyz", "price" => 5, "quantity" => 5, "date" => new \MongoDate(strtotime("2014-02-15T12:05:10Z")) ),
            array("_id" => 7, "item" => "xyz", "price" => 5, "quantity" => 10, "date" => new \MongoDate(strtotime("2014-02-15T14:12:12Z")) ),
        ));

        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
                ->setId('$item')
                ->push('itemsSold', array(
                    'quantity' => '$quantity',
                    'price' => '$price',
                    'quantityPrice' => array(
                        '$multiply' => array('$quantity', '$price')
                    ),
                ));
        });

        $result = $pipeline->aggregate();

        $this->assertEquals('xyz', $result[0]['_id']);

        $this->assertEquals(5, $result[0]['itemsSold'][0]['quantity']);
        $this->assertEquals(5, $result[0]['itemsSold'][0]['price']);
        $this->assertEquals(25, $result[0]['itemsSold'][0]['quantityPrice']);
    }
}