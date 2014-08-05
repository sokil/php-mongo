<?php

namespace Sokil\Mongo;

class GridFsTest extends \PHPUnit_Framework_TestCase
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
    
    public function testGetGridFs()
    {
        $this->assertInstanceOf('\Sokil\Mongo\GridFs', self::$database->getGridFs('images'));
    }
    
    public function testCreateFromFile()
    {
        // create temp file
        $filename = tempnam(sys_get_temp_dir(), 'prefix');
        file_put_contents($filename, 'somebinarydata');
        
        $fs = self::$database->getGridFs('images');
        
        // store file
        $id = $fs->createFromFile($filename, array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $this->assertInstanceOf('\MongoId', $id);
        
        unlink($filename);
        $fs->deleteAll();
    }
    
    public function testCreateFromBinary()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->createFromBinary('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $this->assertInstanceOf('\MongoId', $id);
        $fs->deleteAll();
    }
    
    public function testGet()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->createFromBinary('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $file = $fs->get($id);
        
        $this->assertInstanceOf('\Sokil\Mongo\File', $file);
        
        $this->assertEquals(1, $file->get('meta1'));
    }
    
    public function testSave()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->createFromBinary('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $file = $fs->get($id);
        $file->set('meta1', 777)->save();
        
        $file = $fs->get($id);
        $this->assertEquals(777, $file->get('meta1'));
    }
    
    public function testGetFilename()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->createFromBinary('somebinarydata', array(
            'filename' => '/etc/hosts',
        ));
        
        $this->assertEquals('/etc/hosts', $fs->get($id)->getFilename());        
    }
    
    public function testDelete()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->createFromBinary('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $fs->delete($id);
        
        $this->assertEquals(null, $fs->get($id));
    }
}