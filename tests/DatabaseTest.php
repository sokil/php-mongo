<?php

namespace Sokil\Mongo;

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Database
     */
    private $database;
    
    public function setUp()
    {
        // connect to mongo
        $client = new Client();
        
        // select database
        $this->database = $client->getDatabase('test');
    }

    public function testGetCollection()
    {
        $this->assertInstanceof(
            '\Sokil\Mongo\Collection',
            $this->database->getCollection('collection')
        );

        $this->assertInstanceof(
            '\Sokil\Mongo\Collection',
            $this->database->collection
        );
    }

    public function testEnableCollectionPool()
    {
        $this->database->clearCollectionPool();

        // disable collection pool
        $this->database->disableCollectionPool();
        $this->assertFalse($this->database->isCollectionPoolEnabled());

        // create collection
        $this->database->getCollection('phpmongo_test_collection_1');

        // check if collection in pool
        $this->assertTrue($this->database->isCollectionPoolEmpty());

        // enable collection pool
        $this->database->enableCollectionPool();
        $this->assertTrue($this->database->isCollectionPoolEnabled());

        // read collection to pool
        $this->database->getCollection('phpmongo_test_collection_2');

        // check if document in pool
        $this->assertFalse($this->database->isCollectionPoolEmpty());

        // clear document pool
        $this->database->clearCollectionPool();
        $this->assertTrue($this->database->isCollectionPoolEmpty());

        // disable document pool
        $this->database->disableCollectionPool();
        $this->assertFalse($this->database->isCollectionPoolEnabled());
    }

    /**
     * @expectedException Sokil\Mongo\Exception
     * @expectedExceptionMessage Size or number of elements must be defined
     */
    public function testCreateCappedCollection()
    {
        $this->database->createCappedCollection(
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
        $this->database->map('collection', '\WrongClass');
        $this->database->createCollection('collection');
    }

    public function testStats()
    {
        $stats = $this->database->stats();
        $this->assertEquals(1.0, $stats['ok']);
    }
    
    public function testDisableProfiler()
    {
        $result = $this->database->disableProfiler();
        $this->assertArrayHasKey('was', $result);
        $this->assertArrayHasKey('slowms', $result);
    }
    
    public function testProfileSlowQueries()
    {
        $result = $this->database->profileSlowQueries();
        $this->assertArrayHasKey('was', $result);
        $this->assertArrayHasKey('slowms', $result);
    }
    
    public function testProfileAllQueries()
    {
        $result = $this->database->profileAllQueries();
        $this->assertArrayHasKey('was', $result);
        $this->assertArrayHasKey('slowms', $result);
    }
    
    public function testExecuteJs()
    {
        $result = $this->database->executeJS('return 42;');
        $this->assertEquals(42, $result);
    }
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error #16722: exception: ReferenceError: gversion is not defined
     */
    public function testExecuteInvalidJs()
    {
        $this->database->executeJS('gversion()');
    }
    
    public function testMapCollectionsToClasses()
    {
        $this->database->map(array(
            'collection'    => '\Sokil\Mongo\CarsCollection',
            'gridfs'        => '\Sokil\Mongo\CarPhotosGridFS',
        ));
        
        // create collection
        $this->assertInstanceOf('\Sokil\Mongo\CarsCollection', $this->database->getCollection('collection'));
        
        // create document
        $this->assertInstanceOf('\Sokil\Mongo\CarDocument', $this->database->getCollection('collection')->createDocument());
        
        // create grid fs
        $fs = $this->database->getGridFS('gridfs');
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotosGridFS', $fs);
        
        // create file
        $id = $fs->storeBytes('hello');
        $file = $fs->getFileById($id);
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotoGridFSFile', $file);
        
        $fs->delete();
    }

    public function testMapCollectionToClass()
    {
        $this->database->map('collection', '\Sokil\Mongo\CarsCollection');
        $this->database->map('gridfs', '\Sokil\Mongo\CarPhotosGridFS');

        // create collection
        $this->assertInstanceOf('\Sokil\Mongo\CarsCollection', $this->database->getCollection('collection'));

        // create document
        $this->assertInstanceOf('\Sokil\Mongo\CarDocument', $this->database->getCollection('collection')->createDocument());

        // create grid fs
        $fs = $this->database->getGridFS('gridfs');
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotosGridFS', $fs);

        // create file
        $id = $fs->storeBytes('hello');
        $file = $fs->getFileById($id);
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotoGridFSFile', $file);
        
        $fs->delete();
    }

    public function testGetMapping()
    {
        $this->database->resetMapping();
        $this->database->map(array(
            'collection'    => '\Sokil\Mongo\CarsCollection',
            'gridfs'        => '\Sokil\Mongo\CarPhotosGridFS',
        ));
        
        $reflectionClass = new \ReflectionClass($this->database);
        $property = $reflectionClass->getProperty('mapping');
        $property->setAccessible(true);

        // test if mapping exists
        $this->assertEquals(array(
            'collection'    => '\Sokil\Mongo\CarsCollection',
            'gridfs'        => '\Sokil\Mongo\CarPhotosGridFS',
        ), $property->getValue($this->database));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Class \ThisClassIsNotExists not found while map collection name to class
     */
    public function testGetCollection_UnexistedClassInMapping()
    {
        $this->database->resetMapping();
        $this->database->map(array(
            'collection'    => '\ThisClassIsNotExists',
        ));

        $this->database->getCollection('collection');
    }

    public function testGetGridFSClassName_Classpath()
    {
        $this->database->resetMapping();
        $this->database->map('\Sokil\Mongo');

        $reflectionClass = new \ReflectionClass($this->database);
        $method = $reflectionClass->getMethod('getGridFSClassDefinition');
        $method->setAccessible(true);
        
        $classDefinition1 = $method->invoke($this->database, 'carPhotosGridFS');
        $classDefinition2 = $method->invoke($this->database, 'CarPhotosGridFS');
        
        $this->assertEquals(
            '\Sokil\Mongo\CarPhotosGridFS',
            $classDefinition1['class']
        );

        $this->assertEquals(
            '\Sokil\Mongo\CarPhotosGridFS',
            $classDefinition2['class']
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Class \BlahBlahBlah not found while map collection name to class
     */
    public function testGetGridFs_WrongGridFSClassSpecifiedInMapping()
    {
        $this->database->map(array(
            'gridfs' => '\BlahBlahBlah',
        ));

        $this->database->getGridFS('gridfs');

        $this->fail('Must be exception');
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Must be GridFS
     */
    public function testGetGridFs_SpecifiedGridFSClassInMappingIsNotInstanceOfGridFS()
    {
        $this->database->map(array(
            'gridfs' => '\stdClass',
        ));

        $this->database->getGridFS('gridfs');

        $this->fail('Must be exception');
    }

    public function testReadPrimaryOnly()
    {
        $this->database->readPrimaryOnly();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY
        ), $this->database->getReadPreference());
    }

    public function testReadPrimaryPreferred()
    {
        $this->database->readPrimaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->database->getReadPreference());
    }

    public function testReadSecondaryOnly(array $tags = null)
    {
        $this->database->readSecondaryOnly(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->database->getReadPreference());
    }

    public function testReadSecondaryPreferred(array $tags = null)
    {
        $this->database->readSecondaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->database->getReadPreference());
    }

    public function testReadNearest(array $tags = null)
    {
        $this->database->readNearest(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_NEAREST,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->database->getReadPreference());
    }
    
    public function testSetWriteConcern()
    {
        $this->database->setWriteConcern('majority', 12000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 12000
        ), $this->database->getWriteConcern());
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
            array($this->database->getClient()->getMongoClient(), 'test')
        );

        $mongoDatabaseMock
            ->expects($this->once())
            ->method('setWriteConcern')
            ->will($this->returnValue(false));

        $database = new Database($this->database->getClient(), $mongoDatabaseMock);

        $database->setWriteConcern(1);
    }

    public function testSetUnacknowledgedWriteConcern()
    {
        $this->database->setUnacknowledgedWriteConcern(11000);

        $this->assertEquals(array(
            'w' => 0,
            'wtimeout' => 11000
        ), $this->database->getWriteConcern());
    }

    public function testSetMajorityWriteConcern()
    {
        $this->database->setMajorityWriteConcern(13000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 13000
        ), $this->database->getWriteConcern());
    }
}

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