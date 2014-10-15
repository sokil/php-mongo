<?php

namespace Sokil\Mongo;

class PersistenceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Sokil\Mongo\Persistence
     */
    private $persistence;
    
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private $client;
    
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp()
    {
        $this->client = new Client(MONGO_DSN);
        
        $this->persistence = $this->client->createPersistence();
        
        $this->collection = $this->client
            ->getDatabase('test')
            ->getCollection('phpmongo_test_collection');
    }
    
    public function tearDown()
    {
        $this->collection->delete();
    }

    public function testPersist()
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ));

        $this->assertFalse($this->persistence->contains($document));

        // add document
        $this->persistence->persist($document);

        // check if document in persistence
        $this->assertTrue($this->persistence->contains($document));
    }

    public function testRemove()
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ))
            ->save();

        // add document
        $this->persistence->remove($document);

        // check if document in persistence
        $this->assertTrue($this->persistence->contains($document));

        // store to disk
        $this->persistence->flush();

        // check if document in persistence
        $this->assertFalse($this->persistence->contains($document));

        // check if document removed
        $this->assertEmpty($this->collection->find()->findOne());
    }

    public function testFlush()
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ));

        // add document
        $this->persistence
            ->persist($document)
            ->flush();

        $this->assertEquals('value', $this->collection->find()->findOne()->param);
    }

    public function testClear()
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ));

        // add document
        $this->persistence->persist($document);

        // clear documents
        $this->persistence->clear();

        $this->assertEquals(0, count($this->persistence));
    }

    public function testDetach()
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ));

        $this->assertFalse($this->persistence->contains($document));

        // attach document
        $this->persistence->persist($document);

        // check if document in persistence
        $this->assertTrue($this->persistence->contains($document));

        // detach document
        $this->persistence->detach($document);

        // check if document in persistence
        $this->assertFalse($this->persistence->contains($document));
    }
}