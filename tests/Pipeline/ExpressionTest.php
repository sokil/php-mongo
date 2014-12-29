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

    public function testNormalize_Literal()
    {
        $this->assertEquals(1, Expression::normalize(1));
        $this->assertEquals('hello', Expression::normalize('hello'));
        $this->assertEquals('$field', Expression::normalize('$field'));
    }

    public function testNormalize_ExpressionObject()
    {
        $subExpression = new Expression;
        $subExpression->mod('$field', 2);
        
        $expression = new Expression();
        $expression->add(array(
            1,
            function($expression) {
                $expression->multiply('$field', 12);
            },
            $subExpression,
        ));
        
        $this->assertEquals(array(
            '$add' => array(
                1,
                array(
                    '$multiply' => array('$field', 12),
                ),
                array(
                    '$mod' => array('$field', 2)
                )
            ),
        ), Expression::normalize($expression));
    }

    public function testNormalize_ExpressionCallable()
    {
        $expression = Expression::normalize(function($expression) {
            $subExpression = new Expression;
            $subExpression->mod('$field', 2);

            $expression->add(array(
                1,
                function($expression) {
                    $expression->multiply('$field', 12);
                },
                $subExpression,
            ));
            
        });

        $this->assertEquals(
            array(
                '$add' => array(
                    1,
                    array(
                        '$multiply' => array('$field', 12),
                    ),
                    array(
                        '$mod' => array('$field', 2)
                    )
                ),
            ),
            $expression
        );
    }

    public function testNormalize_Complex()
    {
        $this->markTestIncomplete('Complex structures not normalized');

        $subExpression = new Expression;
        $subExpression->mod('$fieldName', 2);

        $expression = Expression::normalize(array(
            'field1' => 1,
            'field2' => $subExpression,
            'field3' => function($expression) {
                $expression->add('$fieldName', 5);
            },
            'field4' => array(
                'subField41' => 1,
                'subField42' => $subExpression,
                'subField43' => function($expression) {
                    $expression->add('$fieldName', 5);
                },
                'subField44' => array(
                    'subField441' => 1,
                    'subField442' => $subExpression,
                    'subField443' => function($expression) {
                        $expression->add('$fieldName', 5);
                    }
                )
            )
        ));

        $this->assertEquals(
            array(
                'field1' => 1,
                'field2' => array(
                    '$mod' => array('$fieldName', 2),
                ),
                'field3' => array(
                    '$add' => array('$fieldName', 5),
                ),
                'field4' => array(
                    'subField41' => 1,
                    'subField42' => array(
                        '$mod' => array('$fieldName', 2),
                    ),
                    'subField43' => array(
                        '$add' => array('$fieldName', 5),
                    ),
                    'subField44' => array(
                        'subField441' => 1,
                        'subField442' => array(
                            '$mod' => array('$fieldName', 2),
                        ),
                        'subField443' => array(
                            '$add' => array('$fieldName', 5),
                        )
                    )
                )
            ),
            $expression
        );
    }

    public function testAdd_Literal()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testAdd_ArrayExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testAdd_CallableExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testDivide_ArrayExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testDivide_CallableExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testMod_ArrayExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testMod_CallableExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testMultiply_ArrayExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testMultiply_CallableExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testSubtract_ArrayExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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

    public function testSubtract_CallableExpressin()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(function($stage) {
            /* @var $stage \Sokil\Mongo\Pipeline\GroupStage */
            $stage
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