<?php

namespace Sokil\Mongo;

class BatchTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp()
    {
        // connect to mongo
        $client = new Client();

        if (version_compare($client->getDbVersion(), '2.6.0', '<')) {
            $this->markTestSkipped(
                'Current primary does not have a Write API support.'
            );
        }


        // select database
        $database = $client->getDatabase('test');
        $this->collection = $database->getCollection('phpmongo_test_collection');
    }

    public function tearDown()
    {
        if($this->collection) {
            $this->collection->delete();
        }
    }

    public function testCount()
    {
        $batch = new BatchInsert($this->collection);

        $batch->insert(array());
        $batch->insert(array());
        $batch->insert(array());

        $this->assertEquals(3, count($batch));
    }

    public function testInsert()
    {
        $batch = new BatchInsert($this->collection);

        $batch
            ->insert(array('a' => 1))
            ->insert(array('a' => 2))
            ->execute();

        $result = $this->collection
            ->findAsArray()
            ->sort(array('a' => 1))
            ->map(function($data) {
                unset($data['_id']);
                return $data;
            });

        $this->assertEquals(array_values($result), array(
            array('a' => 1),
            array('a' => 2),
        ));
    }

    public function testUpdate()
    {
        // insert
        $this->collection->batchInsert(array(
            array('a' => 1, 'b' => 1),
            array('a' => 2, 'b' => 2),
            array('a' => 3, 'b' => 3),
        ));

        // update
        $batch = new BatchUpdate($this->collection);
        $batch
            ->update(
                array('a' => 1),
                array('$set' => array('b' => 'updated1'))
            )
            ->update(
                $this->collection->expression()->where('a', 2),
                $this->collection->operator()->set('b', 'updated2')
            )
            ->update(
                function(Expression $e) { $e->where('a', 3); },
                function(Operator $o) { $o->set('b', 'updated3'); }
            )
            ->execute();

        // test
        $result = $this->collection
            ->findAsArray()
            ->sort(array('a' => 1))
            ->map(function($data) {
                unset($data['_id']);
                return $data;
            });

        $this->assertEquals(array_values($result), array(
            array('a' => 1, 'b' => 'updated1'),
            array('a' => 2, 'b' => 'updated2'),
            array('a' => 3, 'b' => 'updated3'),
        ));
    }

    public function testDelete()
    {
        // insert
        $this->collection->batchInsert(array(
            array('a' => 1),
            array('a' => 2),
            array('a' => 3),
            array('a' => 4),
            array('a' => 5),
            array('a' => 6),
        ));

        // delete
        $batch = new BatchDelete($this->collection);
        $batch
            ->delete(array('a' => 2))
            ->delete($this->collection->expression()->where('a', 4))
            ->delete(function(Expression $e) { $e->where('a', 6); })
            ->execute();

        // test
        $result = $this->collection
            ->findAsArray()
            ->sort(array('a' => 1))
            ->map(function($data) {
                unset($data['_id']);
                return $data;
            });

        $this->assertEquals(
            array_values($result),
            array(
                array('a' => 1),
                array('a' => 3),
                array('a' => 5),
            )
        );
    }
}