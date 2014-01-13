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
}