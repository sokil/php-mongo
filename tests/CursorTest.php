<?php

namespace Sokil\Mongo;

use PHPUnit\Framework\TestCase;

class CursorTest extends TestCase
{
    /**
     *
     * @var Database
     */
    private $database;

    /**
     *
     * @var Collection
     */
    private $collection;

    public function setUp()
    {
        // connect to mongo
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);

        // select database
        $this->database = $client->getDatabase('test');

        // select collection
        $this->collection = $this->database->getCollection('phpmongo_test_collection');
    }

    public function tearDown()
    {
        if ($this->collection) {
            $this->collection->delete();
        }
    }

    public function testReturnSpecifiedFields()
    {
        // create new document
        $documentId = $this
            ->collection
            ->createDocument(array(
                'a'    => 'a1',
                'b'    => 'b1',
                'c'    => 'c1',
                'd'    => 'd1',
            ))
            ->save()
            ->getId();

        // find some fields of document
        $document = $this
            ->collection
            ->find()
            ->fields(array(
                'a', 'c'
            ))
            ->field('b')
            ->findOne();

        $this->assertEquals(
            array(
                'a'    => 'a1',
                'b'    => 'b1',
                'c'    => 'c1',
                '_id'   => $documentId,
            ),
            $document->toArray()
        );
    }

    public function testSkipSpecifiedFields()
    {
        // create new document
        $documentId = $this
            ->collection
            ->createDocument(array(
                'a'    => 'a1',
                'b'    => 'b1',
                'c'    => 'c1',
                'd'    => 'd1',
            ))
            ->save()
            ->getId();

        // fild some fields of document
        $document = $this
            ->collection
            ->find()
            ->skipFields(array(
                'a', 'c'
            ))
            ->skipField('b')
            ->findOne()
            ->toArray();

        $this->assertEquals(
            array(
                'd'    => 'd1',
                '_id'   => $documentId,
            ),
            $document
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception\CursorException
     */
    public function testErrorOnAcceptedAndSkippedFieldsPassed()
    {
        // create new document
        $document = $this->collection->createDocument(array(
            'a'    => 'a1',
            'b'    => 'b1',
            'c'    => 'c1',
            'd'    => 'd1',
        ));

        $document->save();

        // find some fields of document
        $this->collection->find()
            ->fields(array(
                'a', 'c'
            ))
            ->skipField('b')
            ->findOne();
    }

    public function testSlice()
    {
        // create new document
        $document = $this->collection
            ->createDocument(array(
                'key'    => array('a', 'b', 'c', 'd', 'e', 'f'),
            ))
            ->save();

        // only limit defined
        $this
            ->assertEquals(
                array('a', 'b'),
                $this->collection->find()->slice('key', 2)->findOne()->key
            );

        $this->assertEquals(
            array('e', 'f'),
            $this->collection->find()->slice('key', -2)->findOne()->key
        );

        // limit and skip defined
        $this->assertEquals(
            array('c'),
            $this->collection->find()->slice('key', 1, 2)->findOne()->key
        );

        $this->assertEquals(
            array('e'),
            $this->collection->find()->slice('key', 1, -2)->findOne()->key
        );
    }

    public function testElemMatch()
    {
        // create new document
        $this->collection
            ->createDocument(array(
                "_id"=> new \MongoId("59f889e46803fa3713454b5d"),
                "projectName" => "usecase-updated",
                "classes" => array(
                    array(
                        "_id" => new \MongoId("59f9d7776803faea30b895dd"),
                        "className" => "OLA"
                    ),
                    array(
                        "_id" => new \MongoId("59f9d8ad6803fa4012b895df"),
                        "className" => "HELP"
                    ),
                    array(
                        "_id" => new \MongoId("59f9d9086803fa4112b895de"),
                        "className" => "DOC"
                    ),
                    array(
                        "_id" => new \MongoId("59f9d9186803fa4212b895de"),
                        "className" => "INVOC"
                    )
                )
            ))
            ->save();

        // filter embedded documents
        $filteredClasses = $this->collection
            ->find()
            ->elemMatch(
                'classes',
                function(Expression $e) {
                    $e->where('_id', new \MongoId("59f9d9186803fa4212b895de"));
                }
            )
            ->one()
            ->classes;

        $this->assertEquals(
            'INVOC',
            $filteredClasses[0]['className']
        );
    }

    public function testFindOne()
    {
        $this->collection
            ->createDocument(array(
                'someField'    => 'A',
            ))
            ->save();

        $this->collection
            ->createDocument(array(
                'someField'    => 'B',
            ))
            ->save();

        $documentId = $this->collection
            ->createDocument(array(
                'someField'    => 'C',
            ))
            ->save()
            ->getId();

        // find existed row
        $document = $this->collection
            ->find()
            ->where('someField', 'C')
            ->findOne();

        $this->assertEquals($documentId, $document->getId());

        // find unexisted row
        $document = $this->collection
            ->find()
            ->where('some-unexisted-field', 'some-value')
            ->findOne();

        $this->assertEmpty($document);
    }

    /**
     * Method findOne returns the first document according to the natural order
     * which reflects the order of documents on the disk.
     *
     * @throws Exception
     * @throws Exception\CursorException
     * @throws Exception\WriteException
     */
    public function testFindOneWithSort()
    {
        $expectedDocument = $this->collection->createDocument(array('someField' => '100'))->save();
        $this->collection->createDocument(array('someField' => '70'))->save();
        $this->collection->createDocument(array('someField' => '50'))->save();

        // find existed row
        $actualDocument = $this->collection
            ->find()
            ->sort(['someField' => 1])
            ->one();

        $this->assertEquals($expectedDocument->get('someField'), $actualDocument->get('someField'));
    }

    public function testQuery()
    {
        $exp1 = new Expression;
        $exp1->whereGreater('b', 1);

        $exp2 = new Expression;
        $exp1->whereLess('b', 20);

        $query = $this->collection->find();
        $query->query($exp1);
        $query->query($exp2);

        $this->assertEquals(array(
            'b' => array(
                '$gt' => 1,
                '$lt' => 20,
            ),
        ), $query->getMongoQuery());
    }

    public function testMixedToMongoIdList()
    {
        // create new document
        $document = $this->collection
            ->createDocument(array(
                'p1'    => 'v',
                'p2'    => 'doc1',
            ))
            ->save();

        $this->assertEquals(array(
            1,
            'varchar_id',
            new \MongoId('5412ac982de57284568b4567'),
            new \MongoId('5412ac982de57284568b4568'),
            new \MongoId('5412ac982de57284568b4569'),
            $document->getId(),
        ), Cursor::mixedToMongoIdList(array(
            1,
            'varchar_id',
            '5412ac982de57284568b4567',
            new \MongoId('5412ac982de57284568b4568'),
            array('_id' => new \MongoId('5412ac982de57284568b4569'), 'param' => 'value'),
            $document
        )));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Must be \MongoId, \Sokil\Mongo\Document, array with _id key, string or integer
     */
    public function testMixedToMongoIdList_InvalidType()
    {
        Cursor::mixedToMongoIdList(array(
            new \stdClass,
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Array must have _id key
     */
    public function testMixedToMongoIdList_ArrayWithoutIdKey()
    {
        Cursor::mixedToMongoIdList(array(
            array('param' => 'value'),
        ));
    }

    public function testGetIdList()
    {
        // create new document
        $document1Id = $this->collection
            ->createDocument(array(
                'p'    => 1,
            ))
            ->save()
            ->getId();

        // create new document
        $document2Id = $this->collection
            ->createDocument(array(
                'p'    => 1,
            ))
            ->save()
            ->getId();

        $this->assertEquals(array(
            $document1Id,
            $document2Id,
        ), $this->collection
            ->find()
            ->sort(array('p' => 1))
            ->getIdList()
        );
    }

    public function testFindRandom()
    {
        // create new document
        $document1 = $this->collection->createDocument(array(
            'p1'    => 'v',
            'p2'    => 'doc1',
        ));
        $document1->save();

        $document2 = $this->collection->createDocument(array(
            'p1'    => 'v',
            'p2'    => 'doc2',
        ));
        $document2->save();

        $document3 = $this->collection->createDocument(array(
            'p1'    => 'other_v',
            'p2'    => 'doc3',
        ));
        $document3->save();

        // find unexisted random document
        $document = $this->collection->find()->where('pZZZ', 'v')->findRandom();
        $this->assertEmpty($document);

        // find random documents if only one document match query
        $document = $this->collection->find()->where('p1', 'other_v')->findRandom();
        $this->assertEquals($document->getId(), $document3->getId());

        // find random document among two existed documents
        $document = $this->collection->find()->where('p1', 'v')->findRandom();
        $this->assertTrue(in_array($document->getId(), array($document1->getId(), $document2->getId())));
    }

    public function testFindAll()
    {
        // add doc
        $document = $this->collection->createDocument(array(
            'some-field'    => 'some-value',
        ));
        $document->save();

        // find
        $documents = $this->collection->find()->where('some-field', 'some-value');

        $firstDocument = current($documents->findAll());
        $this->assertEquals($firstDocument->getId(), $document->getId());
    }

    public function testPluck_findAsObject_SimpleField()
    {
        $this->collection
            ->insertMultiple(array(
                array('field' => 1),
                array('field' => 2),
                array('field' => 3),
                array('field' => 4),
            ));

        $this->assertEquals(
            array(1, 2, 3, 4),
            array_values($this->collection->find()->pluck('field'))
        );
    }

    public function testPluck_findAsObject_CompoundField()
    {
        $this->collection
            ->insertMultiple(array(
                array('field' => array('f1' => 'a1', 'f2' => 'b1')),
                array('field' => array('f1' => 'a2', 'f2' => 'b2')),
                array('field' => array('f1' => 'a3', 'f2' => 'b3')),
                array('field' => array('f1' => 'a4', 'f2' => 'b4')),
            ));

        $this->assertEquals(
            array('b1', 'b2', 'b3', 'b4'),
            array_values($this->collection->find()->pluck('field.f2'))
        );
    }

    public function testPluck_findAsArray_SimpleField()
    {
        $this->collection
            ->insertMultiple(array(
                array('field' => 1),
                array('field' => 2),
                array('field' => 3),
                array('field' => 4),
            ));

        $this->assertEquals(
            array(1, 2, 3 ,4),
            array_values($this->collection->findAsArray()->pluck('field'))
        );
    }

    public function testPluck_findAsArray_CompoundField()
    {
        $this->collection
            ->insertMultiple(array(
            array('field' => array('f1' => 'a1', 'f2' => 'b1')),
            array('field' => array('f1' => 'a2', 'f2' => 'b2')),
            array('field' => array('f1' => 'a3', 'f2' => 'b3')),
            array('field' => array('f1' => 'a4', 'f2' => 'b4')),
        ));

        $this->assertEquals(
            array('b1', 'b2', 'b3', 'b4'),
            array_values($this->collection->findAsArray()->pluck('field.f2'))
        );
    }

    public function testReturnAsArray()
    {
        $document = $this->collection->createDocument(array(
            'some-field'    => 'some-value',
        ));

        $document->save();

        // find all rows
        $cursor = $this->collection->findAsArray()->where('some-field', 'some-value');
        $cursor->rewind();
        $document = $cursor->current();

        $this->assertEquals('array', gettype($document));

        // find one row
        $document = $this->collection->findAsArray()->where('some-field', 'some-value')->findOne();
        $this->assertEquals('array', gettype($document));

    }

    public function testSort()
    {
        $this->collection->createDocument(array('p' => 'A'))->save();
        $this->collection->createDocument(array('p' => 'B'))->save();
        $this->collection->createDocument(array('p' => 'C'))->save();
        $this->collection->createDocument(array('p' => 'D'))->save();

        $cursor = $this->collection->find()->sort(array('p' => -1));
        $cursor->rewind();

        $this->assertEquals('D', $cursor->current()->p);
    }

    /**
     * @covers \Sokil\Mongo\Cursor::map
     */
    public function testMap()
    {
        $this->collection->createDocument(array(
            'param'    => 'value1',
        ))->save();

        $this->collection->createDocument(array(
            'param'    => 'value2',
        ))->save();

        // test
        $result = $this->collection->find()->map(function(Document $document) {
            return $document->param;
        });

        $this->assertEquals(array('value1', 'value2'), array_values($result));
    }

    /**
     * @covers \Sokil\Mongo\Cursor::filter
     */
    public function testFilter()
    {
        $this->collection->createDocument(array(
            'param'    => 'value1',
        ))->save();

        $this->collection->createDocument(array(
            'param'    => 'value2',
        ))->save();

        // test
        $result = $this->collection->find()->filter(function(Document $document) {
            return $document->param == 'value1';
        });

        $this->assertEquals('value1', current($result)->param);
    }

    public function testFindAndUpdate()
    {
        $d11 = $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = $this->collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = $this->collection->createDocument(array('param1' => 2, 'param2' => 2))->save();

        $document = $this->collection->find()
            ->where('param1', 1)
            ->sort(array(
                'param2' => 1,
            ))
            ->findAndUpdate($this->collection->operator()->set('newParam', '777'));

        $this->assertNotEmpty($document);

        $this->assertEquals(array(
            'param1'    => 1,
            'param2'    => 1,
            'newParam'  => '777',
            '_id'       => $d11->getId()
        ), $document->toArray());
    }

    public function testFindAndUpdate_NoDocumentsFound()
    {
        $document = $this->collection
            ->find()
            ->where('param1', 1)
            ->findAndUpdate(
                $this->collection->operator()->set('newParam', '777')
            );

        $this->assertNull($document);
    }

    public function testFindAndRemove()
    {
        $d11 = $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = $this->collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = $this->collection->createDocument(array('param1' => 2, 'param2' => 2))->save();

        $document = $this->collection->find()
            ->where('param1', 1)
            ->sort(array(
                'param2' => 1,
            ))
            ->findAndRemove();

        $this->assertNotEmpty($document);

        $this->assertEquals(array(
            'param1'    => 1,
            'param2'    => 1,
            '_id'       => $d11->getId()
        ), $document->toArray());

        $this->assertEquals(3, count($this->collection));
    }

    public function testLimit()
    {
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        $list = $this->collection
            ->find()
            ->limit(2, 2)
            ->findAll();

        $this->assertEquals(2, count($list));

        $document = current($list);
        $this->assertEquals(3, $document->param);

        next($list);

        $document = current($list);
        $this->assertEquals(4, $document->param);
    }

    public function testSetBatchSize()
    {
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        $list = $this->collection
            ->find()
            ->limit(2, 2)
            ->setBatchSize(3)
            ->findAll();

        $this->assertEquals(2, count($list));

        $document = current($list);
        $this->assertEquals(3, $document->param);

        next($list);

        $document = current($list);
        $this->assertEquals(4, $document->param);
    }

    public function testSetBatchSizeInMapping()
    {
        $batchSize = $this->collection
            ->getDatabase()
            ->map(array(
                'col1' => array(
                    'batchSize' => 42,
                )
            ))
            ->getCollection('col1')
            ->find()
            ->getOption('batchSize');

        $this->assertEquals(42, $batchSize);
    }

    public function testCount()
    {
        $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $this->collection->createDocument(array('param1' => 1, 'param2' => 3))->save();
        $this->collection->createDocument(array('param1' => 2, 'param2' => 2))->save();

        $queryBuilder = $this->collection
            ->find()
            ->where('param1', 1)
            ->limit(1)
            ->skip(1);

        $this->assertEquals(3, count($queryBuilder));
    }

    public function testLimitedCount()
    {
        $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $this->collection->createDocument(array('param1' => 1, 'param2' => 3))->save();
        $this->collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $this->collection->createDocument(array('param1' => 2, 'param2' => 2))->save();

        $queryBuilder = $this->collection
            ->find()
            ->where('param1', 2)
            ->limit(10)
            ->skip(1);

        $this->assertEquals(
            1,
            $queryBuilder->limitedCount()
        );
    }

    public function testExplain()
    {
        $this->collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $this->collection->createDocument(array('param1' => 1, 'param2' => 2))->save();

        $explain = $this->collection
            ->find()
            ->where('param1', 2)
            ->explain();

        $currentVersion = $this->collection->getDatabase()->getClient()->getDbVersion();
        if(version_compare($currentVersion, '3', '<')) {
            $this->assertArrayHasKey('cursor', $explain);
        } else {
            $this->assertArrayHasKey('queryPlanner', $explain);
        }

    }

    public function testReadPrimaryOnly()
    {
        $cursor = $this->collection
            ->find()
            ->readPrimaryOnly();

        $this->assertEquals(
            array(
                'type'      => \MongoClient::RP_PRIMARY,
                'tagsets' => array(),
            ),
            $cursor->getReadPreference()
        );

        $cursor->rewind();

        $this->assertEquals(
            array(
                'type' => \MongoClient::RP_PRIMARY,
            ),
            $cursor->getReadPreference()
        );
    }

    public function testReadPrimaryPreferred()
    {
        $cursor = $this->collection
            ->find()
            ->readPrimaryPreferred(array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ));

        $cursor->rewind();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $cursor->getReadPreference());
    }

    public function testReadSecondaryOnly(array $tags = null)
    {
        $cursor = $this->collection
            ->find()
            ->readSecondaryOnly(array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ));

        $cursor->rewind();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $cursor->getReadPreference());
    }

    public function testReadSecondaryPreferred(array $tags = null)
    {
        $cursor = $this->collection
            ->find()
            ->readSecondaryPreferred(array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ));

        $cursor->rewind();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $cursor->getReadPreference());
    }

    public function testReadNearest(array $tags = null)
    {
        $cursor = $this->collection
            ->find()
            ->readNearest(array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ));

        $cursor->rewind();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_NEAREST,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $cursor->getReadPreference());
    }

    public function testHint()
    {
        // create index
        $this->collection->ensureIndex(array('a' => 1));
        $this->collection->ensureIndex(array('a' => 1, 'b' => 1));

        // add documents
        $this->collection
            ->insert(array(
                'a' => 1
            ))
            ->insert(array(
                'a' => 1, 'b' => 1
            ));

        // without hint
        $explainWithoutHint = $this
            ->collection
            ->find()
            ->where('a', 1)
            ->explain();
        
        // with hint
        $explainWithHint = $this
            ->collection
            ->find()
            ->hint(array('a' => 1, 'b' => 1))
            ->where('a', 1)
            ->explain();

        $currentVersion = $this->collection->getDatabase()->getClient()->getDbVersion();
        if(version_compare($currentVersion, '3', '<')) {
            $this->assertEquals('BtreeCursor a_1', $explainWithoutHint['cursor']);
            $this->assertEquals('BtreeCursor a_1_b_1', $explainWithHint['cursor']);
        } else {
            $this->assertEquals('a_1', $explainWithoutHint['queryPlanner']['winningPlan']['inputStage']['indexName']);
            $this->assertEquals('a_1_b_1', $explainWithHint['queryPlanner']['winningPlan']['inputStage']['indexName']);
        }


    }

    public function testMoveToCollection()
    {
        $targetCollectionName = 'targetMoveCollection';

        $targetCollection = $this
            ->collection
            ->getDatabase()
            ->getCollection($targetCollectionName)
            ->delete();

        // fill collection with documents
        for($i = 0; $i < 200; $i++) {
            $this->collection->createDocument(array('param' => $i))->save();
        }

        // move docs
        $this->collection
            ->find()
            ->whereMod('param', 2, 0)
            ->moveToCollection($targetCollectionName);

        // check source collection
        $this->assertEquals(
            100,
            $this->collection->count(),
            'Count of documents in source collection must be 100'
        );

        foreach($this->collection->find() as $document) {
            $this->assertEquals(1, $document->param % 2);
        }

        // check target collection

        $this->assertEquals(
            100,
            $targetCollection->count(),
            'Nothing moved to target collection'
        );

        foreach($targetCollection->find() as $document) {
            $this->assertEquals(0, $document->param % 2);
        }

        // clear
        $targetCollection->delete();
    }

    public function testGetHash()
    {
        $queryBuilder = $this->collection
            ->find()
            ->field('_id')
            ->field('ineterests')
            ->sort(array(
                'age' => 1,
                'gender' => -1,
            ))
            ->limit(10, 20)
            ->whereAll('interests', array('php', 'snowboard'));

        $this->assertEquals(
            '508cc93b371c222c53ae90989d95caae',
            $queryBuilder->getHash()
        );
    }

    public function testHashEquals()
    {
        $queryBuilder1 = $this->collection
            ->find()
            ->field('_id')
            ->field('ineterests')
            ->sort(array(
                'age' => 1,
                'gender' => -1,
            ))
            ->limit(10, 20)
            ->whereAll('interests', array('php', 'snowboard'));

        $queryBuilder2 = $this->collection
            ->find()
            ->sort(array(
                'gender' => -1,
                'age' => 1,
            ))
            ->field('ineterests')
            ->whereAll('interests', array('php', 'snowboard'))
            ->field('_id')
            ->limit(10, 20);

        $queryBuilder3 = $this->collection
            ->find()
            ->sort(array(
                'age' => 1,
            ))
            ->whereAll('interests', array('php', 'snowboard'))
            ->field('_id')
            ->limit(10, 20);

        $this->assertEquals(
            $queryBuilder1->getHash(),
            $queryBuilder2->getHash()
        );

        $this->assertNotEquals(
            $queryBuilder1->getHash(),
            $queryBuilder3->getHash()
        );
    }

    public function testSetClientTimeoutInConstructor()
    {
        $cursor = new Cursor($this->collection, array(
            'clientTimeout' => 42,
        ));

        $this->assertEquals(42, $cursor->getOption('clientTimeout'));
    }

    public function testSetClientTimeoutInSetter()
    {
        $cursor = new Cursor($this->collection);
        $cursor->setClientTimeout(42);

        $this->assertEquals(42, $cursor->getOption('clientTimeout'));
    }

    public function testSetClientTimeoutInMapping()
    {
        $this->database->map('col', array(
            'cursorClientTimeout' => 42,
        ));

        $cursor = $this->database->getCollection('col')->find();

        $this->assertEquals(42, $cursor->getOption('clientTimeout'));
    }

    public function testSetServerTimeoutInConstructor()
    {
        $cursor = new Cursor($this->collection, array(
            'serverTimeout' => 42,
        ));

        $this->assertEquals(42, $cursor->getOption('serverTimeout'));
    }

    public function testSetServerTimeoutInSetter()
    {
        $cursor = new Cursor($this->collection);
        $cursor->setServerTimeout(42);

        $this->assertEquals(42, $cursor->getOption('serverTimeout'));
    }

    public function testSetServerTimeoutInMapping()
    {
        $this->database->map('col', array(
            'cursorServerTimeout' => 42,
        ));

        $cursor = $this->database->getCollection('col')->find();

        $this->assertEquals(42, $cursor->getOption('serverTimeout'));
    }
}
