<?php

namespace Sokil\Mongo;

class PersistenceTest extends \PHPUnit_Framework_TestCase
{    
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
        $this->client = new Client();
        $this->collection = $this->client
            ->getDatabase('test')
            ->getCollection('phpmongo_test_collection');
    }

    public function tearDown()
    {
        $this->collection->delete();
    }

    public function persistanceInstanceProvider()
    {
        return array(
            array(new Persistence()),
            //array(new PersistenceLegacy()),
        );
    }

    /**
     * @dataProvider persistanceInstanceProvider
     */
    public function testPersist(Persistence $persistence)
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ));

        $this->assertFalse($persistence->contains($document));

        // add document
        $persistence->persist($document);

        // check if document in persistence
        $this->assertTrue($persistence->contains($document));
    }

    /**
     * @dataProvider persistanceInstanceProvider
     */
    public function testRemove(Persistence $persistence)
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ))
            ->save();

        // add document
        $persistence->remove($document);

        // check if document in persistence
        $this->assertTrue($persistence->contains($document));

        // store to disk
        $persistence->flush();

        // check if document in persistence
        $this->assertFalse($persistence->contains($document));

        // check if document removed
        $this->assertEmpty($this->collection->find()->findOne());
    }

    /**
     * @dataProvider persistanceInstanceProvider
     */
    public function testPersistInsert(Persistence $persistence)
    {
        $document1 = $this->collection
            ->createDocument(array(
                'param' => 'value1',
            ));

        $document2 = $this->collection
            ->createDocument(array(
                'param' => 'value2',
            ));

        // add documents
        $persistence
            ->persist($document1)
            ->persist($document2)
            ->flush();

        // check results
        $result = $this->collection->find()->asArray()->findAll();

        $this->assertEquals(2, count($result));

        $document1data = current($result);
        unset($document1data['_id']);
        $this->assertEquals(array('param' => 'value1'), $document1data);

        next($result);

        $document2data = current($result);
        unset($document2data['_id']);
        $this->assertEquals(array('param' => 'value2'), $document2data);
    }

    /**
     * @dataProvider persistanceInstanceProvider
     */
    public function testPersistUpdate(Persistence $persistence)
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ))
            ->save();

        $document->param = 'new';

        // add document
        $persistence
            ->persist($document)
            ->flush();

        $this->assertEquals('new', $this->collection->find()->findOne()->param);
    }

    /**
     * @dataProvider persistanceInstanceProvider
     */
    public function testClear(Persistence $persistence)
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ));

        // add document
        $persistence->persist($document);

        // clear documents
        $persistence->clear();

        $this->assertEquals(0, count($persistence));
    }

    /**
     * @dataProvider persistanceInstanceProvider
     */
    public function testDetach(Persistence $persistence)
    {
        $document = $this->collection
            ->createDocument(array(
                'param' => 'value',
            ));

        $this->assertFalse($persistence->contains($document));

        // attach document
        $persistence->persist($document);

        // check if document in persistence
        $this->assertTrue($persistence->contains($document));

        // detach document
        $persistence->detach($document);

        // check if document in persistence
        $this->assertFalse($persistence->contains($document));
    }
}