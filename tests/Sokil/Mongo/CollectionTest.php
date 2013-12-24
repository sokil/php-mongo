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
    
    public function testSaveValidDocument()
    {
        // create document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));
        
        $document->set('some-field-name', 'some-value');
        
        // save document
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->saveDocument($document);
        
        $collection->delete();
    }
    
    /**
     * @expectedException \Sokil\Mongo\Document\Exception\Validate
     */
    public function testSaveInvalidDocument()
    {
        // create document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));
        
        // save document
        $collection = self::$database->getCollection('phpmongo_test_collection');
        $collection->saveDocument($document);
        
        $collection->delete();
    }
    
    public function testDeleteUnexistedColelction()
    {
        $collection = self::$database->getCollection('UNEXISTED_COLLECTION_NAME');
        $collection->delete();
    }
}