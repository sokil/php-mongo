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
        self::$database->disableCollectionPool();
    }

    public function setUp()
    {
        self::$database->resetMapping();
    }

    public function testGetCollection()
    {
        $this->assertInstanceof(
            '\Sokil\Mongo\Collection',
            self::$database->getCollection('collection')
        );

        $this->assertInstanceof(
            '\Sokil\Mongo\Collection',
            self::$database->collection
        );
    }

    public function testEnableCollectionPool()
    {
        self::$database->clearCollectionPool();

        // disable collection pool
        self::$database->disableCollectionPool();
        $this->assertFalse(self::$database->isCollectionPoolEnabled());

        // create collection
        self::$database->getCollection('phpmongo_test_collection_1');

        // check if collection in pool
        $this->assertTrue(self::$database->isCollectionPoolEmpty());

        // enable collection pool
        self::$database->enableCollectionPool();
        $this->assertTrue(self::$database->isCollectionPoolEnabled());

        // read collection to pool
        self::$database->getCollection('phpmongo_test_collection_2');

        // check if document in pool
        $this->assertFalse(self::$database->isCollectionPoolEmpty());

        // clear document pool
        self::$database->clearCollectionPool();
        $this->assertTrue(self::$database->isCollectionPoolEmpty());

        // disable document pool
        self::$database->disableCollectionPool();
        $this->assertFalse(self::$database->isCollectionPoolEnabled());
    }

    /**
     * @expectedException Sokil\Mongo\Exception
     * @expectedExceptionMessage Size or number of elements must be defined
     */
    public function testCreateCappedCollection()
    {
        self::$database->createCappedCollection(
            'collection',
            'swong_size',
            'wrong_number'
        );
    }

    /**
     * @expectedException Sokil\Mongo\Exception
     * @expectedExceptionMessage Class \WrongClass not found while map collection name to class
     */
    public function testCreateCollection()
    {
        self::$database->map('collection', '\WrongClass');
        self::$database->createCollection('collection');
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

    public function testMapCollectionToClass()
    {
        self::$database->map('collection', '\Sokil\Mongo\CarsCollection');
        self::$database->map('gridfs', '\Sokil\Mongo\CarPhotosGridFS');

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

    public function testGetMapping()
    {
        self::$database->resetMapping();
        self::$database->map(array(
            'collection'    => '\Sokil\Mongo\CarsCollection',
            'gridfs'        => '\Sokil\Mongo\CarPhotosGridFS',
        ));

        // test if mapping exists
        $this->assertEquals(array(
            'collection'    => '\Sokil\Mongo\CarsCollection',
            'gridfs'        => '\Sokil\Mongo\CarPhotosGridFS',
        ), self::$database->getMapping());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Class \ThisClassIsNotExists not found while map collection name to class
     */
    public function testGetCollection_UnexistedClassInMapping()
    {
        self::$database->resetMapping();
        self::$database->map(array(
            'collection'    => '\ThisClassIsNotExists',
        ));

        self::$database->getCollection('collection');
    }

    public function testGetGridFSClassName_Classpath()
    {
        self::$database->resetMapping();
        self::$database->map('\Sokil\Mongo');

        $this->assertEquals(
            '\Sokil\Mongo\CarPhotosGridFS',
            self::$database->getGridFSClassName('carPhotosGridFS')
        );

        $this->assertEquals(
            '\Sokil\Mongo\CarPhotosGridFS',
            self::$database->getGridFSClassName('CarPhotosGridFS')
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Class \BlahBlahBlah not found while map GridSF name to class
     */
    public function testGetGridFs_WrongGridFSClassSpecifiedInMapping()
    {
        self::$database->map(array(
            'gridfs' => '\BlahBlahBlah',
        ));

        self::$database->getGridFS('gridfs');

        $this->fail('Must be exception');
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Must be GridFS
     */
    public function testGetGridFs_SpecifiedGridFSClassInMappingIsNotInstanceOfGridFS()
    {
        self::$database->map(array(
            'gridfs' => '\stdClass',
        ));

        self::$database->getGridFS('gridfs');

        $this->fail('Must be exception');
    }

    public function testReadPrimaryOnly()
    {
        self::$database->readPrimaryOnly();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY
        ), self::$database->getReadPreference());
    }

    public function testReadPrimaryPreferred()
    {
        self::$database->readPrimaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), self::$database->getReadPreference());
    }

    public function testReadSecondaryOnly(array $tags = null)
    {
        self::$database->readSecondaryOnly(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), self::$database->getReadPreference());
    }

    public function testReadSecondaryPreferred(array $tags = null)
    {
        self::$database->readSecondaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), self::$database->getReadPreference());
    }

    public function testReadNearest(array $tags = null)
    {
        self::$database->readNearest(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_NEAREST,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), self::$database->getReadPreference());
    }
    
    public function testSetWriteConcern()
    {
        self::$database->setWriteConcern('majority', 12000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 12000
        ), self::$database->getWriteConcern());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error setting write concern
     */
    public function testSetWriteConcern_Error()
    {
        $mongoDatabaseMock = $this->getMock(
            '\MongoDB',
            array('setWriteConcern'),
            array(self::$database->getClient()->getConnection(), 'test')
        );

        $mongoDatabaseMock
            ->expects($this->once())
            ->method('setWriteConcern')
            ->will($this->returnValue(false));

        $database = new Database(self::$database->getClient(), $mongoDatabaseMock);

        $database->setWriteConcern(1);
    }

    public function testSetUnacknowledgedWriteConcern()
    {
        self::$database->setUnacknowledgedWriteConcern(11000);

        $this->assertEquals(array(
            'w' => 0,
            'wtimeout' => 11000
        ), self::$database->getWriteConcern());
    }

    public function testSetMajorityWriteConcern()
    {
        self::$database->setMajorityWriteConcern(13000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 13000
        ), self::$database->getWriteConcern());
    }
}