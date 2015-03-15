<?php

namespace Sokil\Mongo;

class GridFsFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private $database;
        
    /**
     *
     * @var \Sokil\Mongo\GridFS
     */
    private $gridFs;
    
    public function setUp()
    {
        $client = new Client();
        $this->database = $client->getDatabase('test');
        $this->gridFs = $this->database->getGridFs('images');
    }
    
    public function tearDown()
    {
        if($this->gridFs) {
            $this->gridFs->delete();
        }
    }

    public function testInitFileWithArray()
    {
        $file = new GridFSFile($this->gridFs, array(
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
        $file = new GridFSFile($this->gridFs, 'allowed_only_array_or_MongoGridFSFile');

        $this->assertInstanceOf('\Sokil\Mongo\GridFSFile', $file);
    }

    public function testInitFileWithEmptyData()
    {
        $file = new GridFSFile($this->gridFs);

        $this->assertInstanceOf('\Sokil\Mongo\GridFSFile', $file);
    }


    public function testGet()
    {
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));

        $file = $this->gridFs->getFileById($id);

        $this->assertInstanceOf('\Sokil\Mongo\GridFSFile', $file);

        $this->assertEquals(1, $file->get('meta1'));
    }

    public function testGetFilename()
    {
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'filename' => '/etc/hosts',
        ));

        $this->assertEquals('/etc/hosts', $this->gridFs->getFileById($id)->getFilename());
    }

    public function testSave()
    {
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));

        $file = $this->gridFs->getFileById($id);
        $file->set('meta1', 777)->save();

        $file = $this->gridFs->getFileById($id);
        $this->assertEquals(777, $file->get('meta1'));
    }

    public function testGetMongoGridFsFile()
    {
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));

        $file = $this->gridFs->getFileById($id);

        $this->assertInstanceof('\MongoGridFSFile', $file->getMongoGridFsFile());
    }

    public function testCount()
    {
        $data = 'somebinarydata';

        $id = $this->gridFs->storeBytes($data);

        $file = $this->gridFs->getFileById($id);

        $this->assertEquals(strlen($data), $file->count());

        $this->assertEquals(strlen($data), count($file));
    }


    public function testDump()
    {
        $data = 'somebinarydata';
        $id = $this->gridFs->storeBytes($data);
        $file = $this->gridFs->getFileById($id);

        $filename = sys_get_temp_dir() . '/mongoFile.txt';
        $file->dump($filename);

        $this->assertFileExists($filename);

        $this->assertStringEqualsFile($filename, $data);
    }

    public function testDelete()
    {
        $id = $this->gridFs->storeBytes('somebinarydata');
        $file = $this->gridFs->getFileById($id);

        $file->delete();

        $this->assertNull($this->gridFs->getFileById($id));
    }
    
    public function testGetMd5Checksum()
    {
        $id = $this->gridFs->storeBytes('somebinarydata');
        $file = $this->gridFs->getFileById($id);
        
        $this->assertEquals(md5('somebinarydata'), $file->getMd5Checksum());
    }
}