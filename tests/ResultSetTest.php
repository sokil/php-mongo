<?php

namespace Sokil\Mongo;

class ResultSetTest extends \PHPUnit_Framework_TestCase
{
    public function testMap()
    {
        $resultSet = new ResultSet(array(
            1 => array('_id' => 1, 'field' => 'value1'),
            2 => array('_id' => 2, 'field' => 'value2'),
            3 => array('_id' => 3, 'field' => 'value3'),
        ));

        $newSet = $resultSet->map(function($item) {
            $item['newField'] = 'newValue' . $item['_id'];
            return $item;
        });

        $this->assertNotEmpty(count($newSet));

        foreach($newSet as $id => $item) {
            $this->assertArrayHasKey('newField', $item);
            $this->assertEquals('newValue' . $id, $item['newField']);
        }
    }

    public function testReduce()
    {
        $resultSet = new ResultSet(array(
            1 => array('_id' => 1, 'field' => '10'),
            2 => array('_id' => 2, 'field' => '20'),
            3 => array('_id' => 3, 'field' => '30'),
        ));

        $value = $resultSet->reduce(function($accumulator, $item) {
            $accumulator += $item['field'];
            return $accumulator;
        }, 0);

        $this->assertEquals(60, $value);
    }

    public function testFilter()
    {
        $resultSet = new ResultSet(array(
            1 => array('_id' => 1, 'field' => 'value1'),
            2 => array('_id' => 2, 'field' => 'value2'),
            3 => array('_id' => 3, 'field' => 'value3'),
        ));

        // skip even ids
        $newSet = $resultSet->filter(function($item) {
            return ($item['_id'] % 2 !== 0);
        });

        $this->assertEquals(
            array(
                1 => array('_id' => 1, 'field' => 'value1'),
                3 => array('_id' => 3, 'field' => 'value3'),
            ),
            iterator_to_array($newSet)
        );
    }

    public function testEach()
    {
        $resultSet = new ResultSet(array(
            1 => array('_id' => 1, 'field' => 'value1'),
            2 => array('_id' => 2, 'field' => 'value2'),
            3 => array('_id' => 3, 'field' => 'value3'),
        ));

        // skip even ids
        $resultSet->each(function($item, $id, $resultSet) {
            if($item['_id'] % 2 === 0) {
                unset($resultSet[$id]);
            }
        });

        $this->assertEquals(
            array(
                1 => array('_id' => 1, 'field' => 'value1'),
                3 => array('_id' => 3, 'field' => 'value3'),
            ),
            iterator_to_array($resultSet)
        );
    }

    public function testKeys()
    {
        $resultSet = new ResultSet(array(
            'a' => array('_id' => 'a', 'field' => 'value1'),
            'b' => array('_id' => 'b', 'field' => 'value2'),
            'c' => array('_id' => 'c', 'field' => 'value3'),
        ));

        $values = $resultSet->keys();

        $this->assertEquals(array('a', 'b', 'c'), $values);
    }

    public function testValues()
    {
        $resultSet = new ResultSet(array(
            'a' => array('_id' => 'a', 'field' => 'value1'),
            'b' => array('_id' => 'b', 'field' => 'value2'),
            'c' => array('_id' => 'c', 'field' => 'value3'),
        ));

        $values = $resultSet->values();

        $this->assertEquals(array(
            array('_id' => 'a', 'field' => 'value1'),
            array('_id' => 'b', 'field' => 'value2'),
            array('_id' => 'c', 'field' => 'value3'),
        ), $values);
    }

    public function testToArray()
    {
        $resultSet = new ResultSet(array(
            'a' => array('_id' => 'a', 'field' => 'value1'),
            'b' => array('_id' => 'b', 'field' => 'value2'),
            'c' => array('_id' => 'c', 'field' => 'value3'),
        ));

        $values = $resultSet->toArray();

        $this->assertEquals(array(
            'a' => array('_id' => 'a', 'field' => 'value1'),
            'b' => array('_id' => 'b', 'field' => 'value2'),
            'c' => array('_id' => 'c', 'field' => 'value3'),
        ), $values);
    }

    public function testOffsetExists()
    {
        $resultSet = new ResultSet(array(
            'a' => array('_id' => 'a', 'field' => 'value1'),
            'b' => array('_id' => 'b', 'field' => 'value2'),
            'c' => array('_id' => 'c', 'field' => 'value3'),
        ));

        $this->assertTrue(isset($resultSet['a']));
    }

    public function testOffsetSet()
    {
        $resultSet = new ResultSet(array(
            'a' => array('_id' => 'a', 'field' => 'value1'),
            'b' => array('_id' => 'b', 'field' => 'value2'),
            'c' => array('_id' => 'c', 'field' => 'value3'),
        ));

        $resultSet['d'] = array('_id' => 'd', 'field' => 'value4');

        $this->assertEquals(
            array(
                'a' => array('_id' => 'a', 'field' => 'value1'),
                'b' => array('_id' => 'b', 'field' => 'value2'),
                'c' => array('_id' => 'c', 'field' => 'value3'),
                'd' => array('_id' => 'd', 'field' => 'value4'),
            ),
            $resultSet->toArray()
        );
    }

    public function testOffsetGet()
    {
        $resultSet = new ResultSet(array(
            'a' => array('_id' => 'a', 'field' => 'value1'),
            'b' => array('_id' => 'b', 'field' => 'value2'),
            'c' => array('_id' => 'c', 'field' => 'value3'),
        ));

        $resultSet['d'] = array('_id' => 'd', 'field' => 'value4');

        $this->assertEquals(
            array('_id' => 'b', 'field' => 'value2'),
            $resultSet['b']
        );
    }
}