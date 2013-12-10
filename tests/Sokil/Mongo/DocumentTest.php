<?php

namespace Sokil\Mongo;

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
    
    public function testSetId()
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
    
    public function testIncrement()
    {
        // create document
        $doc = self::$collection->createDocument(array('i' => 100));
        self::$collection->saveDocument($doc);
        
        // increment
        $doc->increment('i', 23);
        $doc->set('j', 77);
        self::$collection->saveDocument($doc);
        
        // check
        $doc = self::$collection->getDocument($doc->getId());
        
        $this->assertEquals(123, $doc->i);
        $this->assertEquals(77, $doc->j);
    }
    
    public function testPush()
    {
        $doc = self::$collection->createDocument(array('some1' => 'some', 'some2' => 'some'));
        self::$collection->saveDocument($doc);
        
        // push single to empty
        $doc->push('key1', 1);
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(1), self::$collection->getDocument($doc->getId())->key1);
        
        // push array to empty
        $doc->push('key2', array(1));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(1), self::$collection->getDocument($doc->getId())->key2);
        
        // push single to single
        $doc->push('some1', 'another');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another'), self::$collection->getDocument($doc->getId())->some1);
        
        // push array to single
        $doc->push('some2', array('another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another'), self::$collection->getDocument($doc->getId())->some2);
        
        // push single to array
        $doc->push('some1', 'yet another');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another', 'yet another'), self::$collection->getDocument($doc->getId())->some1);
        
        // push array to array
        $doc->push('some2', array('yet another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another', 'yet another'), self::$collection->getDocument($doc->getId())->some2);
    }
}