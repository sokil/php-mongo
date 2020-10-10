<?php

namespace Sokil\Mongo;

use PHPUnit\Framework\TestCase;

class OperatorTest extends TestCase
{
    public function testSet()
    {
        $operator = new Operator;
        $operator->set('fieldName', 1);
        $operator->set('fieldName', 2);
        $operator->set('fieldName', 3);

        $this->assertEquals(
            array('$set' => array('fieldName' => 3)),
            $operator->getAll()
        );
    }

    public function testPush_ExistedPush()
    {
        $operator = new Operator;
        $operator->push('key', 1);
        $operator->push('key', array(2, 3, 4));

        $this->assertEquals(array(
            '$push' => array(
                'key' => array(
                    '$each' => array(1, array(2, 3, 4)),
                ),
            ),
        ), $operator->getAll());
    }

    public function testPush_ExistedPushEach()
    {
        $operator = new Operator;
        $operator->pushEach('key', array(1, 2, 3, 4));
        $operator->push('key', 5);

        $this->assertEquals(array(
            '$push' => array(
                'key' => array(
                    '$each' => array(1, 2, 3, 4, 5),
                ),
            ),
        ), $operator->getAll());
    }

    public function testPushEach_ExistedPush()
    {
        $operator = new Operator;
        $operator->push('key', 1);
        $operator->pushEach('key', array(2, 3, 4, 5));

        $this->assertEquals(array(
            '$push' => array(
                'key' => array(
                    '$each' => array(1, 2, 3, 4, 5),
                ),
            ),
        ), $operator->getAll());
    }

    public function testPushEach_ExistedPushEach()
    {
        $operator = new Operator;
        $operator->pushEach('key', array(1, 2, 3, 4));
        $operator->pushEach('key', array(5, 6, 7, 8));

        $this->assertEquals(array(
            '$push' => array(
                'key' => array(
                    '$each' => array(1, 2, 3, 4, 5, 6, 7, 8),
                ),
            ),
        ), $operator->getAll());
    }

    public function testPushEachSlice()
    {
        $operator = new Operator;
        $operator->pushEach('fieldName', array(1, 2, 3));
        $operator->pushEachSlice('fieldName', 5);

        $this->assertEquals(
            array(
                '$push' => array(
                    'fieldName' => array(
                        '$each' => array(1, 2, 3),
                        '$slice' => 5,
                    ),
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPushEachSlice_EachNotSet()
    {
        $this->expectException(\Sokil\Mongo\Exception::class);
        $this->expectExceptionMessage('Field fieldName must be pushed wit $each modifier');

        $operator = new Operator;
        $operator->pushEachSlice('fieldName', 42);
    }

    public function testPushEachSort()
    {
        $operator = new Operator;
        $operator->pushEach('fieldName', array(
            array('sub' => 1),
            array('sub' => 2),
            array('sub' => 3),
        ));

        $operator->pushEachSort('fieldName', array('sort' => 1));

        $this->assertEquals(
            array(
                '$push' => array(
                    'fieldName' => array(
                        '$each' => array(
                            array('sub' => 1),
                            array('sub' => 2),
                            array('sub' => 3),
                        ),
                        '$sort' =>  array('sort' => 1),
                    ),
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPushEachSort_EmptySortCondition()
    {
        $this->expectException(\Sokil\Mongo\Exception::class);
        $this->expectExceptionMessage('Sort condition is empty');

        $operator = new Operator;
        $operator->pushEach('fieldName', array(
            array('sub' => 1),
            array('sub' => 2),
            array('sub' => 3),
        ));

        $operator->pushEachSort('fieldName', array());
    }

    public function testPushEachSort_EachNotSet()
    {
        $this->expectException(\Sokil\Mongo\Exception::class);
        $this->expectExceptionMessage('Field fieldName must be pushed with $each modifier');

        $operator = new Operator;
        $operator->pushEachSort('fieldName', array('sub' => 1));
    }

    public function testPushEachPosition()
    {
        $operator = new Operator;

        $operator->pushEach('fieldName', array(1, 2, 3));
        $operator->pushEachPosition('fieldName', 2);

        $this->assertEquals(
            array(
                '$push' => array(
                    'fieldName' => array(
                        '$each' => array(1, 2, 3),
                        '$position' => 2,
                    ),
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPushEachPosition_WrongPositionValue()
    {
        $this->expectException(\Sokil\Mongo\Exception::class);
        $this->expectExceptionMessage('Position must be greater 0');

        $operator = new Operator;
        $operator->pushEach('fieldName', array(1, 2, 3));
        $operator->pushEachPosition('fieldName', -1);
    }

    public function testPushEachPosition_EachNotSet()
    {
        $this->expectException(\Sokil\Mongo\Exception::class);
        $this->expectExceptionMessage('Field fieldName must be pushed with $each modifier');

        $operator = new Operator;
        $operator->pushEachPosition('fieldName', 42);
    }

    public function testIncrement()
    {
        $operator = new Operator();

        $operator->increment('key', 1);

        $operator->increment('key', 41);

        $this->assertEquals(
            array(
                '$inc' => array(
                    'key' => 42,
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPull_MongoId()
    {
        $id = new \MongoId;

        $operator = new Operator();
        $operator->pull('fieldName', $id);

        $this->assertEquals(
            array(
                '$pull' => array(
                    'fieldName' => $id,
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPull_Value_Scalar_New()
    {
        $operator = new Operator();
        $operator->pull('fieldName', 42);

        $this->assertEquals(
            array(
                '$pull' => array(
                    'fieldName' => 42,
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPull_Value_Scalar_Existed()
    {
        $operator = new Operator();
        $operator->pull('fieldName', 41);
        $operator->pull('fieldName', 42);

        $this->assertEquals(
            array(
                '$pull' => array(
                    'fieldName' => 42,
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPull_Value_QueryCallable()
    {
        $operator = new Operator();
        $operator->pull('fieldName', function(Expression $e) {
            $e->where('sub.field', 42);
        });

        $this->assertEquals(
            array(
                '$pull' => array(
                    'fieldName' => array(
                        'sub.field' => 42,
                    ),
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPull_QueryCallable_New()
    {
        $operator = new Operator();
        $operator->pull(function(Expression $e) {
            $e
                ->whereGreater('fieldName', 42)
                ->where('some', 'val');
        });

        $this->assertEquals(
            array(
                '$pull' => array(
                    'fieldName' => array(
                        '$gt' => 42,
                    ),
                    'some' => 'val',
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPull_QueryCallable_ExistedValue()
    {
        $operator = new Operator();
        $operator->pull('fieldName', 41);
        $operator->pull(function(Expression $e) {
            $e
                ->whereLess('fieldName', 42);
        });

        $this->assertEquals(
            array(
                '$pull' => array(
                    'fieldName' => array(
                        '$lt' => 42,
                    ),
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPull_QueryCallable_ExistedQuery()
    {
        $operator = new Operator();
        $operator->pull(function(Expression $e) {
            $e->whereGreater('fieldName', 41);
        });
        $operator->pull(function(Expression $e) {
            $e->whereLess('fieldName', 42);
        });

        $this->assertEquals(
            array(
                '$pull' => array(
                    'fieldName' => array(
                        '$lt' => 42,
                    ),
                ),
            ),
            $operator->getAll()
        );
    }

    public function testPull_WrongExpression()
    {
        $this->expectException(\Sokil\Mongo\Exception::class);
        $this->expectExceptionMessage('Expression must be field name, callable or Expression object');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expression must be field name, callable or Expression object');

        $operator = new Operator();
        $operator->pull(new \stdClass());
    }

    public function testUnsetField()
    {
        $operator = new Operator();
        $operator->unsetField('field');

        $this->assertEquals(
            array(
                '$unset' => array(
                    'field' => '',
                ),
            ),
            $operator->getAll()
        );
    }

    public function testReset()
    {
        $operator = new Operator();
        $operator->push('key1', 42);
        $operator->unsetField('key2');
        $operator->reset();

        $this->assertEquals(
            array(),
            $operator->getAll()
        );
    }

    public function testGet()
    {
        $operator = new Operator;

        $operator->push('key', 42);

        $this->assertEquals(array('key' => 42), $operator->get('$push'));

        $this->assertEquals(42, $operator->get('$push', 'key'));
    }

    public function testGet_UnexistedOperation()
    {
        $operator = new Operator;

        $this->assertNull($operator->get('$push'));
    }

    public function testGet_UnexistedField()
    {
        $operator = new Operator;

        $this->assertNull($operator->get('$push', 'key'));
    }

    public function testAddToSet_NoKey()
    {
        $operator = new Operator;
        $operator->addToSet('param', 'value');

        $this->assertEquals(array(
            '$addToSet' => array(
                'param' => 'value',
            )
        ), $operator->toArray());
    }

    public function testAddToSet_ScalarKeyExists()
    {
        $operator = new Operator;
        $operator->addToSet('param', 'value1');
        $operator->addToSet('param', 'value2');

        $this->assertEquals(array(
            '$addToSet' => array(
                'param' => array(
                    '$each' => array(
                        'value1',
                        'value2',
                    )
                ),
            )
        ), $operator->toArray());
    }

    public function testAddToSet_ArrayKeyExists()
    {
        $operator = new Operator;
        $operator->addToSet('param', array('value1'));
        $operator->addToSet('param', 'value2');

        $this->assertEquals(array(
            '$addToSet' => array(
                'param' => array(
                    '$each' => array(
                        array('value1'),
                        'value2',
                    )
                ),
            )
        ), $operator->toArray());
    }

    public function testAddToSetEach_NoKey()
    {
        $operator = new Operator;
        $operator->addToSetEach('param',
            array('value')
        );

        $this->assertEquals(array(
            '$addToSet' => array(
                'param' => array(
                    '$each' => array(
                        'value',
                    )
                )
            )
        ), $operator->toArray());
    }

    public function testAddToSetEach_KeyExists()
    {
        $operator = new Operator;
        $operator->addToSetEach('param',
            array(
                'value1',
                'value2'
            )
        );
        $operator->addToSetEach('param',
            array(
                'value3',
                'value4'
            )
        );

        $this->assertEquals(array(
            '$addToSet' => array(
                'param' => array(
                    '$each' => array(
                        'value1',
                        'value2',
                        'value3',
                        'value4',
                    )
                )
            )
        ), $operator->toArray());
    }
}