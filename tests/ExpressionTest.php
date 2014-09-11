<?php

namespace Sokil\Mongo;

class ExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testMerge()
    {
        $expression1 = new Expression;
        $expression1->where('a', 77);
        $expression1->where('b', 88);
        $expression1->whereGreater('c', 99);

        $expression2 = new Expression;
        $expression2->where('a', 55);
        $expression1->whereLess('c', 66);

        $expression1->merge($expression2);

        $this->assertEquals(array(
            'a' => array(77, 55),
            'b' => 88,
            'c' => array(
                '$gt' => 99,
                '$lt' => 66,
            ),
        ), $expression1->toArray());
    }
}