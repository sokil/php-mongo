<?php

namespace Sokil\Mongo;

use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private $database;

    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;


    /** @var Client */
    private $client;

    public function setUp()
    {
        // connect to mongo
        $this->client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);

        // select database
        $this->database = $this->client->getDatabase('test');
        $this->collection = $this->database->getCollection('phpmongo_test_collection');
    }

    public function tearDown()
    {
        if($this->collection) {
            $this->collection->delete();
        }
    }

    public function testGetDocumentByReference()
    {
        // create document
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        // invalid col and db
        $foundDocument = $this->collection->getDocumentByReference(
            array(
                '$ref'  => 'some_collection',
                '$db'   => 'some_db',
                '$id'   => $document->getId(),
            ),
            false
        );

        $this->assertNull($foundDocument);

        // invalid db
        $foundDocument = $this->collection->getDocumentByReference(
            array(
                '$ref'  => $this->collection->getName(),
                '$db'   => 'some_db',
                '$id'   => $document->getId(),
            ),
            false)
        ;
        $this->assertNull($foundDocument);

        // all valid
        $foundDocument = $this->collection->getDocumentByReference(
            array(
                '$ref'  => $this->collection->getName(),
                '$db'   => $this->collection->getDatabase()->getName(),
                '$id'   => $document->getId(),
            ),
            false
        );

        $this->assertSame(
            (string)$document->getId(),
            (string)$foundDocument->getId()
        );
    }

    public function testGetDocument()
    {
        // create document
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        // get document
        $foundDocument = $this->collection->getDocument($document->getId());
        $this->assertEquals($document->getId(), $foundDocument->getId());

        // get document as property of collection
        $foundDocument = $this->collection->{$document->getId()};
        $this->assertEquals($document->getId(), $foundDocument->getId());
    }

    public function testGetDocumentDirectly()
    {
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        $this->collection->batchUpdate(
            array('_id' => $document->getId()),
            array(
                '$set' => array('param' => 'updatedValue')
            )
        );

        // already loaded document
        $this->assertEquals('value', $document->param);

        // get document from pool
        $this->assertEquals(
            'value',
            $this->collection->getDocument($document->getId())->param
        );

        $this->assertEquals(
            'updatedValue',
            $this->collection->getDocumentDirectly($document->getId())->param
        );
    }

    public function testGetDocument_ConfigureCursor_DocumentPoolMustBeOmitted()
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value'
            ))
            ->save();

        $this->assertTrue($this->collection->isDocumentInDocumentPool($document));

        // get document with wrong param
        $this->assertEmpty(
            $this->collection
                ->getDocument(
                    $document->getId(),
                    function($cursor) {
                        $cursor->where('param', 'notValidValue');
                    }
                )
        );

        // get document with valid param
        $this->assertEquals(
            'value',
            $this->collection
                ->getDocument(
                    $document->getId(),
                    function($cursor) {
                        $cursor->where('param', 'value');
                    }
                )
                ->param
        );
    }

    public function testGetDocument_ConfigureCursor_getAsArray()
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value'
            ))
            ->save();

        $this->assertTrue($this->collection->isDocumentInDocumentPool($document));

        // get document with wrong param
        $this->assertEquals(
            array('param' => 'value'),
            $this->collection
                ->getDocument(
                    $document->getId(),
                    function($cursor) {
                        $cursor
                            ->asArray()
                            ->skipField('_id');
                    }
                )
        );
    }

    public function testGetDocument_ConfigureCursor_ReturnAsArray()
    {
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        $this->collection->updateAll(array(
            '$set' => array('param' => 'updatedValue')
        ));

        // already loaded document
        $this->assertEquals('value', $document->param);

        // get document from pool
        $this->assertEquals(
            'value',
            $this->collection->getDocument($document->getId())->param
        );

        $this->assertEquals(
            'updatedValue',
            $this->collection->getDocumentDirectly($document->getId())->param
        );
    }

    public function testGetDocument_WrongId()
    {
        // get document
        $foundDocument = $this->collection->getDocument('someunexiteddocumentid');
        $this->assertNull($foundDocument);
    }

    public function testGetStoredDocumentInstanceFromArray()
    {
        $document = $this->collection->hydrate(array(
            '_id' => new \MongoId(),
        ));

        $this->assertTrue($document->isStored());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Document must be stored and has _id key
     */
    public function testGetStoredDocumentInstanceFromArray_DocumentNotStored()
    {
        $document = $this->collection->hydrate(array(
            'param' => 'value',
        ));

        $this->assertTrue($document->isStored());
    }

    public function testCreateDocument_IsNewDocumentStoredToPoolAfterSave()
    {
        $this->assertTrue($this->collection->isDocumentPoolEmpty());

        $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        $this->assertFalse($this->collection->isDocumentPoolEmpty());
    }

    public function testStoreDocumentInPool_DocumentAlreadyStored()
    {
        /**
         * Store document to pool
         */
        $this->assertTrue($this->collection->isDocumentPoolEmpty());

        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        $this->assertFalse($this->collection->isDocumentPoolEmpty());

        /**
         * Modify document in another thread
         */
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);

        $client
            ->getDatabase($document->getCollection()->getDatabase()->getName())
            ->getCollection($document->getCollection()->getName())
            ->find()
            ->findOne()
            ->set('param', 'updatedValue')
            ->save();

        // here oroginal document must be in unconsisted state
        $this->assertEquals('value', $document->get('param'));

        // overload document in pool with new data
        $this->collection->find()->findOne();
        $this->assertEquals('updatedValue', $document->get('param'));
    }

    public function testGetDocumentsFromDocumentPool()
    {
        $document1 = $this->collection
            ->createDocument(array('field' => 'value1'))
            ->save();

        $document2 = $this->collection
            ->createDocument(array('field' => 'value2'))
            ->save();

        $this->assertEquals(2, $this->collection->documentPoolCount());

        // without arguments
        $documents = $this->collection->getDocumentsFromDocumentPool();
        $this->assertEquals(2, count($documents));
        $this->assertArrayHasKey((string) $document1->getId(), $documents);
        $this->assertArrayHasKey((string) $document2->getId(), $documents);

        // with arguments
        $documents = $this->collection->getDocumentsFromDocumentPool(
            array($document1->getId())
        );
        $this->assertEquals(1, count($documents));
        $this->assertArrayHasKey((string) $document1->getId(), $documents);
    }

    public function testIsDocumentInDocumentPool()
    {
        $document = $this->collection
            ->createDocument(array('field' => 'value'))
            ->save();

        $this->assertTrue($this->collection->isDocumentInDocumentPool($document));
        $this->assertTrue($this->collection->isDocumentInDocumentPool($document->getId()));
        $this->assertTrue($this->collection->isDocumentInDocumentPool((string) $document->getId()));

        $this->assertFalse($this->collection->isDocumentInDocumentPool(42));
    }

    public function testGetDocumentByStringId()
    {
        $document = $this->collection
            ->createDocument(array(
                '_id'   => 'abcdef',
                'param' => 'value'
            ))
            ->save();

        // get document
        $foundDocument = $this->collection->getDocument('abcdef');

        $this->assertNotNull($foundDocument);

        $this->assertEquals($document->getId(), $foundDocument->getId());
    }

    public function testGetDocuments_OneFromPoolOtherDirectly()
    {
        $doc1Id = new \MongoId();
        $doc2Id = new \MongoId();
        $doc3Id = new \MongoId();

        // add documents skipping document pool
        $this->collection->batchInsert(array(
            array('_id' => $doc1Id, 'param' => 'value1'),
            array('_id' => $doc2Id, 'param' => 'value2'),
            array('_id' => $doc3Id, 'param' => 'value3'),
        ));

        // load one document to document pool
        $doc1 = $this->collection->getDocument($doc1Id);
        $this->assertNotEmpty($doc1);
        $this->assertInstanceOf('\Sokil\Mongo\Document', $doc1);
        $this->assertEquals('value1', $doc1->param);

        $this->assertEquals(1, $this->collection->documentPoolCount());

        // load all documents
        $documents = $this->collection->getDocuments(array(
            $doc1Id,
            $doc2Id,
            $doc3Id,
        ));

        $this->assertArrayHasKey((string) $doc1Id, $documents);
        $this->assertArrayHasKey((string) $doc2Id, $documents);
        $this->assertArrayHasKey((string) $doc3Id, $documents);
    }

    public function testGetDocuments_AllFromPool()
    {
        $doc1Id = new \MongoId();
        $doc2Id = new \MongoId();
        $doc3Id = new \MongoId();

        // add documents skipping document pool
        $this->collection->batchInsert(array(
            array('_id' => $doc1Id, 'param' => 'value1'),
            array('_id' => $doc2Id, 'param' => 'value2'),
            array('_id' => $doc3Id, 'param' => 'value3'),
        ));

        // load documents to document pool
        $doc1 = $this->collection->getDocument($doc1Id);
        $this->assertEquals('value1', $doc1->param);

        $doc2 = $this->collection->getDocument($doc2Id);
        $this->assertEquals('value2', $doc2->param);

        $doc3 = $this->collection->getDocument($doc3Id);
        $this->assertEquals('value3', $doc3->param);

        $this->assertEquals(3, $this->collection->documentPoolCount());

        // load all documents
        $documents = $this->collection->getDocuments(array(
            $doc1Id,
            $doc2Id,
            $doc3Id,
        ));

        $this->assertArrayHasKey((string) $doc1Id, $documents);
        $this->assertArrayHasKey((string) $doc2Id, $documents);
        $this->assertArrayHasKey((string) $doc3Id, $documents);
    }

    public function testGetDocuments_UnexistedIdsSpecified()
    {
        // get documents when wrong id's
        $this->assertEquals(array(), $this->collection->getDocuments(array(
            new \MongoId,
            new \MongoId,
            new \MongoId,
        )));
    }

    public function testSaveValidNewDocument()
    {
        // create document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));

        $document->set('some-field-name', 'some-value')->save();
    }

    public function testUpdateExistedDocument()
    {
        // create document
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        // update document
        $document->set('param', 'new-value')->save();

        // test
        $document = $this->collection->getDocument($document->getId());
        $this->assertEquals('new-value', $document->param);
    }

    /**
     * @expectedException \Sokil\Mongo\Document\InvalidDocumentException
     */
    public function testSaveInvalidNewDocument()
    {
        // create document
        $document = $this
            ->getMockBuilder('\Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collection))
            ->getMock();

        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));

        // save document
        $document->save();
    }

    public function testDeleteCollection_UnexistedCollection()
    {
        $this->collection = $this->database->getCollection('UNEXISTED_COLLECTION_NAME');
        $this->collection->delete();
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error deleting collection phpmongo_test_collection: Some strange error
     */
    public function testDeleteCollection_ExceptionOnCollectionDeleteError()
    {
        $this->collectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('drop'))
            ->setConstructorArgs(array(
                $this->database->getMongoDB(),
                'phpmongo_test_collection',
            ))
            ->getMock();

        $this->collectionMock
            ->expects($this->once())
            ->method('drop')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'errmsg' => 'Some strange error',
            )));

        $collection = new Collection($this->database, $this->collectionMock);
        $collection->delete();
    }

    public function testDeleteDocuments_ExpressionAsArray()
    {
        // add
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        // delete
        $this->collection->batchDelete(array('param' => array('$gt' => 2)));

        // test
        $this->assertEquals(2, count($this->collection));
    }

    public function testDeleteDocuments_ExpressionAsCallable()
    {
        // add
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        // delete
        $this->collection->batchDelete(function($expression) {
            $expression->whereGreater('param', 2);
        });

        // test
        $this->assertEquals(2, count($this->collection));
    }

    public function testDeleteDocuments_ExpressionAsObject()
    {
        // add
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        // delete
        $this->collection->batchDelete($this->collection->expression()->whereGreater('param', 2));

        // test
        $this->assertEquals(2, count($this->collection));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Mixed must be instance of "\Sokil\Mongo\Expression", array or callable that accepts "\Sokil\Mongo\Expression", "string" given
     */
    public function testBatchDelete_WrongExpressionSpecified()
    {
        // add
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        // delete
        $this->collection->batchDelete('hello');
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error removing documents from collection: Some strange error
     */
    public function testBatchDelete_ErrorDeletingDocuments()
    {
        $this->collectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('remove'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $this->collectionMock
            ->expects($this->once())
            ->method('remove')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'Some strange error',
            )));

        $this->collection = new Collection($this->database, $this->collectionMock);

        $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        $this->collection->batchDelete(
            $this->collection->expression()->where('param', 'value')
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Delete document error: Some strange error
     */
    public function testDeleteDocument_ErrorDeletingDocument()
    {
        $this->collectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('remove'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $this->collectionMock
            ->expects($this->once())
            ->method('remove')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'Some strange error',
            )));

        $this->collection = new Collection($this->database, $this->collectionMock);

        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();

        $this->collection->deleteDocument($document);
    }

    public function testUpdate_ExpressionIsArray()
    {
        // create documents
        $d1 = $this->collection->createDocument(array('p' => 1))->save();
        $d2 = $this->collection->createDocument(array('p' => 2))->save();

        $this->collection->update(
            array('p' => 1),
            $this->collection->operator()->set('k', 'v'),
            array(
                'multiple' => true,
            )
        );

        // test 1
        $data1 = $this
            ->collection
            ->getDocumentDirectly($d1->getId())
            ->toArray();

        unset($data1['_id']);

        $this->assertEquals(
            array(
                'p' => 1,
                'k' => 'v',
            ),
            $data1
        );

        // test 2
        $data2 = $this
            ->collection
            ->getDocumentDirectly($d2->getId())
            ->toArray();

        unset($data2['_id']);

        $this->assertEquals(
            array(
                'p' => 2,
            ),
            $data2
        );
    }

    public function testUpdate_ExpressionIsCallable()
    {
        // create documents
        $d1 = $this->collection->createDocument(array('p' => 1))->save();
        $d2 = $this->collection->createDocument(array('p' => 2))->save();

        $this->collection->update(
            function($expression) { $expression->where('p', 1); },
            $this->collection->operator()->set('k', 'v'),
            array(
                'multiple' => true,
            )
        );

        // test 1
        $data1 = $this
            ->collection
            ->getDocumentDirectly($d1->getId())
            ->toArray();
        
        unset($data1['_id']);

        $this->assertEquals(
            array(
                'p' => 1,
                'k' => 'v',
            ),
            $data1
        );

        // test 2
        $data2 = $this
            ->collection
            ->getDocumentDirectly($d2->getId())
            ->toArray();

        unset($data2['_id']);

        $this->assertEquals(
            array(
                'p' => 2,
            ),
            $data2
        );
    }

    public function testUpdate_ExpressionIsExpressionObject()
    {
        // create documents
        $d1 = $this->collection->createDocument(array('p' => 1))->save();
        $d2 = $this->collection->createDocument(array('p' => 2))->save();

        $this->collection->update(
            $this->collection->expression()->where('p', 1),
            $this->collection->operator()->set('k', 'v'),
            array(
                'multiple' => true,
            )
        );

        // test 1
        $data1 = $this
            ->collection
            ->getDocumentDirectly($d1->getId())
            ->toArray();

        unset($data1['_id']);

        $this->assertEquals(
            array(
                'p' => 1,
                'k' => 'v',
            ),
            $data1
        );

        // test 2
        $data2 = $this
            ->collection
            ->getDocumentDirectly($d2->getId())
            ->toArray();

        unset($data2['_id']);

        $this->assertEquals(
            array(
                'p' => 2,
            ),
            $data2
        );
    }

    public function testUpdate_UpdateDataIsOperatorObject()
    {
        // create documents
        $this->collection->createDocument(array('p' => 1))->save();

        $this->collection->update(
            array(),
            $this->collection->operator()->set('k', 'v')
        );

        // test
        $data = $this->collection->find()->findOne()->toArray();
        unset($data['_id']);

        $this->assertEquals(
            array(
                'p' => 1,
                'k' => 'v',
            ),
            $data
        );
    }

    public function testUpdate_UpdateDataIsOperatorArray()
    {
        // create documents
        $this->collection->createDocument(array('p' => 1))->save();

        $this->collection->update(
            array(),
            array(
                '$set' => array('k' => 'v'),
            )
        );

        // test
        $data = $this->collection->find()->findOne()->toArray();
        unset($data['_id']);

        $this->assertEquals(
            array(
                'p' => 1,
                'k' => 'v',
            ),
            $data
        );
    }

    public function testUpdate_UpdateDataIsOperatorCallable()
    {
        // create documents
        $this->collection->createDocument(array('p' => 1))->save();

        $this->collection->update(
            array(),
            function(Operator $o) {
                $o->set('k', 'v');
            }
        );

        // test
        $data = $this->collection->find()->findOne()->toArray();
        unset($data['_id']);

        $this->assertEquals(
            array(
                'p' => 1,
                'k' => 'v',
            ),
            $data
        );
    }

    public function testUpdate_UpdateData()
    {
        $this->collection->disableDocumentPool();
        
        // create documents
        $this->collection->createDocument(array('p' => 1))->save();

        $this->collection->update(
            array(),
            array('k' => 'v')
        );

        // test
        $data = $this->collection->find()->findOne()->refresh()->toArray();
        unset($data['_id']);

        $this->assertEquals(
            array(
                'k' => 'v',
            ),
            $data
        );
    }

    public function testUpdateMultiple_WithAcknowledgedWriteConcern()
    {
        // get collection
        $this->collection = $this->database
            ->getCollection('phpmongo_test_collection')
            ->delete()
            ->setWriteConcern(1);

        // create documents
        $d1 = $this->collection->createDocument(array('p' => 1));
        $d1->save();

        $d2 = $this->collection->createDocument(array('p' => 1));
        $d2->save();

        // packet update
        $this->collection->updateMultiple(
            $this->collection->expression()->where('p', 1),
            $this->collection->operator()->set('k', 'v')
        );

        // test
        foreach($this->collection->find() as $document) {
            $this->assertArrayHasKey('k', $document->toArray());
        }
    }

    public function testUpdateMultiple_WithUnacknowledgedWriteConcern()
    {
        // get collection
        $this->collection->setUnacknowledgedWriteConcern();

        // create documents
        $d1 = $this->collection->createDocument(array('p' => 1));
        $d1->save();

        $d2 = $this->collection->createDocument(array('p' => 1));
        $d2->save();

        // packet update
        $this->collection->updateMultiple(
            $this->collection->expression()->where('p', 1),
            $this->collection->operator()->set('k', 'v')
        );

        // test
        foreach($this->collection->find() as $document) {
            $this->assertArrayHasKey('k', $document->toArray());
        }
    }

    public function testUpdateAll_WithAcknowledgedWriteConcern()
    {
        // get collection
        $this->collection->setWriteConcern(1);

        // create documents
        $d1 = $this->collection->createDocument(array('p' => 1));
        $d1->save();

        $d2 = $this->collection->createDocument(array('p' => 1));
        $d2->save();

        // packet update
        $this->collection->updateAll(
            $this->collection->operator()->set('k', 'v')
        );

        // test
        foreach($this->collection->find() as $document) {
            $this->assertArrayHasKey('k', $document->toArray());
        }
    }

    public function testUpdateAll_WithUnacknowledgedWriteConcern()
    {
        // get collection
        $this->collection->setUnacknowledgedWriteConcern();

        // create documents
        $d1 = $this->collection->createDocument(array('p' => 1));
        $d1->save();

        $d2 = $this->collection->createDocument(array('p' => 1));
        $d2->save();

        // packet update
        $this->collection->updateAll(
            $this->collection->operator()->set('k', 'v')
        );

        // test
        foreach($this->collection->find() as $document) {
            $this->assertArrayHasKey('k', $document->toArray());
        }
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Update error: some_strange_error: Some strange error
     */
    public function testUpdateMultiple_ErrorWithWriteConcern()
    {
        // mock mongo's collection
        $mongoCollectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('update'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $mongoCollectionMock
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->isType('array'),
                $this->isType('array'),
                $this->arrayHasKey('multiple')
            )
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'some_strange_error',
                'errmsg' => 'Some strange error',
            )));

        // create collection with mocked original mongo collection
        $this->collection = new Collection($this->database, $mongoCollectionMock);
        $this->collection->setWriteConcern(1);

        $this->collection->updateMultiple(new Expression(), new Operator());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Update error
     */
    public function testUpdateMultiple_ErrorWithUnacknowledgedWriteConcern()
    {
        // mock mongo's collection
        $mongoCollectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('update'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $mongoCollectionMock
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->isType('array'),
                $this->isType('array'),
                $this->arrayHasKey('multiple')
            )
            ->will($this->returnValue(false));

        // create collection with mocked original mongo collection
        $this->collection = new Collection($this->database, $mongoCollectionMock);
        $this->collection->setUnacknowledgedWriteConcern();

        $this->collection->updateMultiple(new Expression(), new Operator());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Update error: some_strange_error: Some strange error
     */
    public function testUpdateAll_ErrorWithWriteConcern()
    {
        // mock mongo's collection
        $mongoCollectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('update'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $mongoCollectionMock
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->isType('array'),
                $this->isType('array'),
                $this->arrayHasKey('multiple')
            )
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'some_strange_error',
                'errmsg' => 'Some strange error',
            )));

        // create collection with mocked original mongo collection
        $this->collection = new Collection($this->database, $mongoCollectionMock);
        $this->collection->setWriteConcern(1);

        $this->collection->updateAll(new Operator());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Update error
     */
    public function testUpdateAll_ErrorWithUnacknowledgedWriteConcern()
    {
        // mock mongo's collection
        $mongoCollectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('update'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $mongoCollectionMock
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->isType('array'),
                $this->isType('array'),
                $this->arrayHasKey('multiple')
            )
            ->will($this->returnValue(false));

        // create collection with mocked original mongo collection
        $this->collection = new Collection($this->database, $mongoCollectionMock);
        $this->collection->setUnacknowledgedWriteConcern();

        $this->collection->updateAll(new Operator());
    }

    public function hasDocumentDataProvider()
    {
        return array(
            'same dsn, same database, same collection' => array(
                array('server1', 'test1', 'collection1'),
                array('server1', 'test1', 'collection1'),
                true,
            ),
            'same dsn, same database, diff collection' => array(
                array('server1', 'test1', 'collection1'),
                array('server1', 'test1', 'collection2'),
                false,
            ),
            'same dsn, diff database, same collection' => array(
                array('server1', 'test1', 'collection1'),
                array('server1', 'test2', 'collection1'),
                false,
            ),
            'same dsn, diff database, diff collection' => array(
                array('server1', 'test1', 'collection1'),
                array('server1', 'test2', 'collection2'),
                false,
            ),
            'diff dsn, same database, same collection' => array(
                array('server1', 'test1', 'collection1'),
                array('server2', 'test1', 'collection1'),
                false,
            ),
            'diff dsn, same database, diff collection' => array(
                array('server1', 'test1', 'collection1'),
                array('server2', 'test1', 'collection2'),
                false,
            ),
            'diff dsn, diff database, same collection' => array(
                array('server1', 'test1', 'collection1'),
                array('server2', 'test2', 'collection1'),
                false,
            ),
            'diff dsn, diff database, diff collection' => array(
                array('server1', 'test1', 'collection1'),
                array('server2', 'test2', 'collection2'),
                false,
            ),
        );
    }

    /**
     * @dataProvider hasDocumentDataProvider
     */
    public function testHasDocument($collection1Data, $collection2Data, $isEquals)
    {
        $clientPool = new ClientPool(array(
            'server1' => array(
                'dsn' => 'someDsn1',
            ),
            'server2' => array(
                'dsn' => 'someDsn2',
            ),
        ));

        $collection1 = $clientPool
            ->get($collection1Data[0])
            ->getDatabase($collection1Data[1])
            ->getCollection($collection1Data[2]);

        $collection2 = $clientPool->get($collection2Data[0])
            ->getDatabase($collection2Data[1])
            ->getCollection($collection2Data[2]);

        $document = $collection1->createDocument();

        $this->assertEquals(
            // marker - is equals or not
            $isEquals,
            // check
            $collection2->hasDocument($document)
        );
    }

    public function testEnableDocumentPool()
    {
        // disable document pool
        $this->collection->disableDocumentPool();
        $this->assertFalse($this->collection->isDocumentPoolEnabled());

        // create documents
        $document = $this->collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'A',
                )
            ))
            ->save();

        // read document
        $this->collection->getDocument($document->getId());

        // check if document in pool
        $this->assertTrue($this->collection->isDocumentPoolEmpty());

        // enable document pool
        $this->collection->enableDocumentPool();
        $this->assertTrue($this->collection->isDocumentPoolEnabled());

        // read document to pool
        $this->collection->getDocument($document->getId());

        // check if document in pool
        $this->assertFalse($this->collection->isDocumentPoolEmpty());

        // clear document pool
        $this->collection->clearDocumentPool();
        $this->assertTrue($this->collection->isDocumentPoolEmpty());

        // disable document pool
        $this->collection->disableDocumentPool();
        $this->assertFalse($this->collection->isDocumentPoolEnabled());
    }

    public function testGetDistinct_ExpressionArray()
    {
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'A',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'A',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'B',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F2', 'kk' => 'C',)))->save();

        // get distinkt
        $distinctValues = $this
            ->collection
            ->getDistinct(
                'k.kk',
                array('k.f' => 'F1')
            );

        $this->assertEquals(array('A', 'B'), $distinctValues);
    }

    public function testGetDistinct_ExpressionCallable()
    {
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'A',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'A',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'B',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F2', 'kk' => 'C',)))->save();

        // get distinkt
        $distinctValues = $this
            ->collection
            ->getDistinct(
                'k.kk',
                function($expression) { $expression->where('k.f', 'F1'); }
            );

        $this->assertEquals(array('A', 'B'), $distinctValues);
    }

    public function testGetDistinct_ExpressionObject()
    {
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'A',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'A',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'B',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F2', 'kk' => 'C',)))->save();

        // get distinkt
        $distinctValues = $this
            ->collection
            ->getDistinct(
                'k.kk',
                $this->collection->expression()->where('k.f', 'F1')
            );

        $this->assertEquals(array('A', 'B'), $distinctValues);
    }

    public function testGetDistinct_NoExpression()
    {
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'A',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'A',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F1', 'kk' => 'B',)))->save();
        $this->collection->createDocument(array('k' => array('f' => 'F2', 'kk' => 'C',)))->save();

        // get distinct
        $distinctValues = $this
            ->collection
            ->getDistinct('k.kk');

        $this->assertEquals(array('A', 'B', 'C'), $distinctValues);
    }

    public function testInsertMultiple_Acknowledged()
    {
        $this->collection->setWriteConcern(1);

        $this->collection
            ->insertMultiple(array(
                array('a' => 1, 'b' => 2),
                array('a' => 3, 'b' => 4),
            ));

        $document = $this->collection->find()->where('a', 1)->findOne();

        $this->assertNotEmpty($document);

        $this->assertEquals(2, $document->b);
    }

    public function testInsertMultiple_Unacknovledged()
    {
        $this->collection->setUnacknowledgedWriteConcern();

        $this->collection
            ->insertMultiple(array(
                array('a' => 1, 'b' => 2),
                array('a' => 3, 'b' => 4),
            ));

        $document = $this->collection->find()->where('a', 1)->findOne();

        $this->assertNotEmpty($document);

        $this->assertEquals(2, $document->b);
    }

    /**
     * @expectedException \Sokil\Mongo\Document\InvalidDocumentException
     * @expectedExceptionMessage Document is invalid on batch insert
     */
    public function testInsertMultiple_ValidateError()
    {
        // mock collection
        $this->collectionMock = $this
            ->getMockBuilder('\Sokil\Mongo\Collection')
            ->setMethods(array('createDocument'))
            ->setConstructorArgs(array($this->database, 'phpmongo_test_collection'))
            ->getMock();

        // mock document
        $documentMock = $this
            ->getMockBuilder('Sokil\Mongo\Document')
            ->setMethods(array('rules'))
            ->setConstructorArgs(array($this->collectionMock))
            ->getMock();

        // implement validation rules
        $documentMock
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('a', 'email'),
            )));

        // replace creating document with mocked
        $this->collectionMock
            ->expects($this->once())
            ->method('createDocument')
            ->will($this->returnValue($documentMock));

        // insert multiple
        $this->collectionMock->insertMultiple(array(
            array('a' => 1, 'b' => 2),
            array('a' => 3, 'b' => 4),
        ));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Batch insert error: Some strange error
     */
    public function testInsertMultiple_ErrorInsertingWithAcknowledgeWrite()
    {
        $this->collectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('batchInsert'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $this->collectionMock
            ->expects($this->once())
            ->method('batchInsert')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'Some strange error',
            )));

        $collection = new Collection($this->database, $this->collectionMock);

        // insert multiple
        $collection->insertMultiple(array(
            array('a' => 1, 'b' => 2),
            array('a' => 3, 'b' => 4),
        ));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Batch insert error
     */
    public function testInsertMultiple_ErrorInsertingWithUnacknowledgeWrite()
    {
        $this->collectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('batchInsert'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $this->collectionMock
            ->expects($this->once())
            ->method('batchInsert')
            ->will($this->returnValue(false));

        $this->collection = new Collection($this->database, $this->collectionMock);

        // insert multiple
        $this->collection->insertMultiple(array(
            array('a' => 1, 'b' => 2),
            array('a' => 3, 'b' => 4),
        ));
    }

    public function testInsert_Acknowledged()
    {
        $this->collection->setWriteConcern(1);

        $this->collection->insert(array('a' => 1, 'b' => 2));

        $document = $this->collection->find()->where('a', 1)->findOne();

        $this->assertNotEmpty($document);

        $this->assertEquals(2, $document->b);
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Insert error: some_error: Some strange error
     */
    public function testInsert_Acknowledged_Error()
    {
        $this->collectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('insert'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $this->collectionMock
            ->expects($this->once())
            ->method('insert')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'some_error',
                'errmsg' => 'Some strange error',
            )));

        $collection = new Collection($this->database, $this->collectionMock);
        $collection->setWriteConcern(1);

        $collection->insert(array('a' => 1, 'b' => 2));
    }

    public function testInsert_Unacknowledged()
    {
        $this->collection->setUnacknowledgedWriteConcern();

        $this->collection->insert(array('a' => 1, 'b' => 2));

        $document = $this->collection->find()->where('a', 1)->findOne();

        $this->assertNotEmpty($document);

        $this->assertEquals(2, $document->b);
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Insert error
     */
    public function testInsert_Unacknowledged_Error()
    {
        $this->collectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('insert'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'phpmongo_test_collection'))
            ->getMock();

        $this->collectionMock
            ->expects($this->once())
            ->method('insert')
            ->will($this->returnValue(false));

        $collection = new Collection($this->database, $this->collectionMock);
        $collection->setUnacknowledgedWriteConcern();

        $collection->insert(array('a' => 1, 'b' => 2));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage ns not found
     */
    public function testValidateOnNotExistedCollection()
    {
        $this->database
            ->getCollection('phpmongo_unexisted_collection')
            ->validate(true);
    }

    public function testValidateOnExistedCollection()
    {
        $this->collection->createDocument(array('param' => 1))->save();

        $result = $this->collection->validate(true);

        $this->assertInternalType('array', $result);
    }

    public function testCappedCollectionInsert()
    {
        $this->collection = $this->database
            ->createCappedCollection('capped_collection', 3, 30);

        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        $this->assertEquals(3, $this->collection->find()->count());

        $cursor = $this->collection->find();
        $cursor->rewind();

        $this->assertEquals(2, $cursor->current()->param);

        $cursor->next();
        $this->assertEquals(3, $cursor->current()->param);

        $cursor->next();
        $this->assertEquals(4, $cursor->current()->param);

        $this->collection->delete();
    }

    public function testStats()
    {
        $stats = $this->collection->stats();

        // error occuned - ns not found
        $this->assertEquals((double) 0, $stats['ok']);
    }

    public function testFind()
    {
        $d1 = $this->collection->createDocument(array('param' => 1))->save();
        $d2 = $this->collection->createDocument(array('param' => 2))->save();
        $d3 = $this->collection->createDocument(array('param' => 3))->save();
        $d4 = $this->collection->createDocument(array('param' => 4))->save();

        $queryBuilder = $this->collection->find(function(\Sokil\Mongo\Expression $expression) {
            $expression->where('param', 3);
        });

        $this->assertEquals($d3->getId(), $queryBuilder->findOne()->getId());
    }

    public function testGetResultSet()
    {
        $document1Id = $this->collection->createDocument(array('param' => 1))->save()->getId();
        $document2Id = $this->collection->createDocument(array('param' => 2))->save()->getId();
        $document3Id = $this->collection->createDocument(array('param' => 3))->save()->getId();
        $document4Id = $this->collection->createDocument(array('param' => 4))->save()->getId();

        $resultSet = $this->collection->find()->getResultSet();
        $this->assertInstanceOf('\Sokil\Mongo\ResultSet', $resultSet);

        $filtered = $resultSet
            ->filter(function($item) {
                return $item->param % 2;
            })
            ->keys();

        $this->assertEquals(array($document1Id, $document3Id), $filtered);
    }

    public function testReadPrimaryOnly()
    {
        $this->collection->readPrimaryOnly();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY
        ), $this->collection->getReadPreference());
    }

    public function testReadPrimaryPreferred()
    {
        $this->collection->readPrimaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->collection->getReadPreference());
    }

    public function testReadSecondaryOnly(array $tags = null)
    {
        $this->collection->readSecondaryOnly(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->collection->getReadPreference());
    }

    public function testReadSecondaryPreferred(array $tags = null)
    {
        $this->collection->readSecondaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->collection->getReadPreference());
    }

    public function testReadNearest(array $tags = null)
    {
        $this->collection->readNearest(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_NEAREST,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->collection->getReadPreference());
    }

    public function testSetWriteConcern()
    {
        $this->collection->setWriteConcern('majority', 12000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 12000
        ), $this->collection->getWriteConcern());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error setting write concern
     */
    public function testSetWriteConcern_Error()
    {
        $mongoCollectionMock = $this
            ->getMockBuilder('\MongoCollection')
            ->setMethods(array('setWriteConcern'))
            ->setConstructorArgs(array($this->database->getMongoDB(), 'test'))
            ->getMock();

        $mongoCollectionMock
            ->expects($this->once())
            ->method('setWriteConcern')
            ->will($this->returnValue(false));

        $collection = new Collection($this->database, $mongoCollectionMock);

        $collection->setWriteConcern(1);
    }

    public function testSetUnacknowledgedWriteConcern()
    {
        $this->collection->setUnacknowledgedWriteConcern(11000);

        $this->assertEquals(array(
            'w' => 0,
            'wtimeout' => 11000
        ), $this->collection->getWriteConcern());
    }

    public function testSetMajorityWriteConcern()
    {
        $this->collection->setMajorityWriteConcern(13000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 13000
        ), $this->collection->getWriteConcern());
    }

    public function testEnsureIndex()
    {
        $this->collection->ensureIndex(array(
            'asc'    => 1,
            'desc'   => -1,
        ));

        $indexes = $this->collection->getIndexes();

        $this->assertEquals(array(
            'asc'     => 1,
            'desc'    => -1,
        ), $indexes[1]['key']);

    }
    
    public function testDeleteIndex()
    {
        $this->collection->ensureIndex(array(
            'asc'    => 1,
            'desc'   => -1,
        ));

        $this->collection->deleteIndex(array(
            'asc'    => 1,
            'desc'   => -1,
        ));
        
        $indexes = $this->collection->getIndexes();
        $this->assertEquals(1, count($indexes));
    }

    public function testEnsureSparseIndex()
    {
        $this->collection->ensureSparseIndex(array(
            'sparseAsc'     => 1,
            'sparseDesc'    => -1,
        ));

        $indexes = $this->collection->getIndexes();

        $this->assertEquals(array(
            'sparseAsc'     => 1,
            'sparseDesc'    => -1,
        ), $indexes[1]['key']);


        $this->assertArrayHasKey('sparse', $indexes[1]);
    }

    public function testEnsureTTLIndex()
    {
        $this->collection->ensureTTLIndex(array(
            'ttlAsc'    => 1,
            'ttlDesc'   => -1,
        ), 12000);

        $indexes = $this->collection->getIndexes();

        $this->assertEquals(array(
            'ttlAsc'     => 1,
            'ttlDesc'    => -1,
        ), $indexes[1]['key']);

        $this->assertArrayHasKey('expireAfterSeconds', $indexes[1]);
        $this->assertEquals(12000, $indexes[1]['expireAfterSeconds']);
    }

    public function testEnsureUniqueIndex()
    {
        $this->collection->ensureUniqueIndex(array(
            'uniqueAsc'     => 1,
            'uniqueDesc'    => -1,
        ), true);

        $indexes = $this->collection->getIndexes();

        /*
            v.2.6.8
            array (
                0 => array (
                    'v' => 1,
                    'key' => array (
                        '_id' => 1,
                    ),
                    'name' => '_id_',
                    'ns' => 'test.phpmongo_test_collection',
                ),
                1 => array (
                    'v' => 1,
                    'unique' => true,
                    'key' => array (
                        'uniqueAsc' => 1,
                        'uniqueDesc' => -1,
                    ),
                    'name' => 'uniqueAsc_1_uniqueDesc_-1',
                    'ns' => 'test.phpmongo_test_collection',
                    'dropDups' => true,
                ),
            )

            v.3.0.0
            array(
                0 => array(
                    'v' => 1,
                    'key' => array (
                        '_id' => 1,
                    ),
                    'name' => '_id_',
                    'ns' => 'test.phpmongo_test_collection',
                ),
                1 => array (
                    'v' => 1,
                    'unique' => true,
                    'key' => array (
                        'uniqueAsc' => 1,
                        'uniqueDesc' => -1,
                    ),
                    'name' => 'uniqueAsc_1_uniqueDesc_-1',
                    'ns' => 'test.phpmongo_test_collection',
                ),
            )
         */

        $this->assertEquals(array(
            'uniqueAsc'     => 1,
            'uniqueDesc'    => -1,
        ), $indexes[1]['key']);

        $currentVersion = $this->collection->getDatabase()->getClient()->getDbVersion();
        if (version_compare($currentVersion, '3', '<') && !Client::isEmulationMode()) {
            $this->assertArrayHasKey('dropDups', $indexes[1]);
            $this->assertEquals(1, $indexes[1]['dropDups']);
        }
    }

    public function testEnsureFulltextIndex()
    {
        try {
            $this->collection->ensureFulltextIndex(
                array('fieldname1', 'fieldname2'),
                array(
                    'fieldname1' => 1,
                    'fieldname2' => 2,
                ),
                'spanish'
            );
        } catch (\MongoWriteConcernException $e) {
            $this->assertStringEndsWith('text search not enabled', $e->getMessage());
            return;
        }

        $indexes = $this->collection->getIndexes();
        $index = $indexes[1];

        // 'textIndexVersion' differ in different versions of mongodb
        $this->assertArrayHasKey('textIndexVersion', $index);
        $dbVersion = $this->collection->getDatabase()->getClient()->getDbVersion();
        if (version_compare($dbVersion, '2.6', '<')) {
            $this->assertEquals(1, $index['textIndexVersion']);
        } else if (version_compare($dbVersion, '3.2', '<')) {
            $this->assertEquals(2, $index['textIndexVersion']);
        } else {
            $this->assertEquals(3, $index['textIndexVersion']);
        }
        unset($index['textIndexVersion']);

        // v key
        unset($index['v']);

        // check other params
        $this->assertEquals(
            $index,
            array(
                'key' => array (
                    '_fts' => 'text',
                    '_ftsx' => 1,
                ),
                'name' => 'fieldname1_text_fieldname2_text',
                'ns' => 'test.phpmongo_test_collection',
                'default_language' => 'spanish',
                'weights' => array (
                    'fieldname1' => 1,
                    'fieldname2' => 2,
                ),
                'language_override' => 'language',
            )
        );
    }

    public function testInitIndexes()
    {
        $reflection = new \ReflectionClass($this->collection);
        $property = $reflection->getProperty('definition');
        $property->setAccessible(true);

        $definition = $property->getValue($this->collection);
        $definition->setOption('index', array(
            array(
                'keys' => array('asc' => 1, 'desc' => -1),
                'unique' => true,
            ),
        ));

        $this->collection->initIndexes();

        $indexes = $this->collection->getIndexes();

        $this->assertEquals(array(
            'asc'     => 1,
            'desc'    => -1,
        ), $indexes[1]['key']);

        $this->assertArrayHasKey('unique', $indexes[1]);
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Keys not specified
     */
    public function testInitIndexes_KeysNotSpecified()
    {
        $reflection = new \ReflectionClass($this->collection);
        $property = $reflection->getProperty('definition');
        $property->setAccessible(true);

        $definition = $property->getValue($this->collection);
        $definition->setOption('index', array(
            array(
                'unique' => true,
            ),
        ));

        $this->collection->initIndexes();

        $indexes = $this->collection->getIndexes();

        $this->assertEquals(array(
            'asc'     => 1,
            'desc'    => -1,
        ), $indexes[1]['key']);

        $this->assertArrayHasKey('unique', $indexes[1]);
    }

    public function testOverrideDefaultDocumentClassByString()
    {
        // define array of collections
        $this->database->map(array(
            'collection1' => array(
                'documentClass' => '\Sokil\Mongo\CollectionTestDocumentMock',
            )
        ));

        // define collection
        $this->database->map('collection2', array(
            'documentClass' => '\Sokil\Mongo\CollectionTestDocumentMock',
        ));

        // check
        $collection1 = $this->database->getCollection('collection1');
        $this->assertEquals(
            '\Sokil\Mongo\CollectionTestDocumentMock',
            $collection1->getDocumentClassName()
        );

        $collection2 = $this->database->getCollection('collection2');
        $this->assertEquals(
            '\Sokil\Mongo\CollectionTestDocumentMock',
            $collection2->getDocumentClassName()
        );
    }

    public function testOverrideDefaultDocumentClassByCallable()
    {
        // define array of collections
        $this->database->map(array(
            'collection1' => array(
                'documentClass' => function(array $data) {
                    return '\Sokil\Mongo\\' . $data['documentClassName'];
                },
            )
        ));

        // define collection
        $this->database->map('collection2', array(
            'documentClass' => function(array $data) {
                return '\Sokil\Mongo\\' . $data['documentClassName'];
            },
        ));

        // check
        $collection1 = $this->database->getCollection('collection1');
        $collection1DocumentClassName = $collection1->getDocumentClassName(array(
            'documentClassName' => 'CollectionTestDocumentMock',
        ));
        $this->assertEquals(
            '\Sokil\Mongo\CollectionTestDocumentMock',
            $collection1DocumentClassName
        );

        $collection2 = $this->database->getCollection('collection2');
        $collection2DocumentClassName = $collection2->getDocumentClassName(array(
            'documentClassName' => 'CollectionTestDocumentMock',
        ));
        $this->assertEquals(
            '\Sokil\Mongo\CollectionTestDocumentMock',
            $collection2DocumentClassName
        );
    }

    public function testGetOption()
    {
        // define array of collections
        $this->database->map(array(
            'collection1' => array(
                'someOption' => 'someValue',
            )
        ));

        $this->assertEquals('someValue', $this
            ->database
            ->getCollection('collection1')
            ->getOption('someOption')
        );
    }

    public function testGetOptions()
    {
        // define array of collections
        $this->database->map(array(
            'collection1' => array(
                'someOption' => 'someValue',
            )
        ));

        $options = $this->database
            ->getCollection('collection1')
            ->getOptions();

        $this->assertArrayHasKey('someOption', $options);
        $this->assertEquals($options['someOption'], 'someValue');
    }

    public function testIsVersioningEnabled()
    {
        // set by property
        $this->database->map('col1', '\Sokil\Mongo\CollectionWithVersioningMock');
        $this->assertTrue($this->database->col1->isVersioningEnabled());

        // set by map definition
        $this->database->map('col2', array(
            'versioning' => true,
        ));
        $this->assertTrue($this->database->col2->isVersioningEnabled());

        // set by map definition
        $this->database->map('col3', array(
            'versioning' => false,
        ));
        $this->assertFalse($this->database->col3->isVersioningEnabled());
    }

    /**
     * @throws Exception
     * @throws \MongoException
     */
    public function testRenameNonExistentCollection()
    {
        $this->expectException(Exception::class);

        // set test collection
        $this->collection = $this->database->getCollection('test');

        // rename non-existent collection to cause a MongoClient error
        $this->collection->renameCollection('phpmongo_test_collection', true);
    }

    /**
     * @throws Exception
     * @throws \MongoException
     */
    public function testRenameExistentCollection()
    {
        $sourceCollection = 'source_collection';

        // creating fake-collection to rename it then in test db context
        $this->database->createCollection($sourceCollection);

        $this->collection = $this->database->getCollection($sourceCollection);

        // rename an existent collection
        $status = $this->collection->renameCollection('phpmongo_test_collection', true);

        // assert that we are done renaming
        $this->assertTrue($status);
    }
}

class CollectionTestDocumentMock extends Document { }

class CollectionWithVersioningMock extends Collection {
    protected $versioning = true;
}
