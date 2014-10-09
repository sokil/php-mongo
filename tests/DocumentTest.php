<?php

namespace Sokil\Mongo;

class DocumentMock extends \Sokil\Mongo\Document
{
    protected $_data = array(
        'status' => 'ACTIVE',
        'profile' => array(
            'name' => 'USER_NAME',
            'birth' => array(
                'year' => 1984,
                'month' => 08,
                'day' => 10,
            )
        ),
    );
}

class DocumentTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private static $collection;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        self::$collection = $database->getCollection('phpmongo_test_collection');
    }
    
    public function setUp() {
    }
    
    public function tearDown() {

    }
    
    public static function tearDownAfterClass() {
        self::$collection->delete();
    }
    
    public function testReset()
    {
        $document = self::$collection
            ->createDocument(array(
                'param1'    => 'value1'
            ))
            ->save();
        
        $document->param1 = 'changedValue';
        
        $this->assertEquals('changedValue', $document->get('param1'));
        
        $document->reset();
        
        $this->assertEquals('value1', $document->get('param1'));
    }
    
    public function testToString()
    {
        $document = self::$collection->createDocument(array(
            'param1'    => 'value1'
        ));
        
        self::$collection->saveDocument($document);
        
        $this->assertEquals((string) $document, $document->getId());
    }
    
    public function testVirtualGetter()
    {
        $document = self::$collection->createDocument(array(
            'param' => 'value',
        ));
        
        $this->assertEquals('value', $document->getParam());
        
        $this->assertEquals(null, $document->getUnexistedParam());
    }
    
    public function testVirtualSetter()
    {
        $document = self::$collection->createDocument(array(
            'param' => 'value',
        ));
        
        $document->setParam('newValue');
        
        $this->assertEquals('newValue', $document->get('param'));
    }
    
    /**
     * Test call of method not described in behaviors, not setter and not getter
     * @expectedException \Exception
     * @expectedExceptionMessage Document has no method "unexistedMethod"
     */
    public function testUnhandledMethodCall()
    {
        $document = self::$collection->createDocument(array(
            'param' => 'value',
        ));
        
        $document->unexistedMethod();
    }
        
    public function testCreateDocumentFromArray()
    {
        $document = self::$collection->createDocument(array(
            'param1'    => 'value1',
            'param2'    => array(
                'param21'   => 'value21',
                'param22'   => 'value22',
            )
        ));
        
        $this->assertEquals('value1', $document->get('param1'));
        $this->assertEquals('value22', $document->get('param2.param22'));
    }

    public function testDefineId_AsMongoIdClass()
    {
        $id = new \MongoId();
        $document = self::$collection->createDocument()->defineId($id);
        $this->assertEquals($id, $document->getId());
    }

    public function testDefineId_AsStringThatCanBeMongoIdClass()
    {
        $id = '541073e62de5725a2a8b4567';
        $document = self::$collection->createDocument()->defineId($id);
        $this->assertEquals($id, (string) $document->getId());
    }

    public function testDefineId_AsVarchar()
    {
        $id = 'i_am_id';
        $document = self::$collection->createDocument()->defineId($id);
        $this->assertEquals($id, $document->getId());
    }

    public function testSetId_AsMongoIdClass()
    {
        // save document
        $id = new \MongoId();
        
        $doc = self::$collection->createDocument(array('a' => 'a'));
        $doc->setId($id);
        self::$collection->saveDocument($doc);
        
        // find document
        $this->assertNotEmpty(self::$collection->getDocument($id));
        
        // delete document
        self::$collection->deleteDocument($doc);
    }

    public function testSetId_AsStringThatCanBeMongoIdClass()
    {
        // save document
        $id = '541073e62de5725a2a8b4567';

        $doc = self::$collection->createDocument(array('a' => 'a'));
        $doc->setId($id);
        self::$collection->saveDocument($doc);

        // find document
        $this->assertNotEmpty(self::$collection->getDocument($id));

        // delete document
        self::$collection->deleteDocument($doc);
    }

    public function testSetId_AsVarchar()
    {
        // save document
        $id = 'im_a_key';

        $doc = self::$collection->createDocument(array('a' => 'a'));
        $doc->setId($id);
        self::$collection->saveDocument($doc);

        // find document
        $this->assertNotEmpty(self::$collection->getDocument($id));

        // delete document
        self::$collection->deleteDocument($doc);
    }
    
    public function testIsStored()
    {
        // not stored
        $document = self::$collection->createDocument(array('k' => 'v'));
        $this->assertFalse($document->isStored());
        
        // stored
        self::$collection->saveDocument($document);
        $this->assertTrue($document->isStored());
    }
    
    public function testIsStoredWhenIdSet()
    {
        // not stored
        $document = self::$collection->createDocument(array('k' => 'v'));
        $document->setId(new \MongoId);
        $this->assertFalse($document->isStored());
        
        // stored
        self::$collection->saveDocument($document);
        $this->assertTrue($document->isStored());
    }
    
    public function testSet()
    {
        $document = self::$collection->createDocument(array(
            'param' => 'value',
        ));
        
        $document->set('a.b.c', 'value1');
        $this->assertEquals('value1', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value1'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value1')), $document->get('a'));
        
        $document->set('a.b.c', 'value2');
        $this->assertEquals('value2', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value2'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value2')), $document->get('a'));
        
        self::$collection->saveDocument($document);
        $document = self::$collection->getDocument($document->getId());
        
        $document->set('a.b.c', 'value1');
        $this->assertEquals('value1', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value1'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value1')), $document->get('a'));
        
        $document->set('a.b.c', 'value2');
        $this->assertEquals('value2', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value2'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value2')), $document->get('a'));
    }

    public function testSetObject()
    {
        $obj = new \stdclass;
        $obj->param = 'value';
        
        // save
        $document = self::$collection->createDocument()
            ->set('d', $obj)
            ->save();
        
        $this->assertEquals(
            (array) $obj, 
            $document->d
        );
        
        $this->assertEquals(
            (array) $obj, 
            self::$collection->getDocumentDirectly($document->getId())->d
        );
    }
    
    public function testSetDate()
    {
        $date = new \MongoDate;
        
        // save
        $document = self::$collection->createDocument()
            ->set('d', $date)
            ->save();
        
        $this->assertEquals(
            $date, 
            $document->d
        );
        
        $this->assertEquals(
            $date, 
            self::$collection->getDocumentDirectly($document->getId())->d
        );
    }
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testSetArrayToScalarOnNewDoc()
    {
        $doc = self::$collection->createDocument(array(
            'a' => 1,
        ));
        
        $doc->set('a.b', 2);
        $this->assertEquals(array('a' => array('b' => 2)), $doc->toArray());
    }
    
    public function testSetMongoCode()
    {
        $doc = self::$collection->createDocument(array(
            'code'  => new \MongoCode('Math.sin(45);'),
        ))->save();
        
        $this->assertInstanceOf(
            '\MongoCode',
            self::$collection->getDocumentDirectly($doc->getId())->code
        );
    }
    
    public function testSetMongoRegex()
    {
        $doc = self::$collection->createDocument(array(
            'code'  => new \MongoRegex('/[a-z]/'),
        ))->save();
        
        $this->assertInstanceOf(
            '\MongoRegex',
            self::$collection->getDocumentDirectly($doc->getId())->code
        );
    }
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testSetArrayToScalarOnExistedDoc()
    {
        $doc = self::$collection
            ->createDocument(array(
                'a' => 1,
            ))
            ->save();
        
        $doc->set('a.b', 2)->save();
    }

    public function testSetScenario()
    {
        $document = self::$collection->createDocument();

        $document->setScenario('SOME_SCENARIO');
        $this->assertEquals('SOME_SCENARIO', $document->getScenario());

        $this->assertTrue($document->isScenario('SOME_SCENARIO'));
        $this->assertFalse($document->isScenario('SOME_WRONG_SCENARIO'));

        $document->setNoScenario();
        $this->assertNull($document->getScenario());
    }
    
    public function testGetNull()
    {
        //save 
        $document = self::$collection
            ->createDocument(array(
                'field1' => null,
                'field2' => array(
                    'subfield' => null
                ),
            ))
            ->save();
        
        // get document
        $document = self::$collection->getDocumentDirectly($document->getId());
        
        $this->assertTrue($document->has('field1'));
        $this->assertNull($document->get('field1'));
        
        $this->assertTrue($document->has('field2.subfield'));
        $this->assertNull($document->get('field1.subfield'));
    }
        
    public function testUnsetInNewDocument()
    {
        $doc = self::$collection->createDocument(array(
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
        
        $doc->unsetField('a.a2.a21');
        
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
        ), $doc->toArray());
    }
    
    public function testUnsetInExistedDocument()
    {
        $doc = self::$collection
            ->createDocument(array(
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
            ))
            ->save();
        
        $doc->unsetField('a.a2.a21')->save();
        
        $data = $doc->toArray();
        unset($data['_id']);
        
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
        ), $data);
    }
    
    public function testUnsetAndSet()
    {
        $document = self::$collection
            ->createDocument(array(
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
            ))
            ->save();
        
        $documentId = $document->getId();
        
        $document
            ->set('b', 'b')
            ->unsetField('a.a2.a21')
            ->save();
        
        $document = self::$collection
            ->getDocumentDirectly($documentId);
        
        $documentData = $document->toArray();
        unset($documentData['_id']);
        
        $this->assertEquals(array(
            'a' => array(
                'a1'    => array(
                    'a11'   => 1,
                    'a12'   => 2,
                ),
                'a2'    => array(
                    'a22'   => 2,
                ),
            ),
            'b' => 'b'
        ), $documentData);
    }

    public function testUnsetUnexistedField()
    {
        $document = self::$collection->createDocument();
        $document->unsetField('unexistedField');
    }
    
    public function testAppend()
    {
        $document = self::$collection->createDocument(array(
            'param' => 'value',
        ));
        
        $document->append('a.b.c', 'value1');
        $this->assertEquals('value1', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value1'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value1')), $document->get('a'));
        
        $document->append('a.b.c', 'value2');
        $this->assertEquals(array('value1', 'value2'), $document->get('a.b.c'));
        $this->assertEquals(array('c' => array('value1', 'value2')), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => array('value1', 'value2'))), $document->get('a'));
        
        self::$collection->saveDocument($document);
        $document = self::$collection->getDocument($document->getId());
        
        $document->append('a.b.c', 'value3');
        $this->assertEquals(array('value1', 'value2', 'value3'), $document->get('a.b.c'));
        $this->assertEquals(array('c' => array('value1', 'value2', 'value3')), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => array('value1', 'value2', 'value3'))), $document->get('a'));
    }

    public function testDecrement()
    {
        /**
         * Increment unsaved
         */
        $doc = self::$collection
            ->createDocument(array('i' => 10));

        // increment
        $doc->decrement('j', 2);
        $doc->decrement('j', 4);

        // test
        $this->assertEquals(-6, $doc->get('j'));

        // save
        $doc->save();

        /**
         * Test increment of document in cache
         */
        $doc = self::$collection->getDocument($doc->getId());
        $this->assertEquals(-6, $doc->get('j'));

        /**
         * Test increment after reread from db
         */
        $doc = self::$collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(-6, $doc->get('j'));
    }

    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementNotExistedKeyOfUnsavedDocument()
    {
        /**
         * Increment unsaved
         */
        $doc = self::$collection
            ->createDocument(array('i' => 1));
        
        // increment
        $doc->increment('j', 2);
        $doc->increment('j', 4);
        
        // test
        $this->assertEquals(6, $doc->get('j'));
        
        // save
        $doc->save();
        
        /**
         * Test increment of document in cache
         */
        $doc = self::$collection->getDocument($doc->getId());
        $this->assertEquals(6, $doc->get('j'));
        
        /**
         * Test increment after reread from db
         */
        $doc = self::$collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(6, $doc->get('j'));
    }
    
    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementNotExistedKeyOfSavedDocument()
    {
        $doc = self::$collection
            ->createDocument(array('i' => 1))
            ->save();
        
        /**
         * Increment saved
         */
        $doc->increment('j', 2); // existed key
        $doc->increment('j', 4);
        
        // test
        $this->assertEquals(6, $doc->get('j'));
        
        // save
        $doc->save();
        
        /**
         * Test increment of document in cache
         */
        $doc = self::$collection->getDocument($doc->getId());
        $this->assertEquals(6, $doc->get('j'));
        
        /**
         * Test increment after reread from db
         */
        $doc = self::$collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(6, $doc->get('j'));
    }
    
    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementExistedKeyOfUnsavedDocument()
    {
        /**
         * Increment unsaved
         */
        $doc = self::$collection
            ->createDocument(array('i' => 1));
        
        // increment
        $doc->increment('i', 2);
        $doc->increment('i', 4);
        
        // test
        $this->assertEquals(7, $doc->get('i'));
        
        // save
        $doc->save();
        
        /**
         * Test increment of document in cache
         */
        $doc = self::$collection->getDocument($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
        
        /**
         * Test increment after reread from db
         */
        $doc = self::$collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
    }
    
    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementExistedKeyOfSavedDocument()
    {
        $doc = self::$collection
            ->createDocument(array('i' => 1))
            ->save();
        
        /**
         * Increment saved
         */
        $doc->increment('i', 2); // existed key
        $doc->increment('i', 4);
        
        // test
        $this->assertEquals(7, $doc->get('i'));
        
        // save
        $doc->save();
        
        /**
         * Test increment of document in cache
         */
        $doc = self::$collection->getDocument($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
        
        /**
         * Test increment after reread from db
         */
        $doc = self::$collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
    }
    
    public function testPushNumberToEmptyOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push single to empty
        $doc->push('key', 1);
        $doc->push('key', 2);
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(1, 2), self::$collection->getDocument($doc->getId())->key);
    }

    public function testPushStructure()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));

        $structure = new Structure();
        $structure->mergeUnmodified(array('param' => 'value'));

        $doc->push('field', $structure);
        $doc->push('field', $structure);

        $this->assertEquals(array(
            array('param' => 'value'),
            array('param' => 'value'),
        ), $doc->get('field'));
    }

    public function testPushObjectToEmptyOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        $object1 = new \stdclass;
        $object2 = new \stdclass;
        
        // push single to empty
        $doc->push('key', $object1);
        $doc->push('key', $object2);
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(
            array((array)$object1, (array)$object2), 
            $doc->key
        );
        
        $this->assertEquals(
            array((array)$object1, (array)$object2), 
            self::$collection->getDocumentDirectly($doc->getId())->key
        );
    }
    
    public function testPushMongoIdToEmptyOnExistedDocument()
    {
        // create document
        $doc = self::$collection
            ->createDocument(array(
                'some' => 'some',
            ))
            ->save();
        
        $id = new \MongoId;
        
        // push single to empty
        $doc
            ->push('key', $id)
            ->save();
        
        $this->assertEquals(array($id), $doc->key);
        
        $this->assertEquals(array($id), self::$collection->getDocumentDirectly($doc->getId())->key);
    }
    
    public function testPushArrayToEmptyOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to empty
        $doc->push('key', array(1));
        $doc->push('key', array(2));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(array(1),array(2)), self::$collection->getDocument($doc->getId())->key);
        
    }
    
    public function testPushArrayToEmptyOnNewDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        // push array to empty
        $doc->push('key', array(1));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(array(1)), self::$collection->getDocument($doc->getId())->key);
    }
    
    public function testPushSingleToSingleOnNewDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        // push single to single
        $doc->push('some', 'another1');
        $doc->push('some', 'another2');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another1', 'another2'), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushSingleToSingleOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push single to single
        $doc->push('some', 'another1');
        $doc->push('some', 'another2');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another1', 'another2'), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushArrayToSingleOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to single
        $doc->push('some', array('another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', array('another')), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushArrayToSingleOnNewDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        // push array to single
        $doc->push('some', array('another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', array('another')), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushSingleToArrayOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push single to array
        $doc->push('some', 'some3');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some1', 'some2', 'some3'), self::$collection->getDocument($doc->getId())->some);
        
    }
    
    public function testPushArrayToArrayOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->push('some', array('some3'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some1', 'some2', array('some3')), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushArrayToArrayOnNewDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        // push array to array
        $doc->push('some', array('some3'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some1', 'some2', array('some3')), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushEach_OnUnsavedDocument()
    {
        // create document
        $doc = self::$collection
            ->createDocument(array(
                'some' => 'some',
            ));

        // push single to empty
        $doc->pushEach('key', array(1, 2));
        $doc->push('key', 3);
        $doc->pushEach('key', array(4, 5));

        $this->assertEquals(array(1, 2, 3, 4, 5), $doc->key);

        self::$collection->saveDocument($doc);

        $this->assertEquals(
            array(1, 2, 3, 4, 5),
            self::$collection->getDocumentDirectly($doc->getId())->key
        );
    }

    public function testPushEach_OnSavedDocument()
    {
        // create document
        $doc = self::$collection
            ->createDocument(array(
                'some' => 'some',
            ))
        ->save();

        // push single to empty
        $doc->pushEach('key', array(1, 2));
        $doc->push('key', 3);
        $doc->pushEach('key', array(4, 5));

        $this->assertEquals(array(1, 2, 3, 4, 5), $doc->key);

        self::$collection->saveDocument($doc);

        $this->assertEquals(
            array(1, 2, 3, 4, 5),
            self::$collection->getDocumentDirectly($doc->getId())->key
        );
    }
    
    public function testPushFromArray_ToEmpty_OnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to empty
        $doc->pushFromArray('key', array(1));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(1), self::$collection->getDocument($doc->getId())->key);
        
    }
    
    public function testPushFromArray_ToSingle_OnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to single
        $doc->pushFromArray('some', array('another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another'), self::$collection->getDocument($doc->getId())->some);
        
    }
    
    public function testPushFromArray_ToArray_OnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->pushFromArray('some', array('some3'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some1', 'some2', 'some3'), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPullFromOneDimensionalArray()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->pull('some', 'some2');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(
            array('some1'), 
            $doc->some
        );
        
        $this->assertEquals(
            array('some1'), 
            self::$collection->getDocument($doc->getId())->some
        );
    }
    
    public function testPullFromTwoDimensionalArray()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array(
                array('sub'  => 1), 
                array('sub'  => 2)
            ),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->pull('some', array(
            'sub'  => 2
        ));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(array('sub' => 1)), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPullFromThreeDimensionalArray()
    {
        self::$collection->delete();
        
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array(
                array(
                    'sub'  => array(
                        array('a' => 1),
                        array('b' => 2),
                    )
                ),
                array(
                    'sub'  => array(
                        array('a' => 3),
                        array('b' => 4),
                    )
                )
            ),
        ));
        self::$collection->saveDocument($doc);
        
        // pull 1
        $doc->pull('some', array(
            'sub.a'  => 1
        ));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(
            array(
                'sub'  => array(
                    array('a' => 3),
                    array('b' => 4),
                )
            )
        ), self::$collection->getDocumentDirectly($doc->getId())->some);
        
        // pull 2
        $doc->pull('some', array(
            'sub'  => array(
                'a' => 3,
            )
        ));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(), self::$collection->getDocumentDirectly($doc->getId())->some);
    }
    
    public function testPullFromThreeDimensionalUsingExpressionArray()
    {
        self::$collection->delete();
        
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array(
                array(
                    'sub'  => array(
                        array('a' => 1),
                        array('b' => 2),
                    )
                ),
                array(
                    'sub'  => array(
                        array('a' => 3),
                        array('b' => 4),
                    )
                )
            ),
        ));
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->pull('some', self::$collection->expression()->where('sub.a', 1));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(
            array(
                'sub'  => array(
                    array('a' => 3),
                    array('b' => 4),
                )
            )
        ), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testMergeOnUpdate()
    {
        // save document
        $document = self::$collection
            ->createDocument(array(
                'p' => 'pv',
            ))
            ->save();
           
        // update document
        $document
            ->set('f1', 'fv1')
            ->merge(array(
                'a1'    => 'av1',
                'a2'    => 'av2',
            ));
        
        $documentData = $document->toArray();
        unset($documentData['_id']);
        
        $this->assertEquals(array(
            'p'     => 'pv',
            'f1'    => 'fv1',
            'a1'    => 'av1',
            'a2'    => 'av2',
        ), $documentData);
        
       $document->save();
        
        // test
        $foundDocumentData = self::$collection
            ->getDocumentDirectly($document->getId())
            ->toArray();
        
        unset($foundDocumentData['_id']);
        
        $this->assertEquals(array(
            'p'     => 'pv',
            'f1'    => 'fv1',
            'a1'    => 'av1',
            'a2'    => 'av2',
        ), $foundDocumentData);
    }
    
    public function testDefaultFields()
    {
        $document = new DocumentMock(self::$collection);
        
        $this->assertEquals('ACTIVE', $document->status);
    }

    public function testRedefineDefaultFieldsInConstructor()
    {
        $document = new DocumentMock(self::$collection, array(
            'balance' => 123, // not existed key
            'status' => 'DELETED', // update value
            'profile' => array(
                'name'  => 'UPDATED_NAME',
                'birth'   => array(
                    'day'   => 11,
                ),
            ),
        ));
        
        $this->assertEquals(123, $document->get('balance'));
        $this->assertEquals('DELETED', $document->get('status'));
        
        $this->assertEquals('UPDATED_NAME', $document->get('profile.name'));
        
        $this->assertEquals(1984, $document->get('profile.birth.year'));
        $this->assertEquals(11, $document->get('profile.birth.day'));
    }

    public function testFromArray()
    {
        $document = new DocumentMock(self::$collection);

        $document->fromArray(array(
            'balance' => 123, // not existed key
            'status' => 'DELETED', // update value
            'profile' => array(
                'name'  => 'UPDATED_NAME',
                'birth'   => array(
                    'day'   => 11,
                ),
            ),
        ));

        $this->assertEquals(123, $document->get('balance'));
        $this->assertEquals('DELETED', $document->get('status'));

        $this->assertEquals('UPDATED_NAME', $document->get('profile.name'));

        $this->assertEquals(1984, $document->get('profile.birth.year'));
        $this->assertEquals(11, $document->get('profile.birth.day'));
    }

    public function testMerge()
    {
        $document = new DocumentMock(self::$collection);
        
        $document->merge(array(
            'balance' => 123, // not existed key
            'status' => 'DELETED', // update value
            'profile' => array(
                'name'  => 'UPDATED_NAME',
                'birth'   => array(
                    'day'   => 11,
                ),
            ),
        ));
        
        $this->assertEquals(123, $document->get('balance'));
        $this->assertEquals('DELETED', $document->get('status'));
        
        $this->assertEquals('UPDATED_NAME', $document->get('profile.name'));
        
        $this->assertEquals(1984, $document->get('profile.birth.year'));
        $this->assertEquals(11, $document->get('profile.birth.day'));
    }

    /**
     * Check of nothing changed on document whan save not required
     */
    public function testSave_SaveNotRequired()
    {
        $document = self::$collection->createDocument(array('p' => 'v'));
        $document->save();

        $this->assertFalse($document->isSaveRequired());
        $this->assertTrue($document->isStored());

        $savedDocument = clone $document;
        $document->save();

        $this->assertEquals($savedDocument, $document);
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Update error: some_strange_error: Some strange error
     */
    public function testSave_UpdateError()
    {
        $mongoCollectionMock = $this->getMock(
            '\MongoCollection',
            array('update'),
            array(
                self::$collection->getDatabase()->getMongoDb(),
                'phpmongo_test_collection'
            )
        );

        $mongoCollectionMock
            ->expects($this->once())
            ->method('update')
            ->will($this->returnValue(array(
                'ok'        => (double) 0,
                'err'       => 'some_strange_error',
                'errmsg'    => 'Some strange error',
            )));

        $collection = new Collection(
            self::$collection->getDatabase(),
            $mongoCollectionMock
        );

        // create document
        $document = $collection->createDocument(array('p' => 'v'))->save();

        // update document with error
        $document->set('p', 'v1')->save();
    }
}