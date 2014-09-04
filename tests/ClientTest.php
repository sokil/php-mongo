<?php

namespace Sokil\Mongo;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Client
     */
    private static $client;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        self::$client = new Client('mongodb://127.0.0.1');
    }
    
    public static function tearDownAfterClass()
    {

    }

    public function testConstructClientWithConnectOptions()
    {
        $client = new Client('mongodb://127.0.0.1', array(
            'param' => 'value',
        ));

        $this->assertEquals(array(
            'param' => 'value',
        ), $client->getConnectOptions());
    }

    public function testSetConnection()
    {
        $connection = new \MongoClient('mongodb://127.0.0.1');

        $client = new Client;
        $client->setConnection($connection);

        $this->assertEquals($connection, $client->getConnection());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage DSN not specified
     */
    public function testGetConnectionWhenNoDSNSpecified()
    {
        $client = new Client;
        $client->getConnection();
    }

    public function testGetDatabase()
    {
        $this->assertInstanceOf('\Sokil\Mongo\Database', self::$client->getDatabase('test'));

        $this->assertInstanceOf('\Sokil\Mongo\Database', self::$client->test);
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
        self::$client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Db1Collection1Class',
                'db1Collection2'  => '\Db1Collection2Class',
            ),
            'db2'   => '\Some\Class\Prefix\\',
        ));
        
        $this->assertEquals(
            '\Db1Collection2Class',
            self::$client->getDatabase('db1')->getCollectionClassName('db1Collection2')
        );
    }
    
    public function testMapUndeclaredCollectionToClass()
    {
        self::$client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Db1Collection1Class',
                'db1Collection2'  => '\Db1Collection2Class',
            ),
            'db2'   => '\Some\Class\Prefix\\',
        ));
        
        $this->assertEquals(
            '\Sokil\Mongo\Collection',
            self::$client->getDatabase('db1')->getCollectionClassName('undeclaredCollection')
        );
        
        $this->assertEquals(
            '\Sokil\Mongo\Collection',
            self::$client->getDatabase('undeclaredDatabase')->getCollectionClassName('undeclaredCollection')
        );
    }
    
    public function testMapCollectionToClassPrefix()
    {
        self::$client->map(array(
            'db1'   => array(
                'db1Collection1'  => '\Db1Collection1Class',
                'db1Collection2'  => '\Db1Collection2Class',
            ),
            'db2'   => '\Some\Class\Prefix\\',
        ));
        
        $this->assertEquals(
            '\Some\Class\Prefix\Some\Collection\Name',
            self::$client->getDatabase('db2')->getCollectionClassName('some.collection.name')
        );
    }
    
    public function testUseDatabase()
    {
        $collection = self::$client
            ->useDatabase('test')
            ->getCollection('some-collection');
        
        $this->assertInstanceOf('\Sokil\Mongo\Collection', $collection);
    }
    
    public function testGetVersion()
    {
        $this->assertTrue(version_compare(self::$client->getVersion(), '0.9.0', '>='));
    }
    
    public function testGetDbVersion()
    {
        $version = self::$client->getDbVersion();

        $this->assertEquals(1, preg_match('#^[0-9]+(\.[0-9]+(\.[0-9]+)?)?$#', $version));
    }

    public function testReadPrimaryOnly()
    {
        self::$client->readPrimaryOnly();

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY
        ), self::$client->getReadPreference());
    }

    public function testReadPrimaryPreferred()
    {
        self::$client->readPrimaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_PRIMARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), self::$client->getReadPreference());
    }

    public function testReadSecondaryOnly(array $tags = null)
    {
        self::$client->readSecondaryOnly(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), self::$client->getReadPreference());
    }

    public function testReadSecondaryPreferred(array $tags = null)
    {
        self::$client->readSecondaryPreferred(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_SECONDARY_PREFERRED,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), self::$client->getReadPreference());
    }

    public function testReadNearest(array $tags = null)
    {
        self::$client->readNearest(array(
            array('dc' => 'kyiv'),
            array('dc' => 'lviv'),
        ));

        $this->assertEquals(array(
            'type' => \MongoClient::RP_NEAREST,
            'tagsets' => array(
                array('dc' => 'kyiv'),
                array('dc' => 'lviv'),
            ),
        ), self::$client->getReadPreference());
    }

    public function testSetLogger()
    {
        self::$client->removeLogger();

        $this->assertFalse(self::$client->hasLogger());

        self::$client->setLogger($this->getMock('\Psr\Log\LoggerInterface'));

        $this->assertTrue(self::$client->hasLogger());
    }

    public function testGetLogger()
    {
        self::$client->removeLogger();

        $this->assertFalse(self::$client->hasLogger());

        self::$client->setLogger($this->getMock('\Psr\Log\LoggerInterface'));

        $this->assertInstanceOf('\Psr\Log\LoggerInterface', self::$client->getLogger());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Error setting write concern
     */
    public function testErrorOnSetWriteConcern()
    {
        $connection = $this->getMock(
            '\MongoClient',
            array('setWriteConcern')
        );

        $connection
            ->expects($this->any())
            ->method('setWriteConcern')
            ->will($this->returnValue(false));

        $client = new Client();
        $client->setConnection($connection);

        $client->setWriteConcern(1);
    }

    public function testSetWriteConcern()
    {
        self::$client->setWriteConcern('majority', 12000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 12000
        ), self::$client->getWriteConcern());
    }

    public function testSetUnacknowledgedWriteConcern()
    {
        self::$client->setUnacknowledgedWriteConcern(11000);

        $this->assertEquals(array(
            'w' => 0,
            'wtimeout' => 11000
        ), self::$client->getWriteConcern());
    }

    public function testSetMajorityWriteConcern()
    {
        self::$client->setMajorityWriteConcern(13000);

        $this->assertEquals(array(
            'w' => 'majority',
            'wtimeout' => 13000
        ), self::$client->getWriteConcern());
    }
}