<?php

namespace Sokil\Mongo;

use Sokil\Mongo\DocumentTest\DeprecatedSchemaDocumentStub;
use Sokil\Mongo\DocumentTest\DocumentStub;

class DocumentTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp()
    {
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
        $database = $client->getDatabase('test');
        $this->collection = $database
            ->getCollection('phpmongo_test_collection')
            ->delete();
    }

    public function tearDown()
    {
        $this->collection->delete();
    }

    public function testReset()
    {
        $document = $this->collection
            ->createDocument(array(
                'param1'    => 'value1'
            ))
            ->save();

        $document->param1 = 'changedValue';

        $this->assertEquals('changedValue', $document->get('param1'));

        $document->reset();

        $this->assertEquals('value1', $document->get('param1'));
    }

    public function testReload()
    {
        // Create document
        $id = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save()
            ->getId();

        // Load two unreferenced copies of same document
        $document1 = $this->collection->getDocumentDirectly($id);
        $document2 = $this->collection->getDocumentDirectly($id);

        // store changes on one of them
        $document1
            ->set('param', 'updatedValue')
            ->save();

        // Changes not exists in seconde document before refresh
        $this->assertEquals('value', $document2->param);

        // Document 2 is in not-saved state.
        // After refresh all not saved changes reset
        $document2
            ->set('param', 'someParalelUpdatedButNotSavedValue')
            ->increment('newField');

        // refresh data
        $document2->refresh();

        // now data is fresh
        $this->assertEquals('updatedValue', $document2->param);
        $this->assertEquals(array(), $document2->getOperator()->toArray());
        $this->assertNull($document2->newField);
    }

    public function testToString()
    {
        $document = $this->collection->createDocument(array(
            'param1'    => 'value1'
        ));

        $document->save();

        $this->assertEquals((string) $document, $document->getId());
    }

    public function testVirtualGetter()
    {
        $document = $this->collection->createDocument(array(
            'param' => 'value',
        ));

        $this->assertEquals('value', $document->getParam());

        $this->assertEquals(null, $document->getUnexistedParam());
    }

    public function testVirtualSetter()
    {
        $document = $this->collection->createDocument(array(
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
        $document = $this->collection->createDocument(array(
            'param' => 'value',
        ));

        $document->unexistedMethod();
    }

    public function testCreateDocumentFromArray()
    {
        $document = $this->collection->createDocument(array(
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
        $document = $this->collection->createDocument()->defineId($id);
        $this->assertEquals($id, $document->getId());
    }

    public function testDefineId_AsStringThatCanBeMongoIdClass()
    {
        $id = '541073e62de5725a2a8b4567';
        $document = $this->collection->createDocument()->defineId($id);
        $this->assertEquals($id, (string) $document->getId());
    }

    public function testDefineId_AsVarchar()
    {
        $id = 'i_am_id';
        $document = $this->collection->createDocument()->defineId($id);
        $this->assertEquals($id, $document->getId());
    }

    public function testSetId_AsMongoIdClass()
    {
        // save document
        $id = new \MongoId();

        $doc = $this->collection->createDocument(array('a' => 'a'));
        $doc->setId($id);
        $doc->save();

        // find document
        $this->assertNotEmpty($this->collection->getDocument($id));

        // delete document
        $this->collection->deleteDocument($doc);
    }

    public function testSetId_AsStringThatCanBeMongoIdClass()
    {
        // save document
        $id = '541073e62de5725a2a8b4567';

        $doc = $this->collection->createDocument(array('a' => 'a'));
        $doc->setId($id);
        $doc->save();

        // find document
        $this->assertNotEmpty($this->collection->getDocument($id));

        // delete document
        $this->collection->deleteDocument($doc);
    }

    public function testSetId_AsVarchar()
    {
        // save document
        $id = 'im_a_key';

        $doc = $this->collection->createDocument(array('a' => 'a'));
        $doc->setId($id);
        $doc->save();

        // find document
        $this->assertNotEmpty($this->collection->getDocument($id));

        // delete document
        $this->collection->deleteDocument($doc);
    }

    public function testIsStored()
    {
        // not stored
        $document = $this->collection->createDocument(array('k' => 'v'));
        $this->assertFalse($document->isStored());

        // stored
        $document->save();
        $this->assertTrue($document->isStored());
    }

    public function testIsStoredWhenIdSet()
    {
        // not stored
        $document = $this->collection->createDocument(array('k' => 'v'));
        $document->setId(new \MongoId);
        $this->assertFalse($document->isStored());

        // stored
        $document->save();
        $this->assertTrue($document->isStored());
    }

    public function testSet_NewDocument()
    {
        $document = $this->collection
            ->createDocument(array(
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
    }

    public function testSet_NewKey_StoredDocument()
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ))
            ->save();

        // update existed document
        $document->set('a', 'value')->save();

        // get document from db
        $document = $this->collection->getDocumentDirectly($document->getId());
        $this->assertEquals('value', $document->get('a'));
    }

    public function testSet_ExistedKey_StoredDocument()
    {
        $document = $this->collection
            ->createDocument(array(
                'a' => 'value1',
            ))
            ->save();

        // update existed document
        $document->set('a', 'value2')->save();

        // get document from db
        $document = $this->collection->getDocumentDirectly($document->getId());
        $this->assertEquals('value2', $document->get('a'));
    }

    public function testSet_SubkeyOfNewKey_StoredDocument()
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 1,
            ))
            ->save();

        // update existed document
        $document->set('a.b', 'value2')->save();

        // get document from db
        $document = $this->collection->getDocumentDirectly($document->getId());
        $this->assertEquals('value2', $document->get('a.b'));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Assigning sub-document to scalar value not allowed
     */
    public function testSet_NewScalarSubkeyOfExistedScalarKey_StoredDocument()
    {
        $document = $this->collection
            ->createDocument(array(
                'a' => 1,
                'b' => 2,
            ))
            ->save();

        // update existed document
        $document->set('a.b.c', 'value')->save();
    }

    public function testSet_NewScalarSubkeyOfExistedArrayKey_StoredDocument()
    {
        $document = $this->collection
            ->createDocument(array(
                'a' => array(
                    'z' => 1,
                ),
                'b' => 2,
            ))
            ->save();

        // update existed document
        $document->set('a.b', 'value')->save();

        // get document from db
        $document = $this->collection->getDocumentDirectly($document->getId());
        $data = $document->toArray();
        unset($data['_id']);

        $this->assertEquals(array(
            'a' => array(
                'b' => 'value',
                'z' => 1
            ),
            'b' => 2,
        ), $data);
    }

    public function testSet_ExistedScalarSubkeyOfExistedArrayKey_StoredDocument()
    {
        $document = $this->collection
            ->createDocument(array(
                'a' => array(
                    'b' => array('some' => 'value'),
                ),
                'b' => 2,
            ))
            ->save();

        // update existed document
        $document->set('a.b', 'value')->save();

        // get document from db
        $document = $this->collection->getDocumentDirectly($document->getId());
        $data = $document->toArray();
        unset($data['_id']);

        $this->assertEquals(array(
            'a' => array(
                'b' => 'value',
            ),
            'b' => 2,
        ), $data);
    }

    public function testSet_SubkeyOverwrite_NewDocument()
    {
        /**
         * Modify new document
         */
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ))
            ->set('driving', array('license' => 1, 'other' => 'field'))
            ->set('driving.license', 2);

        $this->assertEquals(array(
            'param' => 'value',
            'driving' => array(
                'other' => 'field',
                'license' => 2,
            )
        ), $document->toArray());

        /**
         * Save new document
         */
        $document->save();

        $data = $this->collection->getDocumentDirectly($document->getId())->toArray();
        unset($data['_id']);
        
        $this->assertEquals(
            array(
                'param' => 'value',
                'driving' => array(
                    'other' => 'field',
                    'license' => 2,
                )
            ),
            $data
        );
    }

    /**
     * @todo now test fails because second set not overwrite first and occured exception:
     * 
     * MongoWriteConcernException: 127.0.0.1:27017: Cannot update 'driving' and 'driving.license' at the same time
     * Need implementation of overwritting values
     */
    public function testSet_SubkeyOverwrite_StoredDocument()
    {
        $this->markTestSkipped("Cannot update 'driving' and 'driving.license' at the same time");
        /**
         * Modify existed document
         */
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ))
            ->save()
            ->set('driving', array('license' => 1, 'other' => 'field'))
            ->set('driving.license', 2);

        $data = $document->toArray();
        unset($data['_id']);
        
        $this->assertEquals(array(
            'param' => 'value',
            'driving' => array(
                'other' => 'field',
                'license' => 2,
            )
        ), $data);

        /**
         * Save new document
         */
        $document->save();

        $data = $this->collection->getDocumentDirectly($document->getId())->toArray();
        unset($data['_id']);

        $this->assertEquals(
            array(
                'param' => 'value',
                'driving' => array(
                    'other' => 'field',
                    'license' => 2,
                )
            ),
            $data
        );
    }

    public function testSetStructure_NewDocument()
    {
        $obj = new Structure;
        $obj->param = 'value';

        // save
        $document = $this->collection->createDocument()
            ->set('d', $obj)
            ->save();

        $this->assertEquals(
            array('param' => 'value'),
            $document->d
        );

        $this->assertEquals(
            array('param' => 'value'),
            $this->collection->getDocumentDirectly($document->getId())->d
        );
    }

    public function testSetStructure_StoredDocument()
    {
        $document = $this->collection->createDocument(array(
            'some' => 'field',
        ))->save();

        $obj = new Structure;
        $obj->param = 'value';

        // save
        $document->set('d', $obj)->save();

        $this->assertEquals(
            array('param' => 'value'),
            $document->d
        );

        $this->assertEquals(
            array('param' => 'value'),
            $this->collection->getDocumentDirectly($document->getId())->d
        );
    }

    public function testSetObject()
    {
        $obj = new \stdclass;
        $obj->param = 'value';

        // save
        $document = $this->collection->createDocument()
            ->set('d', $obj)
            ->save();

        $this->assertEquals(
            (array) $obj,
            $document->d
        );

        $this->assertEquals(
            (array) $obj,
            $this->collection->getDocumentDirectly($document->getId())->d
        );
    }

    public function testSetDate()
    {
        $date = new \MongoDate;

        // save
        $document = $this->collection->createDocument()
            ->set('d', $date)
            ->save();

        $this->assertEquals(
            $date,
            $document->d
        );

        $this->assertEquals(
            $date,
            $this->collection->getDocumentDirectly($document->getId())->d
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testSetArrayToScalarOnNewDoc()
    {
        $doc = $this->collection->createDocument(array(
            'a' => 1,
        ));

        $doc->set('a.b', 2);
        $this->assertEquals(array('a' => array('b' => 2)), $doc->toArray());
    }

    public function testSetMongoCode()
    {
        $doc = $this->collection->createDocument(array(
            'code'  => new \MongoCode('Math.sin(45);'),
        ))->save();

        $this->assertInstanceOf(
            '\MongoCode',
            $this->collection->getDocumentDirectly($doc->getId())->code
        );
    }

    public function testSetMongoRegex()
    {
        $doc = $this->collection->createDocument(array(
            'code'  => new \MongoRegex('/[a-z]/'),
        ))->save();

        $this->assertInstanceOf(
            '\MongoRegex',
            $this->collection->getDocumentDirectly($doc->getId())->code
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testSetArrayToScalarOnExistedDoc()
    {
        $doc = $this->collection
            ->createDocument(array(
                'a' => 1,
            ))
            ->save();

        $doc->set('a.b', 2)->save();
    }

    public function testSetScenario()
    {
        $document = $this->collection->createDocument();

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
        $document = $this->collection
            ->createDocument(array(
                'field1' => null,
                'field2' => array(
                    'subfield' => null
                ),
            ))
            ->save();

        // get document
        $document = $this->collection->getDocumentDirectly($document->getId());

        $this->assertTrue($document->has('field1'));
        $this->assertNull($document->get('field1'));

        $this->assertTrue($document->has('field2.subfield'));
        $this->assertNull($document->get('field1.subfield'));
    }

    public function testUnsetInNewDocument()
    {
        $doc = $this->collection->createDocument(array(
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
        $doc = $this->collection
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
        $document = $this->collection
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

        $document = $this->collection
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
        $document = $this->collection->createDocument();
        $document->unsetField('unexistedField');
    }

    public function testEmptyField()
    {
        $document = $this->collection->createDocument(array(
            'field1' => 'value',
            'field2' => null,
        ));

        $this->assertFalse(empty($document->field1));
        $this->assertTrue(empty($document->field2));
        $this->assertTrue(empty($document->fieldUnexisted));
    }

    public function testUnsetField_NewDocument()
    {
        $document = $this->collection->createDocument(array(
            'field' => 'value',
        ));

        $this->assertEquals('value', $document->field);

        unset($document->field);

        $this->assertEquals(null, $document->field);
    }

    public function testUnsetField_ExistedDocument()
    {
        $document = $this->collection
            ->createDocument(array(
                'field' => 'value',
            ))
            ->save();

        $this->assertEquals('value', $document->field);

        unset($document->field);

        $document->save();

        $this->assertEquals(null, $document->field);

        // reload from db
        $document->refresh();

        $this->assertEquals(null, $document->field);
    }

    public function testPushEach_OnUnsavedDocument()
    {
        // create document
        $doc = $this->collection
            ->createDocument(array(
                'some' => 'some',
            ));

        // push single to empty
        $doc->pushEach('key', array(1, 2));
        $doc->push('key', 3);
        $doc->pushEach('key', array(4, 5));

        $this->assertEquals(array(1, 2, 3, 4, 5), $doc->key);

        $doc->save();

        $this->assertEquals(
            array(1, 2, 3, 4, 5),
            $this->collection->getDocumentDirectly($doc->getId())->key
        );
    }

    public function testPushEach_OnSavedDocument()
    {
        // create document
        $doc = $this->collection
            ->createDocument(array(
                'some' => 'some',
            ))
            ->save();

        // push single to empty
        $doc->pushEach('key', array(1, 2));
        $doc->push('key', 3);
        $doc->pushEach('key', array(4, 5));

        $this->assertEquals(array(1, 2, 3, 4, 5), $doc->key);

        $doc->save();

        $this->assertEquals(
            array(1, 2, 3, 4, 5),
            $this->collection->getDocumentDirectly($doc->getId())->key
        );
    }

    public function testAppend()
    {
        $document = $this->collection->createDocument(array(
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

        $document->save();
        $document = $this->collection->getDocument($document->getId());

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
        $doc = $this->collection
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
        $doc = $this->collection->getDocument($doc->getId());
        $this->assertEquals(-6, $doc->get('j'));

        /**
         * Test increment after reread from db
         */
        $doc = $this->collection->getDocumentDirectly($doc->getId());
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
        $doc = $this->collection
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
        $doc = $this->collection->getDocument($doc->getId());
        $this->assertEquals(6, $doc->get('j'));

        /**
         * Test increment after reread from db
         */
        $doc = $this->collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(6, $doc->get('j'));
    }

    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementNotExistedKeyOfSavedDocument()
    {
        $doc = $this->collection
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
        $doc = $this->collection->getDocument($doc->getId());
        $this->assertEquals(6, $doc->get('j'));

        /**
         * Test increment after reread from db
         */
        $doc = $this->collection->getDocumentDirectly($doc->getId());
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
        $doc = $this->collection
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
        $doc = $this->collection->getDocument($doc->getId());
        $this->assertEquals(7, $doc->get('i'));

        /**
         * Test increment after reread from db
         */
        $doc = $this->collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
    }

    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementExistedKeyOfSavedDocument()
    {
        $doc = $this->collection
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
        $doc = $this->collection->getDocument($doc->getId());
        $this->assertEquals(7, $doc->get('i'));

        /**
         * Test increment after reread from db
         */
        $doc = $this->collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
    }

    public function testAddToSet_NoKey()
    {
        // create document
        $doc = $this->collection->createDocument(array(
            'some' => 'some',
        ));
        $doc->save();

        // before save
        $doc->addToSet('param', 42);
        $this->assertEquals(array(42), $doc->param);

        // after save
        $doc->save();
        $this->assertEquals(
            array(42),
            $this->collection->getDocumentDirectly($doc->getId())->param
        );
    }

    public function testAddToSet_ScalarKey()
    {
        // create document
        $doc = $this->collection->createDocument(array(
            'param' => 41,
        ));
        $doc->save();
        $docId = $doc->getId();

        // befire save
        $doc->addToSet('param', 42);
        $this->assertEquals(array(41, 42), $doc->param);

        // after save
        $doc->save();
        $doc = $this->collection->getDocumentDirectly($docId);
        $this->assertEquals(
            array(41, 42),
            $doc->param
        );
    }

    public function testPullFromOneDimensionalArray()
    {
        // create document
        $doc = $this->collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));

        $doc->save();

        // push array to array
        $doc->pull('some', 'some2');
        $doc->save();

        $this->assertEquals(
            array('some1'),
            $doc->some
        );

        $this->assertEquals(
            array('some1'),
            $this->collection->getDocument($doc->getId())->some
        );
    }

    public function testPullFromTwoDimensionalArray()
    {
        // create document
        $doc = $this->collection->createDocument(array(
            'some' => array(
                array('sub'  => 1),
                array('sub'  => 2)
            ),
        ));

        $doc->save();

        // push array to array
        $doc->pull('some', array(
            'sub'  => 2
        ));
        $doc->save();

        $this->assertEquals(array(array('sub' => 1)), $this->collection->getDocument($doc->getId())->some);
    }

    public function testPullFromThreeDimensionalArray()
    {
        $this->collection->delete();

        // create document
        $doc = $this->collection->createDocument(array(
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
        $doc->save();

        // pull 1
        $doc->pull('some', array(
            'sub.a'  => 1
        ));
        $doc->save();

        $this->assertEquals(array(
            array(
                'sub'  => array(
                    array('a' => 3),
                    array('b' => 4),
                )
            )
        ), $this->collection->getDocumentDirectly($doc->getId())->some);

        // pull 2
        $doc->pull('some', array(
            'sub'  => array(
                'a' => 3,
            )
        ));
        $doc->save();

        $this->assertEquals(array(), $this->collection->getDocumentDirectly($doc->getId())->some);
    }

    /**
     * @deprecated using expression as value
     */
    public function testPullFromThreeDimensionalUsingExpression()
    {
        $this->collection->delete();

        // create document
        $doc = $this->collection->createDocument(array(
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
        $doc->save();

        // push array to array
        $doc->pull(function($e) {
            $e->where('some', array('sub' => array('a' => 1)));
        });

        $doc->save();

        $this->assertEquals(array(
            array(
                'sub'  => array(
                    array('a' => 3),
                    array('b' => 4),
                )
            )
        ), $this->collection->getDocument($doc->getId())->some);
    }

    public function testPullFromThreeDimensionalUsingExpressionInValue()
    {
        $this->collection->delete();

        // create document
        $doc = $this->collection->createDocument(array(
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
        $doc->save();

        // push array to array
        $doc->pull(
            'some',
            $this->collection->expression()->where('sub.a', 1)
        );

        $doc->save();

        $this->assertEquals(array(
            array(
                'sub'  => array(
                    array('a' => 3),
                    array('b' => 4),
                )
            )
        ), $this->collection->getDocument($doc->getId())->some);
    }

    public function testBitwiceAnd()
    {
        $document = $this
            ->collection
            ->createDocument(array('value' => 5))
            ->save();

        $document
            ->bitwiceAnd('value', 4)
            ->save();

        $id = $document->getId();

        $this->assertEquals(
            4,
            $this->collection->getDocumentDirectly($id)->value
        );
    }

    public function testBitwiceOr()
    {
        $document = $this
            ->collection
            ->createDocument(array('value' => 5))
            ->save();

        $document
            ->bitwiceOr('value', 3)
            ->save();

        $id = $document->getId();

        $this->assertEquals(
            7,
            $this->collection->getDocumentDirectly($id)->value
        );
    }

    public function testBitwiceXor()
    {
        $document = $this
            ->collection
            ->createDocument(array('value' => 5))
            ->save();

        $document
            ->bitwiceXor('value', 3)
            ->save();

        $id = $document->getId();

        $this->assertEquals(
            6,
            $this->collection->getDocumentDirectly($id)->value
        );
    }

    public function testMergeOnUpdate()
    {
        // save document
        $document = $this->collection
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
        $foundDocumentData = $this->collection
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
        $document = new DocumentStub($this->collection);

        $this->assertEquals('ACTIVE', $document->status);
    }

    public function testRedefineDefaultFieldsInConstructor()
    {
        $document = new DocumentStub($this->collection, array(
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
        $document = new DocumentStub($this->collection);

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

    public function testMerge()
    {
        $document = new DocumentStub($this->collection);

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
     * Check of nothing changed on document when save not required
     */
    public function testSave_SaveNotRequired()
    {
        $document = $this->collection->createDocument(array('p' => 'v'));
        $document->save();

        $this->assertFalse($document->isSaveRequired());
        $this->assertTrue($document->isStored());

        $data = $document->toArray();
        $document->save();

        $this->assertEquals($data, $document->toArray());
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
                $this->collection->getDatabase()->getMongoDb(),
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
            $this->collection->getDatabase(),
            $mongoCollectionMock
        );

        // create document
        $document = $collection->createDocument(array('p' => 'v'))->save();

        // update document with error
        $document->set('p', 'v1')->save();
    }

    public function testJsonSerializable()
    {
        $document = $this->collection->createDocument(array(
            'k1' => 'v1'
        ));

        $this->assertEquals(
            array('k1' => 'v1'),
            $document->jsonSerialize()
        );
    }

    public function testGetOriginalData()
    {
        $document = new DocumentStub($this->collection);

        $this->assertArrayHasKey('status', $document->toArray());
        $this->assertArrayHasKey('status', $document->getOriginalData());
    }

    public function testDeprecatedSchema()
    {
        $document = new DeprecatedSchemaDocumentStub($this->collection);

        $this->assertSame('ACTIVE', $document->status);
    }

    public function testCreateReference()
    {
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        $reference = $document->createReference();

        $this->assertSame(array(
            '$ref' => 'phpmongo_test_collection',
            '$id' => $document->getId(),
        ), $reference);
    }

    public function testGetRelatedDocument()
    {
        $this->collection->disableDocumentPool();

        $relatedDocument = $this->collection->createDocument(array('param' => 'value'))->save();

        $document = $this->collection
            ->createDocument()
            ->setReference('related', $relatedDocument)
            ->save();

        $foundRelatedDocument = $document->getReferencedDocument('related');

        $this->assertEquals(
            $relatedDocument->getId(),
            $foundRelatedDocument->getId()
        );
    }

    public function testGetRelatedDocumentList()
    {
        $this->collection->disableDocumentPool();

        $relatedDocument = $this->collection->createDocument(array('param' => 'value'))->save();

        $document = $this->collection
            ->createDocument()
            ->pushReference('related', $relatedDocument)
            ->save();

        $foundRelatedDocumentList = $document->getReferencedDocumentList('related');

        $this->assertSame(1, count($foundRelatedDocumentList));

        $foundRelatedDocument = current($foundRelatedDocumentList);

        $this->assertEquals(
            $relatedDocument->getId(),
            $foundRelatedDocument->getId()
        );
    }
}
