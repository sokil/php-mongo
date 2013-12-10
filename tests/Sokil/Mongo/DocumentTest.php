<?php

namespace Sokil\Mongo;

class DocumentTest extends \PHPUnit_Framework_TestCase
{
    public function testUsecase() 
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        $collection = $database->getCollection('phpmongo_test_collection');
        
        // save document
        $id = new \MongoId();
        
        $doc = $collection->createDocument(array('a' => 'a'));
        $doc->setId($id);
        $collection->saveDocument($doc);
        
        // find document
        $this->assertNotEmpty($collection->getDocument($id));
        
        // delete document
        $collection->delete($doc);
    }
}