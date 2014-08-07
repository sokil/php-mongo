<?php

namespace Sokil\Mongo;

class CarsCollection extends Collection 
{
    public function getDocumentClassName(array $documentData = null)
    {
        return '\Sokil\Mongo\CarDocument';
    }
}

class CarDocument extends Document {}

class CarPhotosGridFS extends GridFS 
{    
    public function getFileClassName(\MongoGridFSFile $fileData = null)
    {
        return '\Sokil\Mongo\CarPhotoGridFSFile';
    }
}

class CarPhotoGridFSFile extends GridFSFile {}

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
            'collection'    => '\Sokil\Mongo\CarsCollection',
            'gridfs'        => '\Sokil\Mongo\CarPhotosGridFS',
        ));
        
        // create collection
        $this->assertInstanceOf('\Sokil\Mongo\CarsCollection', self::$database->getCollection('collection'));
        
        // create document
        $this->assertInstanceOf('\Sokil\Mongo\CarDocument', self::$database->getCollection('collection')->createDocument());
        
        // create grid fs
        $fs = self::$database->getGridFS('gridfs');
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotosGridFS', $fs);
        
        // create file
        $id = $fs->storeBytes('hello');
        $file = $fs->getFileById($id);
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotoGridFSFile', $file);
    }
}