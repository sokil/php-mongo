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
    
    public function testGetRevision()
    {
        $document = $this->collection
            ->enableVersioning()
            ->createDocument(array('param' => 'value'))
            ->save()
            ->set('param', 'value1')
            ->save()
            ->set('param', 'value2')
            ->save();
        
        $revisions = $document->getRevisions();
        next($revisions);
        
        $key = key($revisions);
        
        $revision = $document->getRevision($key);
        
        $this->assertEquals('value1', $revision->param);
    }

    public function testGetRevisionOriginalDocument()
    {
        $document = $this->collection
            ->enableVersioning()
            ->createDocument(array('param' => 'value'))
            ->save()
            ->set('param', 'value1')
            ->save()
            ->set('param', 'value2')
            ->save();

        $revisions = $document->getRevisions();
        next($revisions);

        $key = key($revisions);

        $revision = $document->getRevision($key);

        $originalDocument = $revision->getDocument();

        $this->assertInstanceOf('\Sokil\Mongo\Document', $originalDocument);

        $this->assertEquals(
            'phpmongo_test_collection',
            $originalDocument->getCollection()->getName()
        );
    }

    public function testGetRevisionDate()
    {
        $timeNow = time();
        
        $document = $this->collection
            ->enableVersioning()
            ->createDocument(array('param' => 'value'))
            ->save()
            ->set('param', 'value1')
            ->save()
            ->set('param', 'value2')
            ->save();

        $revisions = $document->getRevisions();
        next($revisions);

        $key = key($revisions);

        $revision = $document->getRevision($key);

        $this->assertLessThan(2, $revision->getDate() - $timeNow);
        $this->assertLessThan(2, strtotime($revision->getDate('Y-m-d H:i:s')) - $timeNow);
    }
    
    public function testGetRevisions()
    {
        $document = $this->collection
            ->enableVersioning()
            ->createDocument(array('param' => 'value'))
            ->save()
            ->set('param', 'value1')
            ->save()
            ->set('param', 'value2')
            ->save();
        
        $revisions = $document->getRevisions(1, 1);
        
        $revision = current($revisions);
        
        $this->assertEquals('value1', $revision->param);
        
        $document->clearRevisions();
    }
    
    public function testCreateRevisionOnDelete()
    {
        // revision created only after update
        $document = $this->collection
            ->enableVersioning()
            ->createDocument(array('param' => 'value'))
            ->save();
        
        $this->assertEquals(0, $document->getRevisionsCount());
        
        // revision created
        $document->delete();
        
        $this->assertEquals(1, $document->getRevisionsCount());
        
        $revision = current($document->getRevisions());
        
        $this->assertEquals('value', $revision->get('param'));
        
        $document->clearRevisions();
    }
}