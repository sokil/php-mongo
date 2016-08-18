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

    public function testGetDocumentByReference()
    {
        $collection = $this->database->getCollection('phpmongo_test_collection');
        $collection->delete();

        // create document
        $document = $collection
            ->createDocument(array('param' => 'value'))
            ->save();

        // invalid col and db
        $foundDocument = $this->database->getDocumentByReference(array(
            '$ref'  => 'some_collection',
            '$db'   => 'some_db',
            '$id'   => $document->getId(),
        ), false);

        $this->assertNull($foundDocument);

        /// invalid db
        $foundDocument = $this->database->getDocumentByReference(array(
            '$ref'  => $collection->getName(),
            '$db'   => 'some_db',
            '$id'   => $document->getId(),
        ), false);

        $this->assertNull($foundDocument);

        // all valid
        $foundDocument = $this->database->getDocumentByReference(array(
            '$ref'  => $collection->getName(),
            '$db'   => $collection->getDatabase()->getName(),
            '$id'   => $document->getId(),
        ), false);

        $this->assertSame(
            (string)$document->getId(),
            (string)$foundDocument->getId()
        );

        $collection->delete();
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
     * @expectedException \Sokil\Mongo\Exception
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
     * @expectedException \Sokil\Mongo\Exception
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

    public function testGetProfilerParams()
    {
        $this->database->profileAllQueries(420);
        $result = $this->database->getProfilerParams();

        $this->assertArrayHasKey('was', $result);
        $this->assertEquals(2, $result['was']);

        $this->assertArrayHasKey('slowms', $result);
        $this->assertEquals(420, $result['slowms']);
    }

    public function testGetProfilerLevel()
    {
        $this->database->profileAllQueries(420);
        $level = $this->database->getProfilerLevel();

        $this->assertEquals(2, $level);
    }

    public function testGetProfilerSlowMs()
    {
        $this->database->profileAllQueries(420);
        $slowms = $this->database->getProfilerSlowMs();

        $this->assertEquals(420, $slowms);
    }

    public function testDisableProfiler()
    {
        $result = $this->database->disableProfiler();
        $this->assertArrayHasKey('was', $result);
        $this->assertArrayHasKey('slowms', $result);
    }

    public function testProfileSlowQueries()
    {
        $result = $this->database->profileSlowQueries(200);
        $this->assertArrayHasKey('was', $result);
        $this->assertArrayHasKey('slowms', $result);
    }

    public function testProfileAllQueries()
    {
        $result = $this->database->profileAllQueries();
        $this->assertArrayHasKey('was', $result);
        $this->assertArrayHasKey('slowms', $result);
    }

    public function testFindProfilerRows()
    {
        $this->database->profileAllQueries();

        $row = $this->database->findProfilerRows()
            ->findOne();

        $this->assertArrayHasKey('op', $row);
        $this->assertArrayHasKey('ns', $row);
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

    public function testGetLastError()
    {
        $error = $this->database->getLastError();
        $this->assertArrayHasKey('connectionId', $error);
    }

    public function testMapCollectionsToClasses()
    {
        $this->database->map(array(
            'acmeCollection'    => '\Sokil\Mongo\CarsCollection',
            'acmeGridfs'        => '\Sokil\Mongo\CarPhotosGridFS',
        ));

        // create collection
        $this->assertInstanceOf('\Sokil\Mongo\CarsCollection', $this->database->getCollection('acmeCollection'));

        // create document
        $this->assertInstanceOf('\Sokil\Mongo\CarDocument', $this->database->getCollection('acmeCollection')->createDocument());

        // create grid fs
        $fs = $this->database->getGridFS('acmeGridfs');
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotosGridFS', $fs);

        // create file
        $id = $fs->storeBytes('hello');
        $file = $fs->getFileById($id);
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotoGridFSFile', $file);

        $fs->delete();
    }

    public function testMapCollectionToClass()
    {
        $this->database->map('acmeCollection', '\Sokil\Mongo\CarsCollection');
        $this->database->map('acmeGridfs', '\Sokil\Mongo\CarPhotosGridFS');

        // create collection
        $this->assertInstanceOf('\Sokil\Mongo\CarsCollection', $this->database->getCollection('acmeCollection'));

        // create document
        $this->assertInstanceOf('\Sokil\Mongo\CarDocument', $this->database->getCollection('acmeCollection')->createDocument());

        // create grid fs
        $fs = $this->database->getGridFS('acmeGridfs');
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotosGridFS', $fs);

        // create file
        $id = $fs->storeBytes('hello');
        $file = $fs->getFileById($id);
        $this->assertInstanceOf('\Sokil\Mongo\CarPhotoGridFSFile', $file);

        $fs->delete();
    }

    public function testMapRegexpCollectionNameToClass()
    {
        $this->database->map('/littleCar(\d)/', '\Sokil\Mongo\CarsCollection');
        
        $this->database->map(array(
            '/bigCar(\d+)/' => '\Sokil\Mongo\CarsCollection',
        ));

        $collection = $this->database->getCollection('littleCar5');
        $this->assertInstanceOf(
            '\Sokil\Mongo\CarsCollection',
            $collection
        );

        $this->assertEquals(array(
            'littleCar5',
            5
        ), $collection->getOption('regexp'));

        $collection = $this->database->getCollection('bigCar42');

        $this->assertInstanceOf(
            '\Sokil\Mongo\CarsCollection',
            $collection
        );

        $this->assertEquals(array(
            'bigCar42',
            42
        ), $collection->getOption('regexp'));
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Class \ThisClassIsNotExists not found while map collection name to class
     */
    public function testMap_UnexistedClassInMapping()
    {
        $this->database->resetMapping();
        $this->database->map(array(
            'acmeCollection'    => '\ThisClassIsNotExists',
        ));

        $this->database->getCollection('acmeCollection');
    }

    public function testMap_ClassNamespace()
    {
        $this->database->resetMapping();
        $this->database->map('\Sokil\Mongo');

        $reflectionClass = new \ReflectionClass($this->database);
        $method = $reflectionClass->getMethod('getCollectionDefinition');
        $method->setAccessible(true);

        $classDefinition1 = $method->invoke($this->database, 'carPhotosGridFS');
        $classDefinition2 = $method->invoke($this->database, 'CarPhotosGridFS');

        $this->assertEquals(
            '\Sokil\Mongo\CarPhotosGridFS',
            $classDefinition1->class
        );

        $this->assertEquals(
            '\Sokil\Mongo\CarPhotosGridFS',
            $classDefinition2->class
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Wrong definition passed for collection acmeCollection
     */
    public function testMap_InvalidDefinitionVariableType()
    {
        $this->database->resetMapping();
        $this->database->map(array(
            'acmeCollection'    => 42,
        ));

        $this->database->getCollection('collection');
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
     * @expectedExceptionMessage Must be instance of \Sokil\Mongo\GridFS
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