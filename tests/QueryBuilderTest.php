<?php

namespace Sokil\Mongo;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
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
        self::$collection = $database
            ->getCollection('phpmongo_test_collection');
    }
    
    public function testReturnSpecifiedFields()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'a'    => 'a1',
            'b'    => 'b1',
            'c'    => 'c1',
            'd'    => 'd1',
        ));
        
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // fild some fields of document
        $document = self::$collection->find()
            ->fields(array(
                'a', 'c'
            ))
            ->field('b')
            ->findOne();
        
        $this->assertEquals(array(
            'a'    => 'a1',
            'b'    => 'b1',
            'c'    => 'c1',
            '_id'   => $documentId,
        ), $document->toArray());
    }
    
    public function testSkipSpecifiedFields()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'a'    => 'a1',
            'b'    => 'b1',
            'c'    => 'c1',
            'd'    => 'd1',
        ));
        
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // fild some fields of document
        $document = self::$collection->find()
            ->skipFields(array(
                'a', 'c'
            ))
            ->skipField('b')
            ->findOne();
        
        $this->assertEquals(array(
            'd'    => 'd1',
            '_id'   => $documentId,
        ), $document->toArray());
    }
    
    /**
     * @expectedException \MongoCursorException
     */
    public function testErrorOnAcceptedAndSkippedFieldsPassed()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'a'    => 'a1',
            'b'    => 'b1',
            'c'    => 'c1',
            'd'    => 'd1',
        ));
        
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // fild some fields of document
        $document = self::$collection->find()
            ->fields(array(
                'a', 'c'
            ))
            ->skipField('b')
            ->findOne();
    }
    
    public function testSlice()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'key'    => array('a', 'b', 'c', 'd', 'e', 'f'),
        ));
        
        self::$collection->saveDocument($document);
        
        // only limit defined
        $this->assertEquals(array('a', 'b'), self::$collection->find()->slice('key', 2)->findOne()->key);
        $this->assertEquals(array('e', 'f'), self::$collection->find()->slice('key', -2)->findOne()->key);
        
        // limit and skip defined
        $this->assertEquals(array('c'), self::$collection->find()->slice('key', 1, 2)->findOne()->key);
        $this->assertEquals(array('e'), self::$collection->find()->slice('key', 1, -2)->findOne()->key);
    }
    
    public function testFindOne()
    {
        // create new document
        self::$collection->delete();
        
        self::$collection
            ->createDocument(array(
                'someField'    => 'A',
            ))
            ->save();

        self::$collection
            ->createDocument(array(
                'someField'    => 'B',
            ))
            ->save();

        $documentId = self::$collection
            ->createDocument(array(
                'someField'    => 'C',
            ))
            ->save()
            ->getId();
        
        // find existed row
        $document = self::$collection
            ->find()
            ->where('someField', 'C')
            ->findOne();

        $this->assertEquals($documentId, $document->getId());
        
        // find unexisted row
        $document = self::$collection
            ->find()
            ->where('some-unexisted-field', 'some-value')
            ->findOne();

        $this->assertEmpty($document);
    }

    public function testQuery()
    {
        $exp1 = new Expression;
        $exp1->whereGreater('b', 1);

        $exp2 = new Expression;
        $exp1->whereLess('b', 20);

        $query = self::$collection->find();
        $query->query($exp1);
        $query->query($exp2);

        $this->assertEquals(array(
            'b' => array(
                '$gt' => 1,
                '$lt' => 20,
            ),
        ), $query->toArray());
    }

    public function testMixedToMongoIdList()
    {
        self::$collection->delete();

        // create new document
        $document = self::$collection
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
        self::$collection->delete();

        // create new document
        $document1Id = self::$collection
            ->createDocument(array(
                'p'    => 1,
            ))
            ->save()
            ->getId();

        // create new document
        $document2Id = self::$collection
            ->createDocument(array(
                'p'    => 1,
            ))
            ->save()
            ->getId();

        $this->assertEquals(array(
            $document1Id,
            $document2Id,
        ), self::$collection
            ->find()
            ->sort(array('p' => 1))
            ->getIdList()
        );
    }

    public function testFindRandom()
    {
        self::$collection->delete();
        
        // create new document
        $document1 = self::$collection->createDocument(array(
            'p1'    => 'v',
            'p2'    => 'doc1',
        ));
        self::$collection->saveDocument($document1);
        
        $document2 = self::$collection->createDocument(array(
            'p1'    => 'v',
            'p2'    => 'doc2',
        ));
        self::$collection->saveDocument($document2);
        
        $document3 = self::$collection->createDocument(array(
            'p1'    => 'other_v',
            'p2'    => 'doc3',
        ));
        self::$collection->saveDocument($document3);
        
        // find unexisted random document
        $document = self::$collection->find()->where('pZZZ', 'v')->findRandom();
        $this->assertEmpty($document);
        
        // find random documents if only one document match query
        $document = self::$collection->find()->where('p1', 'other_v')->findRandom();
        $this->assertEquals($document->getId(), $document3->getId());
        
        // find random document among two existed documents
        $document = self::$collection->find()->where('p1', 'v')->findRandom();
        $this->assertTrue(in_array($document->getId(), array($document1->getId(), $document2->getId())));
    }
    
    public function testFindAll()
    {
        // create new document
        self::$collection->delete();
        
        // add doc
        $document = self::$collection->createDocument(array(
            'some-field'    => 'some-value',
        ));
        self::$collection->saveDocument($document);
        
        // find
        $documents = self::$collection->find()->where('some-field', 'some-value');
        
        $firstDocument = current($documents->findAll());
        $this->assertEquals($firstDocument->getId(), $document->getId());
    }
    
    public function testPluck_findAsObject_SimpleField()
    {
        self::$collection
            ->delete()
            ->insertMultiple(array(
                array('field' => 1),
                array('field' => 2),
                array('field' => 3),
                array('field' => 4),
            ));
        
        $this->assertEquals(
            array(1, 2, 3, 4),
            array_values(self::$collection->find()->pluck('field'))
        );
    }
    
    public function testPluck_findAsObject_CompoundField()
    {
        self::$collection
            ->delete()
            ->insertMultiple(array(
                array('field' => array('f1' => 'a1', 'f2' => 'b1')),
                array('field' => array('f1' => 'a2', 'f2' => 'b2')),
                array('field' => array('f1' => 'a3', 'f2' => 'b3')),
                array('field' => array('f1' => 'a4', 'f2' => 'b4')),
            ));
        
        $this->assertEquals(
            array('b1', 'b2', 'b3', 'b4'),
            array_values(self::$collection->find()->pluck('field.f2'))
        );
    }
    
    public function testPluck_findAsArray_SimpleField()
    {
        self::$collection
            ->delete()
            ->insertMultiple(array(
                array('field' => 1),
                array('field' => 2),
                array('field' => 3),
                array('field' => 4),
            ));
        
        $this->assertEquals(
            array(1, 2, 3 ,4),
            array_values(self::$collection->findAsArray()->pluck('field'))
        );
    }
    
    public function testPluck_findAsArray_CompoundField()
    {
        self::$collection
            ->delete()
                ->insertMultiple(array(
                array('field' => array('f1' => 'a1', 'f2' => 'b1')),
                array('field' => array('f1' => 'a2', 'f2' => 'b2')),
                array('field' => array('f1' => 'a3', 'f2' => 'b3')),
                array('field' => array('f1' => 'a4', 'f2' => 'b4')),
            ));
        
        $this->assertEquals(
            array('b1', 'b2', 'b3', 'b4'),
            array_values(self::$collection->findAsArray()->pluck('field.f2'))
        );
    }
    
    public function testReturnAsArray()
    {
        // create new document
        self::$collection->delete();
        
        $document = self::$collection->createDocument(array(
            'some-field'    => 'some-value',
        ));
        
        self::$collection->saveDocument($document);
        
        // find all rows
        $document = self::$collection->findAsArray()->where('some-field', 'some-value')->rewind()->current();
        $this->assertEquals('array', gettype($document));
        
        // find one row
        $document = self::$collection->findAsArray()->where('some-field', 'some-value')->findOne();
        $this->assertEquals('array', gettype($document));
        
    }

    public function testSort()
    {
        self::$collection->createDocument(array('p' => 'A'))->save();
        self::$collection->createDocument(array('p' => 'B'))->save();
        self::$collection->createDocument(array('p' => 'C'))->save();

        $documentId = self::$collection
            ->createDocument(array('p' => 'D'))
            ->save()
            ->getId();

        $document = self::$collection
            ->find()
            ->sort(array('p' => -1));

        $this->assertEquals('D', $document->current()->p);
    }

    public function testLogger()
    {
        self::$collection->delete();

        // create documents
        self::$collection->createDocument(array('param' => 1))->save();
        self::$collection->createDocument(array('param' => 2))->save();
        self::$collection->createDocument(array('param' => 3))->save();
        self::$collection->createDocument(array('param' => 4))->save();

        // create logger
        $logger = $this->getMock('\Psr\Log\LoggerInterface');
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('Sokil\Mongo\QueryBuilder: {"collection":"phpmongo_test_collection","query":{"param":2},"project":[],"sort":[]}');

        // set logger to client
        self::$collection
            ->getDatabase()
            ->getClient()
            ->setLogger($logger);

        // aggregate
        self::$collection->find()->where('param', 2)->findAll();
    }
    
    public function testSearchInArrayField()
    {
        // create document
        $document = self::$collection->createDocument();
        
        $document->push('param', 'value1');
        $document->push('param', 'value2');
        
        self::$collection->saveDocument($document);
        
        // find document
        $document = self::$collection->find()->where('param', 'value1')->findOne();
        
        $this->assertEquals(array('value1', 'value2'), $document->param);
    }
    
    public function testWhereIn()
    {
        // create new document
        self::$collection->delete();
        
        $document = self::$collection->createDocument(array(
            'param'    => 'value1',
        ));
        
        self::$collection->saveDocument($document);
        
        $documentId = $document->getId();
        
        // find all rows
        $document = self::$collection->find()
            ->whereIn('param', array('value1', 'value2', 'value3'))
            ->findOne();
        
        $this->assertNotEmpty($document);
        
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereEmpty()
    {
        // create new document
        self::$collection->delete();
        
        $document = self::$collection->createDocument(array(
            'f_null'      => null,
            'f_string'    => '',
            'f_array'     => array(),
        ));
        
        self::$collection->saveDocument($document);
        
        $documentId = $document->getId();
        
        // find all rows
        $this->assertEquals($documentId, self::$collection->find()->whereEmpty('f_null')->findOne()->getId());
        $this->assertEquals($documentId, self::$collection->find()->whereEmpty('f_string')->findOne()->getId());
        $this->assertEquals($documentId, self::$collection->find()->whereEmpty('f_array')->findOne()->getId());
        $this->assertEquals($documentId, self::$collection->find()->whereEmpty('f_unexisted_field')->findOne()->getId());
    }

    public function testWhereNotEmpty()
    {
        // create new document
        self::$collection->delete();

        // null field
        self::$collection
            ->createDocument(array(
                'param'    => null,
            ))
            ->save();

        // empty array field
        self::$collection
            ->createDocument(array(
                'param'    => array(),
            ))
            ->save();

        // empty string field
        self::$collection
            ->createDocument(array(
                'param'    => '',
            ))
            ->save();

        // NOT EMPTY FIELD
        $documentId = self::$collection
            ->createDocument(array(
                'param'    => 'value',
            ))
            ->save()
            ->getId();

        // unexisted field
        self::$collection
            ->createDocument(array(
                'fieldName'    => 'value',
            ))
            ->save();

        // find all rows
        $documents = self::$collection
            ->find()
            ->whereNotEmpty('param')
            ->findAll();

        $this->assertNotEmpty($documents);

        $this->assertEquals(1, count($documents));

        $document = current($documents);

        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereNotIn()
    {
        // create new document
        self::$collection->delete();
        
        $document = self::$collection->createDocument(array(
            'param'    => 'value',
        ));
        
        self::$collection->saveDocument($document);
        
        $documentId = $document->getId();
        
        // find all rows
        $document = self::$collection->find()
            ->whereNotIn('param', array('value1', 'value2', 'value3'))
            ->findOne();
        
        $this->assertNotEmpty($document);
        
        $this->assertEquals($documentId, $document->getId());
    }

    public function testWhereArraySize()
    {
        // create new document
        self::$collection->delete();

        self::$collection
            ->createDocument(array(
                'param'    => array(),
            ))
            ->save();

        self::$collection
            ->createDocument(array(
                'param'    => array('value1'),
            ))
            ->save();

        $documentId = self::$collection
            ->createDocument(array(
                'param'    => array('value1', 'value2'),
            ))
            ->save()
            ->getId();

        self::$collection
            ->createDocument(array(
                'param'    => array('value1', 'value2', 'value3'),
            ))
            ->save();

        // find all rows
        $document = self::$collection->find()
            ->whereArraySize('param', 2)
            ->findOne();

        $this->assertNotEmpty($document);

        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereLike()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => 'abcd',
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // find all rows
        $document = self::$collection->find()
            ->whereLike('param', 'ab[a-z]{2}')
            ->findOne();
        
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testFileType()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'f_double'          => 1.1,
            'f_string'          => 'string',
            'f_object'          => array('key' => 'value'),
            'f_array'           => array(1, 2, 3),
            'f_array_of_array'  => array(array(1, 2), array(3, 4)),
            'f_objectId'        => new \MongoId,
            'f_boolean'         => false,
            'f_date'            => new \MongoDate,
            'f_null'            => null,
        ));
        self::$collection->saveDocument($document);
        
        $this->assertNotEmpty(self::$collection->find()->whereDouble('f_double')->findOne());
        $this->assertNotEmpty(self::$collection->find()->whereString('f_string')->findOne());
        $this->assertNotEmpty(self::$collection->find()->whereObject('f_object')->findOne());
        $this->assertNotEmpty(self::$collection->find()->whereArray('f_array')->findOne());
        $this->assertNotEmpty(self::$collection->find()->whereArrayOfArrays('f_array_of_array')->findOne());
        $this->assertNotEmpty(self::$collection->find()->whereObjectId('f_objectId')->findOne());
        $this->assertNotEmpty(self::$collection->find()->whereBoolean('f_boolean')->findOne());
        $this->assertNotEmpty(self::$collection->find()->whereDate('f_date')->findOne());
        $this->assertNotEmpty(self::$collection->find()->whereNull('f_null')->findOne());
    }
    
    public function testCombinedWhereWithLikeAndNotIn()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => 'abcd',
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // try to found - must be empty result
        $document= self::$collection->find()
            ->whereLike('param', 'wrongregex[a-z]{2}')
            ->whereNotIn('param', array('abzz'))
            ->findOne();
        
        $this->assertEmpty($document);
        
        // try to found - must be empty result
        $document= self::$collection->find()
            ->whereLike('param', 'ab[a-z]{2}')
            ->whereNotIn('param', array('abcd'))
            ->findOne();
        
        $this->assertEmpty($document);
        
        // try to found - must be one result
        $document = self::$collection->find()
            ->whereLike('param', 'ab[a-z]{2}')
            ->whereNotIn('param', array('abzz'))
            ->findOne();
        
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereOr()
    {
        self::$collection->delete();
        
        // create new document
        $document1 = self::$collection->createDocument(array(
            'param1'    => 'p11',
            'param2'    => 'p12',
        ));
        self::$collection->saveDocument($document1);
        $document1Id = $document1->getId();
        
        $document2 = self::$collection->createDocument(array(
            'param1'    => 'p21',
            'param2'    => 'p22',
        ));
        self::$collection->saveDocument($document2);
        $document2Id = $document2->getId();
        
        // find
        $q1 = self::$collection->find();
        $this->assertEquals($document1Id, $q1->whereOr(
            $q1->expression()->where('param1', 'p11')->where('param2', 'p12'),
            $q1->expression()->where('param1', 'p11')->where('some', 'some')
        )->findOne()->getId());
        
        $q2 = self::$collection->find();
        $this->assertEquals($document2Id, $q2->whereOr(
            $q2->expression()->where('param1', 'p21'),
            $q2->expression()->where('param', '2')
        )->findOne()->getId());
    }
    
    public function testWhereNor()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => '1',
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // find
        $q = self::$collection->find();
        $this->assertEquals($documentId, $q->whereNor(
            $q->expression()->whereGreater('param', 100)->where('some', 'some'),
            $q->expression()->where('param', 5)
        )->findOne()->getId());
    }
    
    public function testWhereNot()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => '1',
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // scalar value
        $q1 = self::$collection->find();
        $this->assertEquals($documentId, $q1->whereNot(
            $q1->expression()->where('param', 2)
        )->findOne()->getId());
        
        // operator-expression
        $q2 = self::$collection->find();
        $this->assertEquals($documentId, $q2->whereNot(
            $q2->expression()->whereGreater('param', 5)
        )->findOne()->getId());
    }
    
    public function testWhereElemMatch()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => array(
                array(
                    'subparam1'    => 10,
                    'subparam2'    => 20,
                ),
                array(
                    'subparam1'    => 200,
                    'subparam2'    => 300,
                ),
            ),
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // find
        $q = self::$collection->find();
        
        $search = $q->whereElemMatch('param', 
            $q->expression()
                // param.sub-param1
                ->whereGreater('subparam1', 0)
                // param.sub-param2
                ->whereLess('subparam2', 30)
                ->whereGreater('subparam2', 10)
        );
    
        $document = $search->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereElemNotMatch()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => array(
                array(
                    'subparam1'    => 10,
                    'subparam2'    => 20,
                ),
                array(
                    'subparam1'    => 200,
                    'subparam2'    => 300,
                ),
            ),
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // find
        $q = self::$collection->find();
        
        $search = $q->whereElemNotMatch('param', 
            $q->expression()->where('subparam1', 10000)
        );
    
        $document = $search->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereElemMatchWithoutHelpers()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => array(
                array(
                    'subparam1'    => 10,
                    'subparam2'    => 20,
                ),
                array(
                    'subparam1'    => 200,
                    'subparam2'    => 300,
                ),
            ),
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // find
        $search = self::$collection->find()->where('param', array('$elemMatch' => array(
            'subparam1' => array(
                '$gt'   => 0,
            ),
            'subparam2' => array(
                '$lt'   => 30,
                '$gt'   => 10,
            )
        )));
    
        $document = $search->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereElemMatchWithLogicalOr()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => array(
                array(
                    'subparam1'    => 10,
                    'subparam2'    => 20,
                ),
                array(
                    'subparam1'    => 200,
                    'subparam2'    => 300,
                ),
            ),
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // find
        $q = self::$collection->find();
        $search = $q->whereOr(
            $q->expression()->whereElemMatch('param', 
                $q->expression()
                    // param.sub-param1
                    ->whereGreater('subparam1', 0)
                    // param.sub-param2
                    ->whereLess('subparam2', 30)
                    ->whereGreater('subparam2', 10)
            ),
            $q->expression()->whereElemMatch('param', 
                $q->expression()
                    // param.sub-param1
                    ->whereGreater('subparam1', 100)
                    // param.sub-param2
                    ->whereLess('subparam2', 400)
                    ->whereGreater('subparam2', 100)    
            )
        );
    
        $document = $search->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereElemMatchWithLogicalAnd()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => array(
                array(
                    'subparam1'    => 10,
                    'subparam2'    => 20,
                ),
                array(
                    'subparam1'    => 200,
                    'subparam2'    => 300,
                ),
            ),
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // find
        $q = self::$collection->find();
        $search = $q->whereAnd(
            $q->expression()->whereElemMatch('param', 
                $q->expression()
                    // param.sub-param1
                    ->whereGreater('subparam1', 0)
                    // param.sub-param2
                    ->whereLess('subparam2', 30)
                    ->whereGreater('subparam2', 10)
            ),
            $q->expression()->whereElemMatch('param', 
                $q->expression()
                    // param.sub-param1
                    ->whereGreater('subparam1', 100)
                    // param.sub-param2
                    ->whereLess('subparam2', 400)
                    ->whereGreater('subparam2', 100)    
            )
        );
    
        $document = $search->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereElemMatchWithLogicalNot()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => array(
                array(
                    'subparam1'    => 10,
                    'subparam2'    => 20,
                ),
                array(
                    'subparam1'    => 200,
                    'subparam2'    => 300,
                ),
            ),
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // find
        $q = self::$collection->find();
        $q->whereNot($q->expression()->whereElemMatch('param', $q->expression()->where('subparam1', 777)));
        
        $document = $q->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($documentId, $document->getId());
    }
    
    /**
     * Merging situations when all except values exual - use $in
     */
    public function _testWhereElemMatchByANDWithLogicalAnd()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'param'    => array(
                array(
                    'subparam1'    => 10,
                    'subparam2'    => 20,
                ),
                array(
                    'subparam1'    => 200,
                    'subparam2'    => 300,
                ),
            ),
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // find
        $q = self::$collection->find();
        $search = $q
            ->whereElemMatch('param', 
                $q->expression()
                    // param.sub-param1
                    ->whereGreater('subparam1', 0)
                    // param.sub-param2
                    ->whereLess('subparam2', 30)
                    ->whereGreater('subparam2', 10)
            )->whereElemMatch('param', 
                $q->expression()
                    // param.sub-param1
                    ->whereGreater('subparam1', 100)
                    // param.sub-param2
                    ->whereLess('subparam2', 400)
                    ->whereGreater('subparam2', 100)    
            );
    
        $document = $search->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereFieldExists()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'fieldName'    => '1',
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        // scalar value
        $this->assertEmpty(self::$collection->find()->whereExists('unexistedFieldName')->findOne());
        $this->assertEquals($documentId, self::$collection->find()->whereExists('fieldName')->findOne()->getId());
    }
    
    public function testWhereFieldNotExists()
    {
        self::$collection->delete();
        
        // create new document
        $document = self::$collection->createDocument(array(
            'fieldName'    => '1',
        ));
        self::$collection->saveDocument($document);
        $documentId = $document->getId();
        
        $this->assertEmpty(self::$collection->find()->whereNotExists('fieldName')->findOne());
        
        $this->assertEquals($documentId, self::$collection->find()->whereNotExists('unexistedFieldName')->findOne()->getId());
    }
    
    public function testToArray()
    {
        // create new document
        self::$collection->delete();
        
        // find
        $query = self::$collection->find()->where('some-field', 'some-value');
        $queryArray = $query->toArray();
        
        $this->assertInternalType('array', $queryArray);
        
        $this->assertEquals(array(
            'some-field' => 'some-value'
        ), $queryArray);
    }

    public function testWhereGreaterOrEqual()
    {
        self::$collection->delete();

        for($i = 0; $i < 10; $i++) {
            self::$collection->createDocument(array('p' => $i))->save();
        }

        $documents = self::$collection->find()->whereGreaterOrEqual('p', 4)->findAll();
        $this->assertEquals(6, count($documents));
        foreach($documents as $document) {
            $this->assertGreaterThanOrEqual(4, $document->p);
        }
    }

    public function testWhereLessOrEqual()
    {
        self::$collection->delete();

        for($i = 0; $i < 10; $i++) {
            self::$collection->createDocument(array('p' => $i))->save();
        }

        $documents = self::$collection->find()->whereLessOrEqual('p', 4)->findAll();
        $this->assertEquals(5, count($documents));
        foreach($documents as $document) {
            $this->assertLessThanOrEqual(4, $document->p);
        }
    }

    public function testWhereAll()
    {
        self::$collection->delete();

        $documentId = self::$collection
            ->createDocument(array('p' => array(
                1, 2, 3, 4, 5
            )))
            ->save()
            ->getId();

        self::$collection->createDocument(array('p' => array(
            3, 4, 5, 6, 7
        )))->save();

        $documents = self::$collection
            ->find()
            ->whereAll('p', array(1, 4))
            ->findAll();

        $this->assertEquals(1, count($documents));

        $document = current($documents);
        $this->assertEquals($documentId, $document->getId());
    }
    
    /**
     * @covers \Sokil\Mongo\QueryBuilder::map
     */
    public function testMap()
    {
        // create new document
        self::$collection->delete();
        
        self::$collection->createDocument(array(
            'param'    => 'value1',
        ))->save();
        
        self::$collection->createDocument(array(
            'param'    => 'value2',
        ))->save();
        
        // test
        $result = self::$collection->find()->map(function(Document $document) {
            return $document->param;
        });
        
        $this->assertEquals(array('value1', 'value2'), array_values($result));
    }
    
    /**
     * @covers \Sokil\Mongo\QueryBuilder::filter
     */
    public function testFilter()
    {
        // create new document
        self::$collection->delete();
        
        self::$collection->createDocument(array(
            'param'    => 'value1',
        ))->save();
        
        self::$collection->createDocument(array(
            'param'    => 'value2',
        ))->save();
        
        // test
        $result = self::$collection->find()->filter(function(Document $document) {
            return $document->param == 'value1';
        });
        
        $this->assertEquals('value1', current($result)->param);
    }
    
    public function testFindAndUpdate()
    {
        self::$collection->delete();
        
        $d11 = self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = self::$collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $document = self::$collection->find()
            ->where('param1', 1)
            ->sort(array(
                'param2' => 1,
            ))
            ->findAndUpdate(self::$collection->operator()->set('newParam', '777'));
        
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
        self::$collection->delete();

        $document = self::$collection
            ->find()
            ->where('param1', 1)
            ->findAndUpdate(
                self::$collection->operator()->set('newParam', '777')
            );

        $this->assertNull($document);
    }
    
    public function testFindAndRemove()
    {
        self::$collection->delete();
        
        $d11 = self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        $d12 = self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        $d21 = self::$collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        $d22 = self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $document = self::$collection->find()
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
        
        $this->assertEquals(3, count(self::$collection));
    }

    public function testLimit()
    {
        self::$collection->delete();

        self::$collection->createDocument(array('param' => 1))->save();
        self::$collection->createDocument(array('param' => 2))->save();
        self::$collection->createDocument(array('param' => 3))->save();
        self::$collection->createDocument(array('param' => 4))->save();

        $list = self::$collection
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

    public function testCount()
    {
        self::$collection->delete();
        
        self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        self::$collection->createDocument(array('param1' => 1, 'param2' => 3))->save();
        self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $queryBuilder = self::$collection
            ->find()
            ->where('param1', 1)
            ->limit(1)
            ->skip(1);
        
        $this->assertEquals(3, count($queryBuilder));
    }
    
    public function testLimitedCount()
    {
        self::$collection->delete();
        
        self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        self::$collection->createDocument(array('param1' => 1, 'param2' => 3))->save();
        self::$collection->createDocument(array('param1' => 2, 'param2' => 1))->save();
        self::$collection->createDocument(array('param1' => 2, 'param2' => 2))->save();
        
        $queryBuilder = self::$collection
            ->find()
            ->where('param1', 2)
            ->limit(10)
            ->skip(1);
        
        $this->assertEquals(1, $queryBuilder->limitedCount());
    }
    
    public function testExplain()
    {
        self::$collection->delete();
        
        self::$collection->createDocument(array('param1' => 1, 'param2' => 1))->save();
        self::$collection->createDocument(array('param1' => 1, 'param2' => 2))->save();
        
        $explain = self::$collection
            ->find()
            ->where('param1', 2)
            ->explain();
        
        $this->assertArrayHasKey('cursor', $explain);
    }

    public function testReadPrimaryOnly()
    {
        $qb = self::$collection
            ->find()
            ->readPrimaryOnly();

        $this->assertEquals(array(
            'type'      => \MongoClient::RP_PRIMARY,
            'tagsets' => array(),
        ), $qb->getReadPreference());

        $qb->current();

        $this->assertEquals(array(
            'type'      => \MongoClient::RP_PRIMARY,
        ), $qb->getReadPreference());
    }

    public function testReadPrimaryPreferred()
    {
        $qb = self::$collection
            ->find()
            ->readPrimaryPreferred(array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ));

        $qb->current();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $qb->getReadPreference());
    }

    public function testReadSecondaryOnly(array $tags = null)
    {
        $qb = self::$collection
            ->find()
            ->readSecondaryOnly(array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ));

        $qb->current();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $qb->getReadPreference());
    }

    public function testReadSecondaryPreferred(array $tags = null)
    {
        $qb = self::$collection
            ->find()
            ->readSecondaryPreferred(array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ));

        $qb->current();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $qb->getReadPreference());
    }

    public function testReadNearest(array $tags = null)
    {
        $qb = self::$collection
            ->find()
            ->readNearest(array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ));

        $qb->current();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_NEAREST,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $qb->getReadPreference());
    }
}