<?php

namespace Sokil\Mongo;

class StructureWrapper extends \Sokil\Mongo\Structure
{
    protected $_data = array(
        'a' => 'def-a',
        'b' => 'def-b',
        'c' => 'def-c',
    );
}

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
        $structure->merge(array(
            'a' => 1,
        ));
        
        $structure->set('a.b', 2);
    }
    
    public function testUnset_ValidFieldWithDots()
    {
        $structure = new Structure;
        $structure->merge(array(
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

    public function testUnset_InvalidFieldWithDots()
    {
        $structure = new Structure;
        $structure->merge(array(
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

        $structure->unsetField('a.zzz.a21');
        $this->assertEquals(array(
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
        ), $structure->toArray());
    }

    public function testUnset_ValidFieldWithoutDots()
    {
        $structure = new Structure;
        $structure->merge(array(
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ));

        $structure->unsetField('b');
        $this->assertEquals(array(
            'a' => 1,
            'c' => 3,
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
    
    public function testGetObject_StringClass()
    {
        $structure = new Structure;
        $structure->set('param1', 'value1');
        $structure->set('param2', array(
            'a' => 'a',
            'b' => 'b'
        ));
        
        // get unexited key
        $this->assertEquals(null, $structure->getObject('unexisted-param', '\Sokil\Mongo\StructureWrapper'));
        
        // get object
        $structureWrapper = $structure->getObject('param2', '\Sokil\Mongo\StructureWrapper');
        
        // tests
        $this->assertInstanceOf('\Sokil\Mongo\StructureWrapper', $structureWrapper);
        $this->assertEquals('b', $structureWrapper->get('b'));
        $this->assertEquals('def-c', $structureWrapper->get('c'));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Wrong structure class specified
     */
    public function testGetObject_StringClass_NotExtendStructure()
    {
        $structure = new Structure;
        $structure->set('param1', 'value1');
        $structure->set('param2', array(
            'a' => 'a',
            'b' => 'b'
        ));

        $structure->getObject('param1', '\stdClass');
    }
    
    public function testGetObject_ClosureClass()
    {
        $structure = new Structure;
        $structure->set('param1', 'value1');
        $structure->set('param2', array(
            'a' => 'a',
            'b' => 'b'
        ));
        
        // get object
        $structureWrapper = $structure->getObject('param2', function($data) {
            return '\Sokil\Mongo\StructureWrapper';
        });
        
        // tests
        $this->assertInstanceOf('\Sokil\Mongo\StructureWrapper', $structureWrapper);
        $this->assertEquals('b', $structureWrapper->get('b'));
        $this->assertEquals('def-c', $structureWrapper->get('c'));
    }

    public function testGetObjectList_StringClass()
    {
        $structure = new Structure;
        $structure->set('param1', array(
            array('a' => 'a0'),
            array('a' => 'a1'),
        ));

        // get unexited key
        $this->assertEquals(array(), $structure->getObjectList('unexisted-param', '\Sokil\Mongo\StructureWrapper'));

        // get existed key
        $list = $structure->getObjectList('param1', '\Sokil\Mongo\StructureWrapper');
        foreach($list as $i => $item) {
            $this->assertInstanceOf('\Sokil\Mongo\StructureWrapper', $item);
            $this->assertEquals('a' . $i, $item->a);
        }
    }

    public function testGetObjectList_ClosureClass()
    {
        $structure = new Structure;
        $structure->set('param1', array(
            array('a' => 'a0'),
            array('a' => 'a1'),
        ));

        $list = $structure->getObjectList('param1', function($data) {
            return '\Sokil\Mongo\StructureWrapper';
        });

        $this->assertEquals('array', gettype($list));
        $this->assertEquals(2, count($list));

        foreach($list as $i => $item) {
            $this->assertInstanceOf('\Sokil\Mongo\StructureWrapper', $item);
            $this->assertEquals('a' . $i, $item->a);
        }
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Wrong structure class specified
     */
    public function testGetObjectList_StringClass_NotExtendStructure()
    {
        $structure = new Structure;
        $structure->set('param1', array(
            array('a' => 'a1'),
            array('a' => 'a2'),
        ));

        $structure->getObjectList('param1', '\stdClass');
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Wrong structure class specified
     */
    public function testGetObjectList_ClosureClass_NotExtendStructure()
    {
        $structure = new Structure;
        $structure->set('param1', 'value1');
        $structure->set('param2', array(
            array('a' => 'a1'),
        ));

        // get object
        $structure->getObjectList('param2', function($data) {
            return '\stdClass';
        });
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Wrong class name specified. Use string or closure
     */
    public function testGetObjectList_ClassNotClosureAndValidClassName()
    {
        $structure = new Structure;
        $structure->set('param1', 'value1');
        $structure->set('param2', array(
            array('a' => 'a1'),
        ));

        // get object
        $structure->getObjectList('param2', 42);
    }

    public function testGetModifiedFields()
    {
        $structure = new Structure;
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
    
    /**
     * @covers \Sokil\Mongo\Structure::has
     */
    public function testHas()
    {
        $structure = new Structure;
        
        $structure->load(array(
            'param1'    => array(
                'param2'    => 'value2',
            )
        ));
        
        $this->assertTrue($structure->has('param1'));
        $this->assertTrue($structure->has('param1.param2'));
        
        $this->assertFalse($structure->has('paramNONE'));
        $this->assertFalse($structure->has('paramNONE.param2'));
    }
    
    public function testHasNull()
    {
        $structure = new Structure;
        
        $structure->load(array(
            'param'    => null
        ));
        
        $this->assertTrue($structure->has('param'));
    }

    public function testMergeUnmodified_WithoutDots_NewKey()
    {
        $structure = new Structure;
        $structure->mergeUnmodified(array('param1' => 'value1'));
        $structure->mergeUnmodified(array('param2' => 'value2'));

        $this->assertEquals(array(
            'param1' => 'value1',
            'param2' => 'value2',
        ), $structure->toArray());
    }

    public function testMergeUnmodified_WithDots_NewKey()
    {
        $structure = new Structure;
        $structure->mergeUnmodified(array('param1' => array('sub1' => 'value1')));
        $structure->mergeUnmodified(array('param2' => array('sub2' => 'value2')));

        $this->assertEquals(array(
            'param1' => array('sub1' => 'value1'),
            'param2' => array('sub2' => 'value2'),
        ), $structure->toArray());
    }

    public function testMergeUnmodified_WithoutDots_ExistedKey()
    {
        $structure = new Structure;
        $structure->mergeUnmodified(array('param' => 'value1'));
        $structure->mergeUnmodified(array('param' => 'value2'));

        $this->assertEquals(array(
            'param' => 'value2',
        ), $structure->toArray());
    }

    public function testMergeUnmodified_WithDots_ExistedKey()
    {
        $structure = new Structure;
        $structure->mergeUnmodified(array('param' => array('sub1' => 'value1')));
        $structure->mergeUnmodified(array('param' => array('sub2' => 'value2')));

        $this->assertEquals(array(
            'param' => array(
                'sub1' => 'value1',
                'sub2' => 'value2',
            )
        ), $structure->toArray());
    }

    public function testMergeModified_WithoutDots_NewKey()
    {
        $structure = new Structure;
        $structure->merge(array('param1' => 'value1'));
        $structure->merge(array('param2' => 'value2'));

        $this->assertEquals(array(
            'param1' => 'value1',
            'param2' => 'value2',
        ), $structure->toArray());

        $this->assertTrue($structure->isModified('param1'));
        $this->assertTrue($structure->isModified('param2'));
    }

    public function testMergeModified_WithDots_NewKey()
    {
        $structure = new Structure;
        $structure->merge(array('param1' => array('sub1' => 'value1')));
        $structure->merge(array('param2' => array('sub2' => 'value2')));

        $this->assertEquals(array(
            'param1' => array('sub1' => 'value1'),
            'param2' => array('sub2' => 'value2'),
        ), $structure->toArray());

        $this->assertTrue($structure->isModified('param1'));
        $this->assertTrue($structure->isModified('param2'));
    }

    public function testMergeModified_WithoutDots_ExistedKey()
    {
        $structure = new Structure;
        $structure->merge(array('param' => 'value1'));
        $structure->merge(array('param' => 'value2'));

        $this->assertEquals(array(
            'param' => 'value2',
        ), $structure->toArray());

        $this->assertTrue($structure->isModified('param'));
    }

    public function testMergeModified_WithDots_ExistedKey()
    {
        $structure = new Structure;
        $structure->merge(array('param' => array('sub1' => 'value1')));
        $structure->merge(array('param' => array('sub2' => 'value2')));

        $this->assertEquals(array(
            'param' => array(
                'sub1' => 'value1',
                'sub2' => 'value2',
            )
        ), $structure->toArray());

        $this->assertTrue($structure->isModified('param'));
    }

    public function testLoad_setModifiedWithoutDots()
    {
        $structure = new Structure;

        $structure->load(array(
            'param' => 'value',
        ), true);

        $this->assertEquals('value', $structure->get('param'));

        $this->assertTrue($structure->isModified('param'));
    }

    public function testLoad_setModifiedWithDots()
    {
        $structure = new Structure;

        $structure->load(array(
            'param' => array(
                'subparam' => 'value',
            )
        ), true);

        $this->assertEquals('value', $structure->get('param.subparam'));
        return;

        $this->assertTrue($structure->isModified('param.subparam'));
    }

    public function testLoad_setUnmodifiedWithoutDots()
    {
        $structure = new Structure;

        $structure->load(array(
            'param' => 'value',
        ), false);

        $this->assertEquals('value', $structure->get('param'));

        $this->assertFalse($structure->isModified('param'));
    }

    public function testLoad_setUnmodifiedWithDots()
    {
        $structure = new Structure;

        $structure->load(array(
            'param' => array(
                'subparam' => 'value',
            )
        ), false);

        $this->assertEquals('value', $structure->get('param.subparam'));

        $this->assertFalse($structure->isModified('param.subparam'));
    }
}