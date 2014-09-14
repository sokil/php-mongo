<?php

namespace Sokil\Mongo;

class OperatorTest extends \PHPUnit_Framework_TestCase
{
    public function testPushEach_ExistedKey_SingleValue()
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

    public function testIncrement()
    {
        $operator = new Operator();

        $operator->increment('key', 1);

        $operator->increment('key', 41);

        $this->assertEquals(array(
            '$inc' => array(
                'key' => 42,
            ),
        ), $operator->getAll());
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