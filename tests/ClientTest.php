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
        $this->client = new Client(MONGO_DSN);
    }

    public function testConstructClientWithConnectOptions()
    {
        $client = new Client(MONGO_DSN, array(
            'param' => 'value',
        ));

        $this->assertEquals(array(
            'param' => 'value',
        ), $client->getConnectOptions());
    }

    public function testSetConnection()
    {
        $mongoClient = new \MongoClient(MONGO_DSN);

        $client = new Client;
        $client->setMongoClient($mongoClient);

        $this->assertEquals($mongoClient, $client->getMongoClient());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage DSN not specified
     */
    public function testGetConnectionWhenNoDSNSpecified()
    {
        $client = new Client;
        $client->getMongoClient();
    }

    public function testGetDatabase()
    {
        $this->assertInstanceOf('\Sokil\Mongo\Database', $this->client->getDatabase('test'));

        $this->assertInstanceOf('\Sokil\Mongo\Database', $this->client->test);
    }

    public function testGetDatabase_NameNotSpecified_DefaultNameSpecified()
    {
        $client = new Client('mongodb://127.0.0.1/');
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
                'db1Collection1'  => '\Db1Collection1Class',
                'db1Collection2'  => '\Db1Collection2Class',
            ),
            'db2'   => '\Some\Class\Prefix\\',
        ));
        
        $this->assertEquals(
            '\Db1Collection2Class',
            $this->client->getDatabase('db1')->getCollectionClassName('db1Collection2')
        );
    }
    
    public function testMapUndeclaredCollectionToClass()
    {
        $this->client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Db1Collection1Class',
                'db1Collection2'  => '\Db1Collection2Class',
            ),
            'db2'   => '\Some\Class\Prefix\\',
        ));
        
        $this->assertEquals(
            '\Sokil\Mongo\Collection',
            $this->client->getDatabase('db1')->getCollectionClassName('undeclaredCollection')
        );
        
        $this->assertEquals(
            '\Sokil\Mongo\Collection',
            $this->client->getDatabase('undeclaredDatabase')->getCollectionClassName('undeclaredCollection')
        );
    }
    
    public function testMapCollectionToClassPrefix()
    {
        $this->client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Db1Collection1Class',
                'db1Collection2'  => '\Db1Collection2Class',
            ),
            'db2'   => '\Some\Class\Prefix\\',
        ));
        
        $this->assertEquals(
            '\Some\Class\Prefix\Some\Collection\Name',
            $this->client->getDatabase('db2')->getCollectionClassName('some.collection.name')
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

        $this->assertEquals(1, preg_match('#^[0-9]+(\.[0-9]+(\.[0-9]+)?)?$#', $version));
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
        $mongoClientMock = $this->getMock(
            '\MongoClient',
            array('setWriteConcern')
        );

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