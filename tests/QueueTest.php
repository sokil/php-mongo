<?php

namespace Sokil\Mongo;

class QueueTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private static $database;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        self::$database = $client->getDatabase('test');
    }
    
    public static function tearDownAfterClass() {

    }
    
    public function testQueueCount()
    {
        $queue = self::$database->getQueue('queue_channel')->clear();
        
        $queue->enqueue(array(
            'param' => 1,
        ));
        
        $queue->enqueue(array(
            'param' => 2,
        ));
        
        $queue->enqueue(array(
            'param' => 3,
        ), 10);
        
        // check count of messages in queue
        $this->assertEquals(3, count($queue));
    }
    
    public function testDequeue()
    {
        $queue = self::$database->getQueue('queue_channel')->clear();

        // add normal
        $queue->enqueue(array(
            'param' => 1,
        ));

        // add with priority
        $queue->enqueue('priority-driven', 10);

        // add normal
        $queue->enqueue(array(
            'param' => 3,
        ));

        // check if message with priority first
        $this->assertEquals('priority-driven', $queue->dequeue());

        $this->assertEquals(1, $queue->dequeue()->get('param'));

        $this->assertEquals(3, $queue->dequeue()->get('param'));
    }
    
    public function testDequeueFromNotExistedCollection()
    {
        $queue = self::$database->getQueue('some_strange_unexisted_channel');
        
        $this->assertNull($queue->dequeue());
    }
}