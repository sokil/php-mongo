<?php

namespace Sokil\Mongo;

class ExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Collection
     */
    private $collection;

    public function setUp()
    {
        // connect to mongo
        $client = new Client();
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        $this->collection = $database->getCollection('phpmongo_test_collection');
    }
    
    public function tearDown()
    {
        $this->collection->delete();
    }
    
    public function testMerge()
    {
        $expression1 = new Expression;
        $expression1->where('a', 77);
        $expression1->where('b', 88);
        $expression1->whereGreater('c', 99);

        $expression2 = new Expression;
        $expression2->where('a', 55);
        $expression1->whereLess('c', 66);

        $expression1->merge($expression2);

        $this->assertEquals(array(
            'a' => array(77, 55),
            'b' => 88,
            'c' => array(
                '$gt' => 99,
                '$lt' => 66,
            ),
        ), $expression1->toArray());
    }
    
    public function testSearchInArrayField()
    {
        // create document
        $document = $this->collection->createDocument();
        
        $document->push('param', 'value1');
        $document->push('param', 'value2');
        
        $document->save();
        
        // find document
        $document = $this->collection->find()->where('param', 'value1')->findOne();
        
        $this->assertEquals(array('value1', 'value2'), $document->param);
    }
    
    public function testWhereIn()
    {        
        $document = $this->collection->createDocument(array(
            'param'    => 'value1',
        ));
        
        $document->save();
        
        $documentId = $document->getId();
        
        // find all rows
        $document = $this->collection->find()
            ->whereIn('param', array('value1', 'value2', 'value3'))
            ->findOne();
        
        $this->assertNotEmpty($document);
        
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereEmpty()
    {
        
        $document = $this->collection->createDocument(array(
            'f_null'      => null,
            'f_string'    => '',
            'f_array'     => array(),
        ));
        
        $document->save();
        
        $documentId = $document->getId();
        
        // find all rows
        $this->assertEquals($documentId, $this->collection->find()->whereEmpty('f_null')->findOne()->getId());
        $this->assertEquals($documentId, $this->collection->find()->whereEmpty('f_string')->findOne()->getId());
        $this->assertEquals($documentId, $this->collection->find()->whereEmpty('f_array')->findOne()->getId());
        $this->assertEquals($documentId, $this->collection->find()->whereEmpty('f_unexisted_field')->findOne()->getId());
    }

    public function testWhereNotEmpty()
    {

        // null field
        $this->collection
            ->createDocument(array(
                'param'    => null,
            ))
            ->save();

        // empty array field
        $this->collection
            ->createDocument(array(
                'param'    => array(),
            ))
            ->save();

        // empty string field
        $this->collection
            ->createDocument(array(
                'param'    => '',
            ))
            ->save();

        // NOT EMPTY FIELD
        $documentId = $this->collection
            ->createDocument(array(
                'param'    => 'value',
            ))
            ->save()
            ->getId();

        // unexisted field
        $this->collection
            ->createDocument(array(
                'fieldName'    => 'value',
            ))
            ->save();

        // find all rows
        $documents = $this->collection
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
        $document = $this->collection->createDocument(array(
            'param'    => 'value',
        ));
        
        $document->save();
        
        $documentId = $document->getId();
        
        // find all rows
        $document = $this->collection->find()
            ->whereNotIn('param', array('value1', 'value2', 'value3'))
            ->findOne();
        
        $this->assertNotEmpty($document);
        
        $this->assertEquals($documentId, $document->getId());
    }

    public function testWhereArraySize()
    {
        $this->collection
            ->createDocument(array(
                'param'    => array(),
            ))
            ->save();

        $this->collection
            ->createDocument(array(
                'param'    => array('value1'),
            ))
            ->save();

        $documentId = $this->collection
            ->createDocument(array(
                'param'    => array('value1', 'value2'),
            ))
            ->save()
            ->getId();

        $this->collection
            ->createDocument(array(
                'param'    => array('value1', 'value2', 'value3'),
            ))
            ->save();

        // find all rows
        $document = $this->collection->find()
            ->whereArraySize('param', 2)
            ->findOne();

        $this->assertNotEmpty($document);

        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereLike()
    {
        
        // create new document
        $document = $this->collection->createDocument(array(
            'param'    => 'abcd',
        ));
        $document->save();
        $documentId = $document->getId();
        
        // find all rows
        $document = $this->collection->find()
            ->whereLike('param', 'ab[a-z]{2}')
            ->findOne();
        
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testFileType()
    {
        
        // create new document
        $document = $this->collection->createDocument(array(
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
        $document->save();
        
        $this->assertNotEmpty($this->collection->find()->whereDouble('f_double')->findOne());
        $this->assertNotEmpty($this->collection->find()->whereString('f_string')->findOne());
        $this->assertNotEmpty($this->collection->find()->whereObject('f_object')->findOne());
        $this->assertNotEmpty($this->collection->find()->whereArray('f_array')->findOne());
        $this->assertNotEmpty($this->collection->find()->whereArrayOfArrays('f_array_of_array')->findOne());
        $this->assertNotEmpty($this->collection->find()->whereObjectId('f_objectId')->findOne());
        $this->assertNotEmpty($this->collection->find()->whereBoolean('f_boolean')->findOne());
        $this->assertNotEmpty($this->collection->find()->whereDate('f_date')->findOne());
        $this->assertNotEmpty($this->collection->find()->whereNull('f_null')->findOne());
    }
    
    public function testCombinedWhereWithLikeAndNotIn()
    {
        // create new document
        $document = $this->collection->createDocument(array(
            'param'    => 'abcd',
        ));
        
        $document->save();
        $documentId = $document->getId();
        
        // try to found - must be empty result
        $document= $this->collection->find()
            ->whereLike('param', 'wrongregex[a-z]{2}')
            ->whereNotIn('param', array('abzz'))
            ->findOne();
        
        $this->assertEmpty($document);
        
        // try to found - must be empty result
        $document= $this->collection->find()
            ->whereLike('param', 'ab[a-z]{2}')
            ->whereNotIn('param', array('abcd'))
            ->findOne();
        
        $this->assertEmpty($document);
        
        // try to found - must be one result
        $document = $this->collection->find()
            ->whereLike('param', 'ab[a-z]{2}')
            ->whereNotIn('param', array('abzz'))
            ->findOne();
        
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereOr()
    {
        // create new document
        $document1 = $this->collection->createDocument(array(
            'param1'    => 'p11',
            'param2'    => 'p12',
        ));
        $document1->save();
        $document1Id = $document1->getId();
        
        $document2 = $this->collection->createDocument(array(
            'param1'    => 'p21',
            'param2'    => 'p22',
        ));
        $document2->save();
        $document2Id = $document2->getId();
        
        // find
        $q1 = $this->collection->find();
        $this->assertEquals($document1Id, $q1->whereOr(
            $q1->expression()->where('param1', 'p11')->where('param2', 'p12'),
            $q1->expression()->where('param1', 'p11')->where('some', 'some')
        )->findOne()->getId());
        
        $q2 = $this->collection->find();
        $this->assertEquals($document2Id, $q2->whereOr(
            $q2->expression()->where('param1', 'p21'),
            $q2->expression()->where('param', '2')
        )->findOne()->getId());
    }
    
    public function testWhereNor()
    {
        // create new document
        $document = $this->collection->createDocument(array(
            'param'    => '1',
        ));
        $document->save();
        $documentId = $document->getId();
        
        // find
        $q = $this->collection->find();
        $this->assertEquals($documentId, $q->whereNor(
            $q->expression()->whereGreater('param', 100)->where('some', 'some'),
            $q->expression()->where('param', 5)
        )->findOne()->getId());
    }
    
    public function testWhereNot()
    {
        // create new document
        $document = $this->collection->createDocument(array(
            'param'    => '1',
        ));
        $document->save();
        $documentId = $document->getId();
        
        // scalar value
        $q1 = $this->collection->find();
        $this->assertEquals($documentId, $q1->whereNot(
            $q1->expression()->where('param', 2)
        )->findOne()->getId());
        
        // operator-expression
        $q2 = $this->collection->find();
        $this->assertEquals($documentId, $q2->whereNot(
            $q2->expression()->whereGreater('param', 5)
        )->findOne()->getId());
    }
    
    public function testWhereElemMatch()
    {
        // create new document
        $document = $this->collection->createDocument(array(
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
        $document->save();
        $documentId = $document->getId();
        
        // find
        $q = $this->collection->find();
        
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
        // create new document
        $document = $this->collection->createDocument(array(
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
        $document->save();
        $documentId = $document->getId();
        
        // find
        $q = $this->collection->find();
        
        $search = $q->whereElemNotMatch('param', 
            $q->expression()->where('subparam1', 10000)
        );
    
        $document = $search->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($documentId, $document->getId());
    }
    
    public function testWhereElemMatchWithoutHelpers()
    {
        // create new document
        $document = $this->collection->createDocument(array(
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
        $document->save();
        $documentId = $document->getId();
        
        // find
        $search = $this->collection->find()->where('param', array('$elemMatch' => array(
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
        // create new document
        $document = $this->collection->createDocument(array(
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
        $document->save();
        $documentId = $document->getId();
        
        // find
        $q = $this->collection->find();
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
        // create new document
        $document = $this->collection->createDocument(array(
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
        $document->save();
        $documentId = $document->getId();
        
        // find
        $q = $this->collection->find();
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
        // create new document
        $document = $this->collection->createDocument(array(
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
        $document->save();
        $documentId = $document->getId();
        
        // find
        $q = $this->collection->find();
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
        // create new document
        $document = $this->collection->createDocument(array(
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
        $document->save();
        $documentId = $document->getId();
        
        // find
        $q = $this->collection->find();
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
        // create new document
        $document = $this->collection->createDocument(array(
            'fieldName'    => '1',
        ));
        $document->save();
        $documentId = $document->getId();
        
        // scalar value
        $this->assertEmpty($this->collection->find()->whereExists('unexistedFieldName')->findOne());
        $this->assertEquals($documentId, $this->collection->find()->whereExists('fieldName')->findOne()->getId());
    }
    
    public function testWhereFieldNotExists()
    {
        // create new document
        $document = $this->collection->createDocument(array(
            'fieldName'    => '1',
        ));
        $document->save();
        $documentId = $document->getId();
        
        $this->assertEmpty($this->collection->find()->whereNotExists('fieldName')->findOne());
        
        $this->assertEquals($documentId, $this->collection->find()->whereNotExists('unexistedFieldName')->findOne()->getId());
    }
    
    public function testToArray()
    {
        // find
        $query = $this->collection->find()->where('some-field', 'some-value');
        $queryArray = $query->toArray();
        
        $this->assertInternalType('array', $queryArray);
        
        $this->assertEquals(array(
            'some-field' => 'some-value'
        ), $queryArray);
    }

    public function testWhereGreaterOrEqual()
    {
        for($i = 0; $i < 10; $i++) {
            $this->collection->createDocument(array('p' => $i))->save();
        }

        $documents = $this->collection->find()->whereGreaterOrEqual('p', 4)->findAll();
        $this->assertEquals(6, count($documents));
        foreach($documents as $document) {
            $this->assertGreaterThanOrEqual(4, $document->p);
        }
    }

    public function testWhereLessOrEqual()
    {
        for($i = 0; $i < 10; $i++) {
            $this->collection->createDocument(array('p' => $i))->save();
        }

        $documents = $this->collection->find()->whereLessOrEqual('p', 4)->findAll();
        $this->assertEquals(5, count($documents));
        foreach($documents as $document) {
            $this->assertLessThanOrEqual(4, $document->p);
        }
    }

    public function testWhereMod()
    {
        for($i = 0; $i < 10; $i++) {
            $this->collection->createDocument(array(
                'i' => $i,
            ));
        }
        
        foreach($this->collection->find()->whereMod('i', 3, 0) as $document) {
            $this->assertEquals(0, $document->i % 3);
        }
    }
    
    public function testWhereNoneOf()
    {
        $documentId = $this->collection
            ->createDocument(array(
                'p' => array(
                    1, 2, 3, 4, 5
                )
            ))
            ->save();

        $documents = $this->collection
            ->find()
            ->whereNoneOf('p', array(1, 4))
            ->findAll();

        $this->assertEquals(0, count($documents));
    }

    public function testWhereText()
    {
        $this->collection->ensureFulltextIndex(
            array(
                'subject',
                'body'
            ),
            null,
            array(
                'subject' => 2,
                'body' => 1,
            )
        );

        $this->collection->batchInsert(array(
            array('subject' => 'big brown dog', 'body' => 'walking on street'),
            array('subject' => 'little pony', 'body' => 'flying among rainbows'),
        ));

        $documents = $this->collection
            ->find()
            ->whereText('pony')
            ->findAll();

        $this->assertEquals(1, count($documents));

        $document = current($documents);

        $this->assertEquals('little pony', $document->subject);
    }
}