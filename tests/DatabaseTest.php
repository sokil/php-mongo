<?php

namespace Sokil\Mongo;

class CollectionMock extends Collection { }
class GridFSMock extends GridFS { }

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
    
    public function testDisableProfiler()
    {
        $result = self::$database->disableProfiler();
        $this->assertArrayHasKey('was', $result);
        $this->assertArrayHasKey('slowms', $result);
    }
    
    public function testProfileSlowQueries()
    {
        $result = self::$database->profileSlowQueries();
        $this->assertArrayHasKey('was', $result);
        $this->assertArrayHasKey('slowms', $result);
    }
    
    public function testProfileAllQueries()
    {
        $result = self::$database->profileAllQueries();
        $this->assertArrayHasKey('was', $result);
        $this->assertArrayHasKey('slowms', $result);
    }
    
    public function testExecuteJs()
    {
        $result = self::$database->executeJS('return 42;');
        $this->assertEquals(42, $result);
    }
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error #16722: exception: ReferenceError: gversion is not defined
     */
    public function testExecuteInvalidJs()
    {
        var_dump(self::$database->executeJS('gversion()'));
    }
    
    public function testMapCollectionsToClasses()
    {
        self::$database->map(array(
            'collection'    => '\Sokil\Mongo\CollectionMock',
            'gridfs'        => '\Sokil\Mongo\GridFSMock',
        ));
        
        $this->assertInstanceOf('\Sokil\Mongo\CollectionMock', self::$database->getCollection('collection'));
        
        $this->assertInstanceOf('\Sokil\Mongo\GridFSMock', self::$database->getCollection('gridfs'));
    }
}