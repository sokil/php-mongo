<?php

namespace Sokil\Mongo;

class OperatorTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @expectedException Sokil\Mongo\Exception
     * @expectedExceptionMessage Field fieldName must be pushed wit $each modifier
     */
    public function testPushEachSlice_EachNotSet()
    {
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

    /**
     * @expectedException Sokil\Mongo\Exception
     * @expectedExceptionMessage Sort condition is empty
     */
    public function testPushEachSort_EmptySortCondition()
    {
        $operator = new Operator;
        $operator->pushEach('fieldName', array(
            array('sub' => 1),
            array('sub' => 2),
            array('sub' => 3),
        ));

        $operator->pushEachSort('fieldName', array());
    }

    /**
     * @expectedException Sokil\Mongo\Exception
     * @expectedExceptionMessage Field fieldName must be pushed with $each modifier
     */
    public function testPushEachSort_EachNotSet()
    {
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

    /**
     * @expectedException Sokil\Mongo\Exception
     * @expectedExceptionMessage Position must be greater 0
     */
    public function testPushEachPosition_WrongPositionValue()
    {
        $operator = new Operator;
        $operator->pushEach('fieldName', array(1, 2, 3));
        $operator->pushEachPosition('fieldName', -1);
    }

    /**
     * @expectedException Sokil\Mongo\Exception
     * @expectedExceptionMessage Field fieldName must be pushed with $each modifier
     */
    public function testPushEachPosition_EachNotSet()
    {
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

    public function testPull_Value_New()
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

    public function testPull_Value_Existed()
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expression must be field name, callable or Expression object
     */
    public function testPull_WrongExpression()
    {
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
}