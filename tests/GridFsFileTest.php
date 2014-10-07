<?php

namespace Sokil\Mongo;

class GridFsFileTest extends \PHPUnit_Framework_TestCase
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

    public function testInitFileWithArray()
    {
        $file = new GridFSFile(self::$database->getGridFS('images'), array(
            'param' => 'value'
        ));

        $this->assertInstanceOf('\Sokil\Mongo\GridFSFile', $file);
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Wrong file data specified
     */
    public function testInitFileWithWrongData()
    {
        $file = new GridFSFile(self::$database->getGridFS('images'), 'allowed_only_array_or_MongoGridFSFile');

        $this->assertInstanceOf('\Sokil\Mongo\GridFSFile', $file);
    }

    public function testInitFileWithEmptyData()
    {
        $file = new GridFSFile(self::$database->getGridFS('images'));

        $this->assertInstanceOf('\Sokil\Mongo\GridFSFile', $file);
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

    public function testGetFilename()
    {
        $fs = self::$database->getGridFs('images');

        $id = $fs->storeBytes('somebinarydata', array(
            'filename' => '/etc/hosts',
        ));

        $this->assertEquals('/etc/hosts', $fs->getFileById($id)->getFilename());
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

    public function testGetMongoGridFsFile()
    {
        $fs = self::$database->getGridFs('images');

        $id = $fs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));

        $file = $fs->getFileById($id);

        $this->assertInstanceof('\MongoGridFSFile', $file->getMongoGridFsFile());
    }

    public function testCount()
    {
        $fs = self::$database->getGridFs('images');

        $data = 'somebinarydata';

        $id = $fs->storeBytes($data);

        $file = $fs->getFileById($id);

        $this->assertEquals(strlen($data), $file->count());

        $this->assertEquals(strlen($data), count($file));
    }


    public function testDump()
    {
        $fs = self::$database->getGridFs('images');

        $data = 'somebinarydata';
        $id = $fs->storeBytes($data);
        $file = $fs->getFileById($id);

        $filename = sys_get_temp_dir() . '/mongoFile.txt';
        $file->dump($filename);

        $this->assertFileExists($filename);

        $this->assertStringEqualsFile($filename, $data);
    }

    public function testDelete()
    {
        $fs = self::$database->getGridFs('images');

        $id = $fs->storeBytes('somebinarydata');
        $file = $fs->getFileById($id);

        $file->delete();

        $this->assertNull($fs->getFileById($id));
    }
    
    public function testGetMd5Checksum()
    {
        $fs = self::$database->getGridFs('images');
        $id = $fs->storeBytes('somebinarydata');
        $file = $fs->getFileById($id);
        
        $this->assertEquals(md5('somebinarydata'), $file->getMd5Checksum());
    }
}