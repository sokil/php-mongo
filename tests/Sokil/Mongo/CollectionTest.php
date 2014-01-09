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
    
    public function testSaveValidNewDocument()
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