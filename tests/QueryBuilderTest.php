<?php

namespace Sokil\Mongo;

class SearchTest extends \PHPUnit_Framework_TestCase
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
        
        $document = self::$collection->createDocument(array(
            'some-field'    => 'some-value',
        ));
        
        self::$collection->saveDocument($document);
        
        // find existed row
        $document = self::$collection->find()->where('some-field', 'some-value')->findOne();
        $this->assertNotEmpty($document);
        
        // find unexisted row
        $document = self::$collection->find()->where('some-unexisted-field', 'some-value')->findOne();
        $this->assertEmpty($document);
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
}