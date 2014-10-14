<?php

namespace Sokil\Mongo;

class QueueTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private $database;
    
    /**
     *
     * @var \Sokil\Mongo\Queue
     */
    private $queue;
    
    public function setUp()
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        $this->database = $client->getDatabase('test');
        $this->queue = $this->database->getQueue('queue_channel');
    }
    
    public function tearDown()
    {
        $this->queue->clear();
    }
    
    public function testQueueCount()
    {
        $this->queue->enqueue(array(
            'param' => 1,
        ));
        
        $this->queue->enqueue(array(
            'param' => 2,
        ));
        
        $this->queue->enqueue(array(
            'param' => 3,
        ), 10);
        
        // check count of messages in queue
        $this->assertEquals(3, count($this->queue));
    }
    
    public function testDequeue()
    {
        // add normal
        $this->queue->enqueue(array(
            'param' => 1,
        ));

        // add with priority
        $this->queue->enqueue('priority-driven', 10);

        // add normal
        $this->queue->enqueue(array(
            'param' => 3,
        ));

        // check if message with priority first
        $this->assertEquals('priority-driven', $this->queue->dequeue());

        $this->assertEquals(1, $this->queue->dequeue()->get('param'));

        $this->assertEquals(3, $this->queue->dequeue()->get('param'));
    }
    
    public function testDequeueFromNotExistedCollection()
    {
        $queue = $this->database->getQueue('some_strange_unexisted_channel');
        
        $this->assertNull($queue->dequeue());
    }
}