<?php

namespace Sokil\Mongo;

class ClientTest extends \PHPUnit_Framework_TestCase
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
    
    public static function tearDownAfterClass() {
        self::$collection->delete();
    }
    
    public function testUsecase() 
    {        
        /**
         * Create document
         */
        
        // create document
        $document = self::$collection->createDocument(array(
            'l1'   => array(
                'l11'   => 'l11value',
                'l12'   => 'l12value',
            ),
            'l2'   => array(
                'l21'   => 'l21value',
                'l22'   => 'l22value',
            ),
        ));
        
        // insert document
        self::$collection->saveDocument($document);
        $documentId = (string) $document->getId();
        
        // test
        $this->assertNotEmpty($documentId);
        
        /**
         * Update document
         */
        
        // update
        $document->set('l1.l12', 'updated');
        $document->set('l3', 'add new key');
        self::$collection->saveDocument($document);
        
        // test
        $document = self::$collection->getDocument($documentId);
        
        $this->assertEquals('updated', $document->get('l1.l12'));
        $this->assertEquals('add new key', $document->get('l3'));
        
        /**
         * Delete document
         */
        self::$collection->deleteDocument($document);
        
        $this->assertEmpty(self::$collection->getDocument($documentId));
    }
}