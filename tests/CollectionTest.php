<?php

namespace Sokil\Mongo;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private static $database;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        self::$database = $client->getDatabase('test');
    }
    
    public static function tearDownAfterClass() {

    }
    
    public function testGetDocument()
    {
        // create document
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $document = $collection->createDocument(array('param' => 'value'));   
        $collection->saveDocument($document);
        
        // get document
        $foundDocument = $collection->getDocument($document->getId());
        
        $this->assertEquals($document->getId(), $foundDocument->getId());

        // get document as property of collection
        $foundDocument = $collection->{$document->getId()};

        $this->assertEquals($document->getId(), $foundDocument->getId());
    }
    
    public function testGetDocumentByStringId()
    {
        // create document
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->delete();
        
        $document = $collection
            ->createDocument(array(
                '_id'   => 'abcdef',
                'param' => 'value'
            ));
        
        $document->save();
        
        // get document
        $foundDocument = $collection->getDocument('abcdef');
        
        $this->assertNotNull($foundDocument);
        
        $this->assertEquals($document->getId(), $foundDocument->getId());
    }
    
    public function testGetDocuments()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');
        
        // create document1
        $document1 = $collection->createDocument(array('param' => 'value1'));   
        $collection->saveDocument($document1);
        
        // create document 2
        $document2 = $collection->createDocument(array('param' => 'value2'));   
        $collection->saveDocument($document2);
        
        // get documents
        $foundDocuments = $collection->getDocuments(array(
            $document1->getId(),
            $document2->getId()
        ));
        
        $this->assertEquals(2, count($foundDocuments));
        
        $this->assertArrayHasKey((string) $document1->getId(), $foundDocuments);
        $this->assertArrayHasKey((string) $document2->getId(), $foundDocuments);
    }

    public function testGetDocuments_UnexistedIdsSpecified()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->delete();

        // get documents when wrong id's
        $this->assertEquals(array(), $collection->getDocuments(array(
            new \MongoId,
            new \MongoId,
            new \MongoId,
        )));
    }
    
    public function testSaveValidNewDocument()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');
        
        // create document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));
        
        $document->set('some-field-name', 'some-value');
        
        // save document
        $collection->saveDocument($document);
        
        $collection->delete();
    }
    
    public function testUpdateExistedDocument()
    {
        // create document
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $document = $collection->createDocument(array('param' => 'value'));   
        $collection->saveDocument($document);
        
        // update document
        $document->set('param', 'new-value');
        $collection->saveDocument($document);
        
        // test
        $document = $collection->getDocument($document->getId());
        $this->assertEquals('new-value', $document->param);
        
        $collection->delete();
    }
    
    /**
     * @expectedException \Sokil\Mongo\Document\Exception\Validate
     */
    public function testSaveInvalidNewDocument()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');
        
        // create document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array($collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));
        
        // save document
        
        $collection->saveDocument($document);
        
        $collection->delete();
    }

    public function testDeleteCollection_UnexistedCollection()
    {
        $collection = self::$database->getCollection('UNEXISTED_COLLECTION_NAME');
        $collection->delete();
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error deleting collection phpmongo_test_collection: Some strange error
     */
    public function testDeleteCollection_ExceptionOnCollectionDeleteError()
    {
        $collectionMock = $this->getMock(
            '\MongoCollection',
            array('drop'),
            array(self::$database->getMongoDB(), 'phpmongo_test_collection')
        );

        $collectionMock
            ->expects($this->once())
            ->method('drop')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'errmsg' => 'Some strange error',
            )));

        $collection = new Collection(self::$database, $collectionMock);

        $collection->delete();
    }

    public function testDeleteDocuments()
    {
        // get collection
        $collection = self::$database
            ->getCollection('phpmongo_test_collection');
        $collection->delete();
        
        // add
        $collection->createDocument(array('param' => 1))->save();
        $collection->createDocument(array('param' => 2))->save();
        $collection->createDocument(array('param' => 3))->save();
        $collection->createDocument(array('param' => 4))->save();
        
        // delete
        $collection->deleteDocuments($collection->expression()->whereGreater('param', 2));
        
        // test
        $this->assertEquals(2, count($collection));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Delete document error: Some strange error
     */
    public function testDeleteDocument_ErrorDeletingDocument()
    {
        $collectionMock = $this->getMock(
            '\MongoCollection',
            array('remove'),
            array(self::$database->getMongoDB(), 'phpmongo_test_collection')
        );

        $collectionMock
            ->expects($this->once())
            ->method('remove')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'Some strange error',
            )));

        $collection = new Collection(self::$database, $collectionMock);

        $document = $collection
            ->createDocument(array('param' => 'value'))
            ->save();

        $collection->deleteDocument($document);
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error removing documents from collection: Some strange error
     */
    public function testDeleteDocuments_ErrorDeletingDocuments()
    {
        $collectionMock = $this->getMock(
            '\MongoCollection',
            array('remove'),
            array(self::$database->getMongoDB(), 'phpmongo_test_collection')
        );

        $collectionMock
            ->expects($this->once())
            ->method('remove')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'Some strange error',
            )));

        $collection = new Collection(self::$database, $collectionMock);

        $collection
            ->createDocument(array('param' => 'value'))
            ->save();

        $collection->deleteDocuments(
            $collection->expression()
                ->where('param', 'value')
        );
    }

    public function testUpdateMultiple()
    {
        // get collection
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->delete();
        
        // create documents
        $d1 = $collection->createDocument(array('p' => 1));
        $collection->saveDocument($d1);
        
        $d2 = $collection->createDocument(array('p' => 1));
        $collection->saveDocument($d2);
        
        // packet update
        $collection->updateMultiple(
            $collection->expression()->where('p', 1),
            $collection->operator()->set('k', 'v')
        );
        
        // test
        foreach($collection->find() as $document) {
            $this->assertArrayHasKey('k', $document->toArray());
        }
    }
    
    public function testUpdateMultipleOnEmptyExpression()
    {
        // get collection
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->delete();
        
        // create documents
        $d1 = $collection->createDocument(array('p' => 1));
        $collection->saveDocument($d1);
        
        $d2 = $collection->createDocument(array('p' => 1));
        $collection->saveDocument($d2);
        
        // packet update
        $collection->updateAll(
            $collection->operator()->set('k', 'v')
        );
        
        // test
        foreach($collection->find() as $document) {
            $this->assertArrayHasKey('k', $document->toArray());
        }
    }

    public function testEnableDocumentPool()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->clearDocumentPool();

        // disable document pool
        $collection->disableDocumentPool();
        $this->assertFalse($collection->isDocumentPoolEnabled());

        // create documents
        $document = $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'A',
                )
            ))
            ->save();

        // read document
        $collection->getDocument($document->getId());

        // check if document in pool
        $this->assertTrue($collection->isDocumentPoolEmpty());

        // enable document pool
        $collection->enableDocumentPool();
        $this->assertTrue($collection->isDocumentPoolEnabled());

        // read document to pool
        $collection->getDocument($document->getId());

        // check if document in pool
        $this->assertFalse($collection->isDocumentPoolEmpty());

        // clear document pool
        $collection->clearDocumentPool();
        $this->assertTrue($collection->isDocumentPoolEmpty());

        // disable document pool
        $collection->disableDocumentPool();
        $this->assertFalse($collection->isDocumentPoolEnabled());
    }

    public function testGetDistinct()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');
    
        // create documents
        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'A',
                )
            ))
            ->save();
        
        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'A',
                )
            ))
            ->save();
        
        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'B',
                )
            ))
            ->save();
        
        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F2',
                    'kk'    => 'C',
                )
            ))
            ->save();
        
        // get distinkt
        $distinctValues = $collection
            ->getDistinct('k.kk', $collection->expression()->where('k.f', 'F1'));
        
        $this->assertEquals(array('A', 'B'), $distinctValues);
    }

    public function testGetDistinctWithoutExpression()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');

        // create documents
        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'A',
                )
            ))
            ->save();

        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'A',
                )
            ))
            ->save();

        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F1',
                    'kk'    => 'B',
                )
            ))
            ->save();

        $collection
            ->createDocument(array(
                'k' => array(
                    'f'     => 'F2',
                    'kk'    => 'C',
                )
            ))
            ->save();

        // get distinct
        $distinctValues = $collection
            ->getDistinct('k.kk');

        $this->assertEquals(array('A', 'B', 'C'), $distinctValues);
    }
    
    public function testInsertMultiple_Acknowledged()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->setMajorityWriteConcern();
        
        $collection
            ->insertMultiple(array(
                array('a' => 1, 'b' => 2),
                array('a' => 3, 'b' => 4),
            ));
        
        $document = $collection->find()->where('a', 1)->findOne();
        
        $this->assertNotEmpty($document);
        
        $this->assertEquals(2, $document->b);
    }

    public function testInsertMultiple_Unacknovledged()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->setUnacknowledgedWriteConcern();

        $collection
            ->insertMultiple(array(
                array('a' => 1, 'b' => 2),
                array('a' => 3, 'b' => 4),
            ));

        $document = $collection->find()->where('a', 1)->findOne();

        $this->assertNotEmpty($document);

        $this->assertEquals(2, $document->b);
    }

    /**
     * @expectedException \Sokil\Mongo\Document\Exception\Validate
     * @expectedExceptionMessage Document invalid
     */
    public function testInsertMultiple_ValidateError()
    {
        // mock collection
        $collectionMock = $this->getMock(
            '\Sokil\Mongo\Collection',
            array('createDocument'),
            array(self::$database, 'phpmongo_test_collection')
        );

        // mock document
        $documentMock = $this->getMock(
            'Sokil\Mongo\Document',
            array('rules'),
            array($collectionMock)
        );

        // implement validation rules
        $documentMock
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('a', 'email'),
            )));

        // replace creating document with mocked
        $collectionMock
            ->expects($this->once())
            ->method('createDocument')
            ->will($this->returnValue($documentMock));

        // insert multiple
        $collectionMock->insertMultiple(array(
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
        $collectionMock = $this->getMock(
            '\MongoCollection',
            array('batchInsert'),
            array(self::$database->getMongoDB(), 'phpmongo_test_collection')
        );

        $collectionMock
            ->expects($this->once())
            ->method('batchInsert')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'Some strange error',
            )));

        $collection = new Collection(self::$database, $collectionMock);

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
        $collectionMock = $this->getMock(
            '\MongoCollection',
            array('batchInsert'),
            array(self::$database->getMongoDB(), 'phpmongo_test_collection')
        );

        $collectionMock
            ->expects($this->once())
            ->method('batchInsert')
            ->will($this->returnValue(false));

        $collection = new Collection(self::$database, $collectionMock);

        // insert multiple
        $collection->insertMultiple(array(
            array('a' => 1, 'b' => 2),
            array('a' => 3, 'b' => 4),
        ));
    }
    
    public function testInsert_Acknowledged()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->setMajorityWriteConcern();
        
        $collection->insert(array('a' => 1, 'b' => 2));
        
        $document = $collection->find()->where('a', 1)->findOne();
        
        $this->assertNotEmpty($document);
        
        $this->assertEquals(2, $document->b);
    }

    public function testInsert_Unacknowledged()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->setUnacknowledgedWriteConcern();

        $collection->insert(array('a' => 1, 'b' => 2));

        $document = $collection->find()->where('a', 1)->findOne();

        $this->assertNotEmpty($document);

        $this->assertEquals(2, $document->b);
    }
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage ns not found
     */
    public function testValidateOnNotExistedCollection()
    {
        self::$database
            ->getCollection('phpmongo_unexisted_collection')
            ->validate(true);
    }
    
    public function testValidateOnExistedCollection()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection');
        
        $collection->createDocument(array('param' => 1))->save();
       
        $result = $collection->validate(true);
        
        $this->assertInternalType('array', $result);
    }
    
    public function testCappedCollectionInsert()
    {
        $collection = self::$database
            ->createCappedCollection('capped_collection', 3, 30);
        
        $collection->createDocument(array('param' => 1))->save();
        $collection->createDocument(array('param' => 2))->save();
        $collection->createDocument(array('param' => 3))->save();
        $collection->createDocument(array('param' => 4))->save();
        
        $this->assertEquals(3, $collection->find()->count());
        
        $documents = $collection->find();   
        
        $this->assertEquals(2, $documents->current()->param);
        
        $documents->next();
        $this->assertEquals(3, $documents->current()->param);
        
        $documents->next();
        $this->assertEquals(4, $documents->current()->param);
    }
    
    public function testStats()
    {
        $stats = self::$database
            ->createCollection('phpmongo_test_collection')
            ->stats();
        
        $this->assertEquals(1.0, $stats['ok']);
    }

    public function testFind()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->delete();

        $d1 = $collection->createDocument(array('param' => 1))->save();
        $d2 = $collection->createDocument(array('param' => 2))->save();
        $d3 = $collection->createDocument(array('param' => 3))->save();
        $d4 = $collection->createDocument(array('param' => 4))->save();

        $queryBuilder = $collection->find(function(\Sokil\Mongo\Expression $expression) {
            $expression->where('param', 3);
        });

        $this->assertEquals($d3->getId(), $queryBuilder->findOne()->getId());
    }
    
    public function testAggregate()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->delete();
            
        $collection->createDocument(array('param' => 1))->save();
        $collection->createDocument(array('param' => 2))->save();
        $collection->createDocument(array('param' => 3))->save();
        $collection->createDocument(array('param' => 4))->save();
        
        $result = $collection->createPipeline()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')))
            ->aggregate();
        
        $this->assertEquals(9, $result[0]['sum']);
        
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Wrong pipelines specified
     */
    public function testAggregate_WrongArgument()
    {
        self::$database
            ->getCollection('phpmongo_test_collection')
            ->aggregate('hello');
    }

    public function testLogAggregateResults()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->delete();

        // create documents
        $collection->createDocument(array('param' => 1))->save();
        $collection->createDocument(array('param' => 2))->save();
        $collection->createDocument(array('param' => 3))->save();
        $collection->createDocument(array('param' => 4))->save();

        // create logger
        $logger = $this->getMock('\Psr\Log\LoggerInterface');
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('Sokil\Mongo\Collection:<br><b>Pipelines</b>:<br>[{"$match":{"param":{"$gte":2}}},{"$group":{"_id":0,"sum":{"$sum":"$param"}}}]');

        // set logger to client
        self::$database->getClient()->setLogger($logger);

        // aggregate
        $collection->createPipeline()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')))
            ->aggregate();
    }
    
    public function testExplainAggregate()
    {
        $collection = self::$database
            ->getCollection('phpmongo_test_collection')
            ->delete();
            
        $collection->createDocument(array('param' => 1))->save();
        $collection->createDocument(array('param' => 2))->save();
        $collection->createDocument(array('param' => 3))->save();
        $collection->createDocument(array('param' => 4))->save();
        
        $pipelines = $collection->createPipeline()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')));
        
        try {
            $explain = $collection->explainAggregate($pipelines);
            $this->assertArrayHasKey('stages', $explain);
        } catch (\Exception $e) {
            $this->assertEquals('Explain of aggregation implemented only from 2.6.0', $e->getMessage());
        }
        
    }

    public function testReadPrimaryOnly()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');

        $collection->readPrimaryOnly();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY
        ), $collection->getReadPreference());
    }

    public function testReadPrimaryPreferred()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');

        $collection->readPrimaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $collection->getReadPreference());
    }

    public function testReadSecondaryOnly(array $tags = null)
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');

        $collection->readSecondaryOnly(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $collection->getReadPreference());
    }

    public function testReadSecondaryPreferred(array $tags = null)
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');

        $collection->readSecondaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $collection->getReadPreference());
    }

    public function testReadNearest(array $tags = null)
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');

        $collection->readNearest(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_NEAREST,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $collection->getReadPreference());
    }

    public function testSetWriteConcern()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');

        $collection->setWriteConcern('majority', 12000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 12000
        ), $collection->getWriteConcern());
    }

    public function testSetUnacknowledgedWriteConcern()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');

        $collection->setUnacknowledgedWriteConcern(11000);

        $this->assertEquals(array(
            'w' => 0,
            'wtimeout' => 11000
        ), $collection->getWriteConcern());
    }

    public function testSetMajorityWriteConcern()
    {
        $collection = self::$database->getCollection('phpmongo_test_collection');

        $collection->setMajorityWriteConcern(13000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 13000
        ), $collection->getWriteConcern());
    }
}