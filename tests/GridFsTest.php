<?php

namespace Sokil\Mongo;

class GridFsTest extends \PHPUnit_Framework_TestCase
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
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
        $this->database = $client->getDatabase('test');
        $this->gridFs = $this->database->getGridFs('images');
    }
    
    public function tearDown()
    {
        $this->gridFs->delete();
    }
    
    public function testGetGridFs()
    {
        $this->assertInstanceOf('\Sokil\Mongo\GridFs', $this->database->getGridFs('images'));
    }
    
    public function testCreateFromFile()
    {
        // create temp file
        $filename = tempnam(sys_get_temp_dir(), 'prefix');
        file_put_contents($filename, 'somebinarydata');
        
        // store file
        $id = $this->gridFs->storeFile($filename, array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $this->assertInstanceOf('\MongoId', $id);
        
        unlink($filename);
    }

    public function testCreateFromBinary()
    {        
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $this->assertInstanceOf('\MongoId', $id);
    }

    public function testGetFileById_MongoIdArgument()
    {
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));

        $this->assertNotEmpty($this->gridFs->getFileById($id));
    }

    public function testGetFileById_StringArgument()
    {
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));

        $this->assertNotEmpty($this->gridFs->getFileById((string) $id));
    }

    public function testGetFileById_VarcharArgument()
    {
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            '_id'   => 'varchar_id',
            'meta1' => 1,
            'meta2' => 2,
        ));

        $this->assertNotEmpty($this->gridFs->getFileById('varchar_id'));
    }
    
    public function testDeleteById_MongoId()
    {        
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $this->gridFs->deleteFileById($id);
        
        $this->assertEquals(null, $this->gridFs->getFileById($id));
    }
    
    public function testDeleteById_MongoIdString()
    {        
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta1' => 1,
            'meta2' => 2,
        ));
        
        $this->gridFs->deleteFileById((string) $id);
        
        $this->assertEquals(null, $this->gridFs->getFileById($id));
    }
    
    public function testFind()
    {
        $this->gridFs->storeBytes('somebinarydata', array(
            'meta' => 1,
        ));
        
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta' => 2,
        ));
        
        $this->gridFs->storeBytes('somebinarydata', array(
            'meta' => 3,
        ));
        
        $file = $this->gridFs->find()->where('meta', 2)->current();
        
        $this->assertNotEmpty($file);
        
        $this->assertInstanceof('\Sokil\Mongo\GridFSFile', $file);
        
        $this->assertEquals($id, $file->get('_id'));
        
        $this->assertEquals(2, $file->meta);
    }
    
    public function testGetBytes()
    {        
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta' => 1,
        ));
        
        $this->assertEquals('somebinarydata', $this->gridFs->getFileById($id)->getBytes());
    }
    
    public function testGetResource()
    {        
        $id = $this->gridFs->storeBytes('somebinarydata', array(
            'meta' => 1,
        ));
        
        $this->assertTrue(is_resource($this->gridFs->getFileById($id)->getResource()));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error deleting file: some_error: Some error message
     */
    public function testDeleteFileById_WithAcknowledgedWriteConcern()
    {
        $mongoGridFsMock = $this->getMock(
            '\MongoGridFS',
            array('delete'),
            array($this->database->getMongoDB(), 'images')
        );

        $mongoGridFsMock
            ->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'err' => 'some_error',
                'errmsg' => 'Some error message',
            )));

        $gridFS = new GridFS($this->database, $mongoGridFsMock);

        $id = $gridFS->storeBytes('data');

        $gridFS->deleteFileById($id);
    }
}