<?php

namespace Sokil\Mongo;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private $client;
    
    public function setUp()
    {
        // connect to mongo
        $this->client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
    }

    public function testConstructClientWithConnectOptions()
    {
        $client = new Client(null, array(
            'param' => 'value',
        ));

        $this->assertEquals(array(
            'param' => 'value',
        ), $client->getConnectOptions());
    }

    public function testSetMongoClient()
    {
        $mongoClient = new \MongoClient(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);

        $client = new Client();
        $client->setMongoClient($mongoClient);

        $this->assertEquals($mongoClient, $client->getMongoClient());
    }

    public function testSetCredentials()
    {
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
        $client->setCredentials('u', 'p');

        $connectOptions = $client->getConnectOptions();

        $this->assertArrayHasKey('username', $connectOptions);
        $this->assertEquals('u', $connectOptions['username']);

        $this->assertArrayHasKey('password', $connectOptions);
        $this->assertEquals('p', $connectOptions['password']);

        return $this;
    }

    public function testGetConnectionWhenNoDSNSpecified()
    {
        $client = new Client();
        $this->assertEquals(Client::DEFAULT_DSN, $client->getDsn());
    }

    public function testGetDatabase()
    {
        $this->assertInstanceOf('\Sokil\Mongo\Database', $this->client->getDatabase('test'));

        $this->assertInstanceOf('\Sokil\Mongo\Database', $this->client->test);
    }

    public function testGetDatabase_NameNotSpecified_DefaultNameSpecified()
    {
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
        $client->useDatabase('some_name');

        $this->assertEquals('some_name', $client->getDatabase()->getName());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Database not selected
     */
    public function testGetDatabase_NameNotSpecified_DefaultNameNotSpecified()
    {
        $client = new Client('mongodb://127.0.0.1/');
        $this->assertEquals('some_name', $client->getDatabase()->getName());
    }
    
    public function testMapDeclaredCollectionToClass()
    {
        $this->client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Sokil\Mongo\Db1Collection1Class',
                'db1Collection2'  => '\Sokil\Mongo\Db1Collection2Class',
            ),
            'db2'   => '\Some\Class\Prefix\\',
        ));
        
        $database = $this->client->getDatabase('db1');
        
        $reflactionClass = new \ReflectionClass($database);
        $method = $reflactionClass->getMethod('getCollectionDefinition');
        $method->setAccessible(true);
        $collectionClassName = $method->invoke($database, 'db1Collection2');
        
        $this->assertEquals(
            '\Sokil\Mongo\Db1Collection2Class',
            $collectionClassName->class
        );
    }
    
    public function testMapUndeclaredCollectionToClass()
    {
        $this->client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Sokil\Mongo\Db1Collection1Class',
                'db1Collection2'  => '\Sokil\Mongo\Db1Collection2Class',
            ),
            'db2'   => '\Some\Class\Prefix\\',
        ));
        
        $database = $this->client->getDatabase('db1');
        
        $reflectionClass = new \ReflectionClass($database);
        $method = $reflectionClass->getMethod('getCollectionDefinition');
        $method->setAccessible(true); 
        
        $classDefinition = $method->invoke($database, 'undeclaredCollection');
        
        $this->assertEquals(
            '\Sokil\Mongo\Collection',
            $classDefinition->class
        );
    }
    
    public function testMap_CollectionToClassPrefix()
    {
        $this->client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Sokil\Mongo\Db1Collection1Class',
                'db1Collection2'  => '\Sokil\Mongo\Db1Collection2Class',
            ),
            'db2'   => array(
                '*' => array(
                    'class' => '\\Sokil\\Mongo\\',
                ),
            ),
        ));
        
        $database = $this->client->getDatabase('db2');
        
        $reflectionClass = new \ReflectionClass($database);
        $method = $reflectionClass->getMethod('getCollectionDefinition');
        $method->setAccessible(true);
        
        $classDefinition = $method->invoke($database, 'db2Collection1Class');
        
        $this->assertEquals(
            '\Sokil\Mongo\Db2Collection1Class',
            $classDefinition->class
        );
    }

    public function testMap_CollectionToClassPrefix_PointCollection()
    {
        $this->client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Sokil\Mongo\Db1Collection1Class',
                'db1Collection2'  => '\Sokil\Mongo\Db1Collection2Class',
            ),
            'db2'   => array(
                '*' => array(
                    'class' => '\\Sokil\\',
                ),
            ),
        ));

        $database = $this->client->getDatabase('db2');

        $reflectionClass = new \ReflectionClass($database);
        $method = $reflectionClass->getMethod('getCollectionDefinition');
        $method->setAccessible(true);

        $classDefinition = $method->invoke($database, 'mongo.db2Collection1Class');

        $this->assertEquals(
            '\Sokil\Mongo\Db2Collection1Class',
            $classDefinition->class
        );
    }

    public function testMap_CollectionToClassPrefixDeprecated()
    {
        $this->client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Sokil\Mongo\Db1Collection1Class',
                'db1Collection2'  => '\Sokil\Mongo\Db1Collection2Class',
            ),
            'db2'   => '\\Sokil\\Mongo\\',
        ));

        $database = $this->client->getDatabase('db2');

        $reflectionClass = new \ReflectionClass($database);
        $method = $reflectionClass->getMethod('getCollectionDefinition');
        $method->setAccessible(true);

        $classDefinition = $method->invoke($database, 'db2Collection1Class');

        $this->assertEquals(
            '\Sokil\Mongo\Db2Collection1Class',
            $classDefinition->class
        );
    }
    
    public function testUseDatabase()
    {
        $collection = $this->client
            ->useDatabase('test')
            ->getCollection('some-collection');
        
        $this->assertInstanceOf('\Sokil\Mongo\Collection', $collection);
    }
    
    public function testGetVersion()
    {
        $this->assertTrue(version_compare($this->client->getVersion(), '0.9.0', '>='));
    }
    
    public function testGetDbVersion()
    {
        $version = $this->client->getDbVersion();
        $this->assertEquals(1, preg_match('#^[0-9]+(\.[0-9]+(\.[0-9]+(-[0-9A-Za-z\-]+(\+[0-9A-Za-z\-]+)?)?)?)?$#', $version));
    }

    public function testReadPrimaryOnly()
    {
        $this->client->readPrimaryOnly();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY
        ), $this->client->getReadPreference());
    }

    public function testReadPrimaryPreferred()
    {
        $this->client->readPrimaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->client->getReadPreference());
    }

    public function testReadSecondaryOnly(array $tags = null)
    {
        $this->client->readSecondaryOnly(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->client->getReadPreference());
    }

    public function testReadSecondaryPreferred(array $tags = null)
    {
        $this->client->readSecondaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->client->getReadPreference());
    }

    public function testReadNearest(array $tags = null)
    {
        $this->client->readNearest(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_NEAREST,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), $this->client->getReadPreference());
    }

    public function testSetLogger()
    {
        $this->client->removeLogger();

        $this->assertFalse($this->client->hasLogger());

        $this->client->setLogger($this->getMock('\Psr\Log\LoggerInterface'));

        $this->assertTrue($this->client->hasLogger());
    }

    public function testGetLogger()
    {
        $this->client->removeLogger();

        $this->assertFalse($this->client->hasLogger());

        $this->client->setLogger($this->getMock('\Psr\Log\LoggerInterface'));

        $this->assertInstanceOf('\Psr\Log\LoggerInterface', $this->client->getLogger());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error setting write concern
     */
    public function testErrorOnSetWriteConcern()
    {
        $mongoClientMock = $this
            ->getMockBuilder(
                '\MongoClient',
                array('setWriteConcern')
            )
            ->disableOriginalConstructor()
            ->getMock();

        $mongoClientMock
            ->expects($this->any())
            ->method('setWriteConcern')
            ->will($this->returnValue(false));

        $client = new Client();
        $client->setMongoClient($mongoClientMock);

        $client->setWriteConcern(1);
    }

    public function testSetWriteConcern()
    {
        $this->client->setWriteConcern('majority', 12000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 12000
        ), $this->client->getWriteConcern());
    }

    public function testSetUnacknowledgedWriteConcern()
    {
        $this->client->setUnacknowledgedWriteConcern(11000);

        $this->assertEquals(array(
            'w' => 0,
            'wtimeout' => 11000
        ), $this->client->getWriteConcern());
    }

    public function testSetMajorityWriteConcern()
    {
        $this->client->setMajorityWriteConcern(13000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 13000
        ), $this->client->getWriteConcern());
    }
}

class Db1Collection1Class extends \Sokil\Mongo\Collection {}
class Db1Collection2Class extends \Sokil\Mongo\Collection {}
class Db2Collection1Class extends \Sokil\Mongo\Collection {}