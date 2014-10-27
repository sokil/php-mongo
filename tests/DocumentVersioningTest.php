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
    
    public function tearDown()
    {
        $this->collection->delete();
    }
    
    public function testCreateRevisionOnUpdate()
    {
        // revision created only after update
        $document = $this->collection
            ->enableVersioning()
            ->createDocument(array('param' => 'value'))
            ->save();
        
        $this->assertEquals(0, $document->getRevisionsCount());
        
        // revision created
        $document->set('param', 'updatedValue')->save();
        
        $this->assertEquals(1, $document->getRevisionsCount());
        
        $revision = current($document->getRevisions());
        
        $this->assertEquals('value', $revision->get('param'));
        
        $document->clearRevisions();
    }
    
    public function testGetRevisionKeys()
    {
        // revision created only after update
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save()
            ->set('param', 'value1')
            ->save()
            ->set('param', 'value2')
            ->save();
        
        $this->assertEquals(2, $document->getRevisionKeys());
    }
    
    public function testGetRevision()
    {
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save()
            ->set('param', 'value1')
            ->save()
            ->set('param', 'value2')
            ->save();
        
        $key = current($document->getRevisionKeys());
        
        $revision = $document->getRevision($key);
        $this->assertEquals('value1', $revision->param);
    }
    
    public function testGetRevisions()
    {
        $document = $this->collection
            ->createDocument(array('param' => 'value'))
            ->save()
            ->set('param', 'value1')
            ->save()
            ->set('param', 'value2')
            ->save();
        
        $revisions = $document->getRevisions(1, 1);
        $this->assertEquals('value2', $revisions->param);
    }
}