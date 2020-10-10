<?php

namespace Sokil\Mongo;

use PHPUnit\Framework\TestCase;

class AggregatePipelinesTest extends TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp(): void
    {
        // connect to mongo
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
        $client->debug();

        // select database
        $database = $client->getDatabase('test');

        // select collection
        $this->collection = $database->getCollection('phpmongo_test_collection');
    }

    public function tearDown(): void
    {
        $this->collection->delete();
    }

    public function testSkipLimit()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->skip(11)->limit(23);

        $this->assertEquals('[{"$skip":11},{"$limit":23}]', (string) $pipeline);
    }

    public function testSkipReset()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline
            ->skip(11)
            ->match(array(
                'a' => 1,
                'b' => array(
                    '$lt' => 12,
                )
            ))
            ->skip(23);

        $this->assertEquals('[{"$skip":11},{"$match":{"a":1,"b":{"$lt":12}}},{"$skip":23}]', (string) $pipeline);
    }

    public function testLimitReset()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline
            ->limit(11)
            ->match(array(
                'a' => 1,
                'b' => array(
                    '$lt' => 12,
                )
            ))
            ->limit(23);

        $this->assertEquals('[{"$limit":11},{"$match":{"a":1,"b":{"$lt":12}}},{"$limit":23}]', (string) $pipeline);
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
        $this->collection->batchInsert(
            [
                ['subdoc' => ['key' => [1, 2]]],
                ['subdoc' => ['key' => [4, 8]]],
            ]
        );

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

    public function testPipeline_MatchExpression()
    {
        $pipeline = new Pipeline($this->collection);

        $expression = new Expression();
        $expression
            ->where('a', 1)
            ->whereLess('b', 12);

        $pipeline->match($expression);

        $this->assertEquals(
            '[{"$match":{"a":1,"b":{"$lt":12}}}]',
            (string) $pipeline
        );
    }

    public function testPipeline_MatchArray()
    {
        $pipeline = new Pipeline($this->collection);

        $pipeline->match(array(
            'a' => 1,
            'b' => array(
                '$lt' => 12,
            )
        ));

        $this->assertEquals(
            '[{"$match":{"a":1,"b":{"$lt":12}}}]',
            (string) $pipeline
        );
    }
    
    public function testAggregate_Callable()
    {
        $this->collection->batchInsert(array(
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

    public function testAggregate_WrongArgument()
    {
        $this->expectException(\Sokil\Mongo\Exception::class);
        $this->expectExceptionMessage('Wrong pipeline specified');

        $this->collection->aggregate('hello');
    }

    public function testAggregate_AllowDiskUseOption()
    {
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        $pipeline = $this->collection
            ->createAggregator()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')))
            ->allowDiskUse();

        try {
            $result = $this->collection->aggregate($pipeline);
            $this->assertArrayHasKey('sum', $result['0']);
            $this->assertEquals(9, $result['0']['sum']);
        } catch (\Exception $e) {
            $this->assertEquals('Option allowDiskUse of aggregation implemented only from 2.6.0', $e->getMessage());
        }
    }

    public function testAggregate_ResultAsCursor()
    {
        $this->collection->delete();
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        $pipeline = $this->collection
            ->createAggregator()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')));

        $result = $this->collection->aggregate($pipeline, array(), true);

        $this->assertInstanceOf('\MongoCommandCursor', $result);

        $result = iterator_to_array($result);
        $this->assertSame(
            [
                ['_id' => 0, 'sum' => 9],
            ],
            $result
        );
    }

    public function testAggregateExplainByPipeline()
    {
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        $pipeline = $this->collection
            ->createAggregator()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')))
            ->explain();

        try {
            $explain = $this->collection->aggregate($pipeline);
            $this->assertArrayHasKey('$cursor', $explain['0']);
            $this->assertArrayHasKey('$group', $explain['1']);
        } catch (\Exception $e) {
            $this->assertEquals('Explain of aggregation implemented only from 2.6.0', $e->getMessage());
        }
    }

    public function testAggregateExplainByOption()
    {
        $this->collection->createDocument(array('param' => 1))->save();
        $this->collection->createDocument(array('param' => 2))->save();
        $this->collection->createDocument(array('param' => 3))->save();
        $this->collection->createDocument(array('param' => 4))->save();

        $pipeline = $this->collection
            ->createAggregator()
            ->match(array('param' => array('$gte' => 2)))
            ->group(array('_id' => 0, 'sum' => array('$sum' => '$param')));

        $explain = $this->collection->aggregate($pipeline, ['explain' => true]);
        $this->assertNotEmpty($explain['0']['$cursor']);
    }
}
