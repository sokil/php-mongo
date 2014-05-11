<?php

namespace Sokil\Mongo;

class DatabaseTest extends \PHPUnit_Framework_TestCase
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
    
    public function testStats()
    {
        $stats = self::$database->stats();
        $this->assertEquals(1.0, $stats['ok']);
    }
}