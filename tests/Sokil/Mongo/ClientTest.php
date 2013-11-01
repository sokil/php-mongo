<?php

namespace Sokil\Mongo;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testUsecase() 
    {
        /**
         * Connect to collection
         */
        
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        $collection = $database->getCollection('phpmongo_test_collection');
        
        /**
         * Create document
         */
        
        // create document
        $document = $collection->create(array(
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
        $collection->save($document);
        
        // test
        $documentId = (string) $document->getId();
        $this->assertNotEmpty($documentId);
        
        /**
         * Update document
         */
        
        // update
        $document->set('l1.l12', 'updated');
        $collection->save($document);
        
        // test
        $this->assertEquals($documentId, (string) $document->getId());
        
        /**
         * Read document
         */
        
        // get document
        $document = $collection->findById($documentId);
                
        // test
        $this->assertEquals('updated', $document->get('l1.l12'));
        
        /**
         * Delete document
         */
        $collection->delete($document);
        
        $this->assertEmpty($collection->findById($documentId));
    }
}