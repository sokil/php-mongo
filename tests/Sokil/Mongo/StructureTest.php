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
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testSetArrayToScalar()
    {
        $structure = new Structure;
        $structure->fromArray(array(
            'a' => 1,
        ));
        
        $structure->set('a.b', 2);
    }
    
    public function testUnset()
    {
        $structure = new Structure;
        $structure->fromArray(array(
            'a' => array(
                'a1'    => array(
                    'a11'   => 1,
                    'a12'   => 2,
                ),
                'a2'    => array(
                    'a21'   => 1,
                    'a22'   => 2,
                ),
            )
        ));
        
        $structure->unsetField('a.a2.a21');
        $this->assertEquals(array(
            'a' => array(
                'a1'    => array(
                    'a11'   => 1,
                    'a12'   => 2,
                ),
                'a2'    => array(
                    'a22'   => 2,
                ),
            )
        ), $structure->toArray());
    }
    
    public function testAppend()
    {
        $structure = new Structure;
        
        $structure->append('key', 'v1');
        $this->assertEquals('v1', $structure->key);
        
        $structure->append('key', 'v2');
        $this->assertEquals(array('v1', 'v2'), $structure->key);
        
        $structure->set('key', 'v1');
        $this->assertEquals('v1', $structure->key);
        
        $structure->append('key', 'v2');
        $this->assertEquals(array('v1', 'v2'), $structure->key);
    }
    
    public function testGet()
    {
        $structure = new Structure;
        $structure->set('a.b.c.d', 'value');
        
        // get unexisted
        $this->assertEquals(null, $structure->get('unexisted-key'));
        
        // get existed
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
        
        // get unexited key
        $this->assertEquals(null, $structure->getObject('unexisted-param', '\Sokil\Mongo\StructureTest\StructureWrapper'));
        
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
        
        // get unexited key
        $this->assertEquals(array(), $structure->getObjectList('unexisted-param', '\Sokil\Mongo\StructureTest\StructureWrapper'));
        
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
    
    public function testGetModifiedFields()
    {
        $structure = new \Sokil\Mongo\Structure;
        $structure->set('param1', 'value');
        $structure->set('param2.subparam', 'value');
        
        $this->assertEquals(array(
            'param1',
            'param2.subparam'
        ), $structure->getModifiedFields());
    }
    
    public function testIsModified()
    {
        $structure = new \Sokil\Mongo\Structure;
 
        $this->assertFalse($structure->isModified());
        // 1
        $structure->set('param1', 'value');
        
        $this->assertTrue($structure->isModified());
        
        $this->assertTrue($structure->isModified('param1'));
        $this->assertFalse($structure->isModified('param1-unex'));
        
        // 2
        $structure->set('param2.subparam', 'value');
        
        $this->assertTrue($structure->isModified('param2'));
        $this->assertTrue($structure->isModified('param2.subparam'));
        
        $this->assertFalse($structure->isModified('param2-unex'));
        $this->assertFalse($structure->isModified('param2-unex.subparam'));
        $this->assertFalse($structure->isModified('param2.subparam-unex'));
        
        // 3
        $structure->set('param3.subparam1.subparam', 'value');
        
        $this->assertTrue($structure->isModified('param3'));
        $this->assertTrue($structure->isModified('param3.subparam1'));
        $this->assertTrue($structure->isModified('param3.subparam1.subparam'));
        
        $this->assertFalse($structure->isModified('param3-unex'));
        $this->assertFalse($structure->isModified('param3-unex.subparam1'));
        $this->assertFalse($structure->isModified('param3.subparam1-unex'));
        $this->assertFalse($structure->isModified('param3.subparam1.subparam-unex'));
    }
}