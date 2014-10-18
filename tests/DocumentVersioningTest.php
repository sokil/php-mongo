<?php

namespace Sokil\Mongo;

class DocumentVersioningTest extends \PHPUnit_Framework_TestCase
{
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
    
    public function testCreateRevisionOnUpdate()
    {
        // revision created only after update
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save();
        
        $this->assertEquals(0, $document->getRevisions());
        
        // revision created
        $document->set('param', 'updatedValue')->save();
        
        $this->assertEquals(1, $document->getRevisions());
        
        $revision = current($document->getRevisions());
        
        $this->assertEquals('value', $revision->get('param'));
    }
}