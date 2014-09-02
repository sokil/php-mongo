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
        $id = $fs->storeFile($filename, array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $this->assertInstanceOf('\MongoId', $id);
        
        unlink($filename);
        $fs->delete();
    }

    public function testInitFileWithArray()
    {
        $file = new GridFSFile(self::$database->getGridFS('images'), array(
            'param' => 'value'
        ));

        $this->assertInstanceOf('\Sokil\Mongo\GridFSFile', $file);
    }

    public function testCreateFromBinary()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $this->assertInstanceOf('\MongoId', $id);
        $fs->delete();
    }
    
    public function testGet()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $file = $fs->getFileById($id);
        
        $this->assertInstanceOf('\Sokil\Mongo\GridFSFile', $file);
        
        $this->assertEquals(1, $file->get('meta1'));
    }
    
    public function testSave()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $file = $fs->getFileById($id);
        $file->set('meta1', 777)->save();
        
        $file = $fs->getFileById($id);
        $this->assertEquals(777, $file->get('meta1'));
    }
    
    public function testGetFilename()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->storeBytes('somebinarydata', array(
            'filename' => '/etc/hosts',
        ));
        
        $this->assertEquals('/etc/hosts', $fs->getFileById($id)->getFilename());        
    }
    
    public function testDelete()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $fs->deleteFileById($id);
        
        $this->assertEquals(null, $fs->getFileById($id));
    }
    
    public function testFind()
    {
        $fs = self::$database->getGridFs('images');
        
        $fs->storeBytes('somebinarydata', array(
            'meta' => 1,
        ));
        
        $id = $fs->storeBytes('somebinarydata', array(
            'meta' => 2,
        ));
        
        $fs->storeBytes('somebinarydata', array(
            'meta' => 3,
        ));
        
        $file = $fs->find()->where('meta', 2)->current();
        
        $this->assertNotEmpty($file);
        
        $this->assertInstanceof('\Sokil\Mongo\GridFSFile', $file);
        
        $this->assertEquals($id, $file->get('_id'));
        
        $this->assertEquals(2, $file->meta);
    }
    
    public function testGetBytes()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->storeBytes('somebinarydata', array(
            'meta' => 1,
        ));
        
        $this->assertEquals('somebinarydata', $fs->getFileById($id)->getBytes());
    }
    
    public function testGetResource()
    {
        $fs = self::$database->getGridFs('images');
        
        $id = $fs->storeBytes('somebinarydata', array(
            'meta' => 1,
        ));
        
        $this->assertTrue(is_resource($fs->getFileById($id)->getResource()));
    }
}