<?php

namespace Sokil\Mongo;

class StructureTest extends \PHPUnit_Framework_TestCase
{
    public function testSet()
    {
        $structure = new Structure;
        
        // 1
        $structure->set('a', 'a');
        
        // 2
        $structure->set('b.a', 'b.a');
        $structure->set('b.b', 'b.b');
        
        // 3
        $structure->set('c.a.a', 'c.a.a');
        $structure->set('c.a.b', 'c.a.b');
        $structure->set('c.a.c', 'c.a.c');
        
        $structure->set('c.b.a', 'c.b.a');
        $structure->set('c.b.b', 'c.b.b');
        $structure->set('c.b.c', 'c.b.c');
        
        $this->assertEquals(array(
            'a' => 'a',
            'b' => array(
                'a' => 'b.a',
                'b' => 'b.b'
            ),
            'c' => array(
                'a' => array(
                    'a' => 'c.a.a',
                    'b' => 'c.a.b',
                    'c' => 'c.a.c'
                ),
                'b' => array(
                    'a' => 'c.b.a',
                    'b' => 'c.b.b',
                    'c' => 'c.b.c'
                ),
            )
        ), $structure->toArray());
    }
    
    public function testGet()
    {
        $structure = new Structure;
        $structure->set('a.b.c.d', 'value');
        
        $this->assertEquals('value', $structure->get('a.b.c.d'));
        $this->assertEquals(array('d' => 'value'), $structure->get('a.b.c'));
        $this->assertEquals(array('c' => array('d' => 'value')), $structure->get('a.b'));
        $this->assertEquals(array('b' => array('c' => array('d' => 'value'))), $structure->get('a'));
    }
    
    public function testGetObject()
    {
        $structure = new Structure;
        $structure->set('param1', 'value1');
        $structure->set('param2', array(
            'a' => 'a',
            'b' => 'b'
        ));
        
        // get object
        $structureWrapper = $structure->getObject('param2', '\Sokil\Mongo\StructureTest\StructureWrapper');
        
        // tests
        $this->assertInstanceOf('\Sokil\Mongo\StructureTest\StructureWrapper', $structureWrapper);
        $this->assertEquals('b', $structureWrapper->get('b'));
        $this->assertEquals('def-c', $structureWrapper->get('c'));
    }
    
    public function testGetObjectWithClusureClassName()
    {
        $structure = new Structure;
        $structure->set('param1', 'value1');
        $structure->set('param2', array(
            'a' => 'a',
            'b' => 'b'
        ));
        
        // get object
        $structureWrapper = $structure->getObject('param2', function($data) {
            return '\Sokil\Mongo\StructureTest\StructureWrapper';
        });
        
        // tests
        $this->assertInstanceOf('\Sokil\Mongo\StructureTest\StructureWrapper', $structureWrapper);
        $this->assertEquals('b', $structureWrapper->get('b'));
        $this->assertEquals('def-c', $structureWrapper->get('c'));
    }
    
    public function testGetObjectListWithClusureClassName()
    {
        $structure = new Structure;
        $structure->set('param1', 'value1');
        $structure->set('param2', array(
            array('a' => 'a1'),
        ));
        
        // get object
        $structureList = $structure->getObjectList('param2', function($data) {
            return '\Sokil\Mongo\StructureTest\StructureWrapper';
        });
        
        // tests
        $this->assertEquals('array', gettype($structureList));
        $this->assertEquals(1, count($structureList));
        
        $this->assertInstanceOf('\Sokil\Mongo\StructureTest\StructureWrapper', $structureList[0]);
        
        $this->assertEquals('a1', $structureList[0]->get('a'));
        $this->assertEquals('def-b', $structureList[0]->get('b'));
        $this->assertEquals('def-c', $structureList[0]->get('c'));
    }
}