<?php

namespace Sokil\Mongo;

class AggregatePipelinesTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp()
    {
        // connect to mongo
        $client = new Client();

        // select database
        $database = $client->getDatabase('test');

        // select collection
        $this->collection = $database->getCollection('phpmongo_test_collection');
    }

    public function tearDown()
    {
        $this->collection->delete();
    }

    public function testSkipLimit()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->skip(11)->limit(23);

        $this->assertEquals('[{"$skip":11},{"$limit":23}]', (string) $pipeline);
    }

    public function testPipeline_AppendFewGroups()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->group(array(
            '_id' => '$field1',
            'group1' => array('$sum' => '$field2'),
            'group2' => array('$sum' => '$field3'),
        ));

        $pipeline->group(array(
            '_id' => array('id1' => '$_id', 'id2' => '$group1'),
            'field' => array('$sum' => '$group2')
        ));

        $this->assertEquals(
            array(
            array('$group' => array(
                    '_id' => '$field1',
                    'group1' => array('$sum' => '$field2'),
                    'group2' => array('$sum' => '$field3'),
                )),
            array('$group' => array(
                    '_id' => array('id1' => '$_id', 'id2' => '$group1'),
                    'field' => array('$sum' => '$group2')
                )),
            ), $pipeline->toArray());
    }

    /**
     * Check if pipeline added as new or appended to previouse on same operator
     */
    public function testPipeline_Append()
    {

        $pipeline = new Pipeline($this->collection);

        // insert new match pipeline
        $pipeline->match(array(
            'field1' => 'value1'
        ));

        // insert new project pipeline
        $pipeline->project(array(
            'field2' => 'value2'
        ));

        // insert new match pipeline
        $pipeline->match(array(
            'field3' => 'value3'
        ));

        // append match pipeline to previous
        $pipeline->match(array(
            'field3' => 'value3merged',
            'field4' => 'value4'
        ));

        // insert new sort pipeline
        $pipeline->sort(array(
            'field5' => 'value5'
        ));

        // insert new group pipeline
        $pipeline->group(array(
            '_id' => '$groupField',
            'field6' => array('$sum' => 1)
        ));

        $this->assertEquals(array(
            array('$match' => array('field1' => 'value1')),
            array('$project' => array('field2' => 'value2')),
            array('$match' => array(
                    'field3' => 'value3merged',
                    'field4' => 'value4',
                )),
            array('$sort' => array('field5' => 'value5')),
            array('$group' => array('_id' => '$groupField', 'field6' => array('$sum' => 1))),
            ), $pipeline->toArray());
    }

    public function testUnwind()
    {
        // add documents
        $this->collection->batchInsert(array(
            array('subdoc' => array('key' => array(1, 2))),
            array('subdoc' => array('key' => array(4, 8))),
        ));

        // create pipeline
        $pipeline = new Pipeline($this->collection);
        $pipeline->unwind('$subdoc.key');

        // get result
        $result = $pipeline->aggregate();

        $this->assertEquals(4, count($result));

        $this->assertEquals(1, $result[0]['subdoc']['key']);
        $this->assertEquals(2, $result[1]['subdoc']['key']);
        $this->assertEquals(4, $result[2]['subdoc']['key']);
        $this->assertEquals(8, $result[3]['subdoc']['key']);

        $this->assertTrue((string) $result[0]['_id'] === (string) $result[1]['_id']);
        $this->assertTrue((string) $result[2]['_id'] === (string) $result[3]['_id']);

        $this->assertTrue((string) $result[0]['_id'] !== (string) $result[2]['_id']);
    }

    /**
     * Check if pipeline added as new or appended to previouse on same operator
     */
    public function testPipeline_ToString()
    {

        $pipeline = new Pipeline($this->collection);

        // insert new match pipeline
        $pipeline->match(array(
            'field1' => 'value1'
        ));

        // insert new project pipeline
        $pipeline->project(array(
            'field2' => 'value2'
        ));

        // insert new match pipeline
        $pipeline->match(array(
            'field3' => 'value3'
        ));

        // append match pipeline to previous
        $pipeline->match(array(
            'field3' => 'value3merged',
            'field4' => 'value4'
        ));

        // insert new sort pipeline
        $pipeline->sort(array(
            'field5' => 'value5'
        ));

        // insert new group pipeline
        $pipeline->group(array(
            '_id' => '$groupField',
            'field6' => array(
                '$sum' => 1
            )
        ));

        $validJson = '[{"$match":{"field1":"value1"}},{"$project":{"field2":"value2"}},{"$match":{"field3":"value3merged","field4":"value4"}},{"$sort":{"field5":"value5"}},{"$group":{"_id":"$groupField","field6":{"$sum":1}}}]';
        $this->assertEquals($validJson, $pipeline->__toString());
    }

    public function testPipeline_MatchCallable()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->match(function($expression) {
            /* @var $expression \Sokil\Mongo\Expression */
            $expression
                ->where('a', 1)
                ->whereLess('b', 12);
        });

        $this->assertEquals(
            '[{"$match":{"a":1,"b":{"$lt":12}}}]',
            (string) $pipeline
        );
    }
    
    public function testAggregate_Callable()
    {
        $this->collection->insertMultiple(array(
            array('order' => 1, 'item' => 1, 'amount' => 110, 'category' => 1),
            array('order' => 1, 'item' => 2, 'amount' => 120, 'category' => 1),
            array('order' => 1, 'item' => 3, 'amount' => 130, 'category' => 2),
            array('order' => 2, 'item' => 1, 'amount' => 210, 'category' => 1),
            array('order' => 2, 'item' => 2, 'amount' => 220, 'category' => 1),
            array('order' => 2, 'item' => 3, 'amount' => 230, 'category' => 2),
        ));

        $result = $this->collection->aggregate(function($pipeline) {
            /* @var $pipeline \Sokil\Mongo\Pipeline */
            $pipeline
                ->match(array(
                    'category' => 1
                ))
                ->group(array(
                    '_id' => '$order',
                    'totalAmount' => array(
                        '$sum' => '$amount',
                    )
                ))
                ->sort(array(
                    '_id' => 1
            ));
        });

        $this->assertEquals(array (
            0 => array (
                '_id' => 1,
                'totalAmount' => 230,
            ),
            1 => array (
                '_id' => 2,
                'totalAmount' => 430,
            ),
        ), $result);
    }

    public function testAggregate_FluentInterface()
    {
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        $result = $this->collection
            ->createAggregator()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')))
            ->aggregate();

        $this->assertEquals(9, $result[0]['sum']);

    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Wrong pipeline specified
     */
    public function testAggregate_WrongArgument()
    {
        $this->collection->aggregate('hello');
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Aggregate error: some_error
     */
    public function testAggregate_ServerSideError()
    {
        $mongoDatabaseMock = $this->getMock(
            '\MongoDB',
            array('command'),
            array($this->collection->getDatabase()->getClient()->getMongoClient(), 'test')
        );

        $mongoDatabaseMock
            ->expects($this->once())
            ->method('command')
            ->will($this->returnValue(array(
                'ok' => (double) 0,
                'errmsg' => 'some_error',
                'code' => 1785342,
            )));

        $database = new Database($this->collection->getDatabase()->getClient(), $mongoDatabaseMock);
        $database
            ->getCollection('phpmongo_test_collection')
            ->aggregate(array(
                array('$match' => array('field' => 'value'))
            ));
    }

    public function testLogger()
    {
        // create documents
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        // create logger
        $logger = $this->getMock('\Psr\Log\LoggerInterface');
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('Sokil\Mongo\Collection:<br><b>Pipeline</b>:<br>[{"$match":{"param":{"$gte":2}}},{"$group":{"_id":0,"sum":{"$sum":"$param"}}}]');

        // set logger to client
        $this->collection->getDatabase()->getClient()->setLogger($logger);

        // aggregate
        $this->collection
            ->createAggregator()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')))
            ->aggregate();
    }

    public function testExplainAggregate()
    {
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        $pipeline = $this->collection
            ->createAggregator()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')));

        try {
            $explain = $this->collection->explainAggregate($pipeline);
            $this->assertArrayHasKey('stages', $explain);
        } catch (\Exception $e) {
            $this->assertEquals('Explain of aggregation implemented only from 2.6.0', $e->getMessage());
        }

    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Explain of aggregation implemented only from 2.6.0
     */
    public function testExplainAggregate_UnsupportedDbVersion()
    {
        // define db version where aggregate explanation supported
        $clientMock = $this->getMock(
            '\Sokil\Mongo\Client',
            array('getDbVersion')
        );

        $clientMock
            ->expects($this->once())
            ->method('getDbVersion')
            ->will($this->returnValue('2.4.0'));

        $clientMock->setMongoClient($this->collection->getDatabase()->getClient()->getMongoClient());

        $clientMock
            ->getDatabase('test')
            ->getCollection('phpmongo_test_collection')
            ->explainAggregate(array());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     * @expectedExceptionMessage Wrong pipeline specified
     */
    public function testExplainAggregate_WrongArgument()
    {
        // define db version where aggregate explanation supported
        $clientMock = $this->getMock(
            '\Sokil\Mongo\Client',
            array('getDbVersion')
        );

        $clientMock
            ->expects($this->once())
            ->method('getDbVersion')
            ->will($this->returnValue('2.6.0'));

        $clientMock->setMongoClient($this->collection->getDatabase()->getClient()->getMongoClient());

        $this->collection = $clientMock
            ->getDatabase('test')
            ->getCollection('phpmongo_test_collection')
            ->explainAggregate('wrong_argument');
    }

}
