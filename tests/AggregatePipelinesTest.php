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

    public function testPipelineAppendFewGroups()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(array(
            '_id' => '$field1',
            'group1' => array('$sum' => '$field2'),
            'group2' => array('$sum' => '$field3'),
        ));

        $pipelines->group(array(
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
            ), $pipelines->toArray());
    }

    /**
     * Check if pipeline added as new or appended to previouse on same operator
     */
    public function testPipelineAppend()
    {

        $pipelines = new AggregatePipelines($this->collection);

        // insert new match pipeline
        $pipelines->match(array(
            'field1' => 'value1'
        ));

        // insert new project pipeline
        $pipelines->project(array(
            'field2' => 'value2'
        ));

        // insert new match pipeline
        $pipelines->match(array(
            'field3' => 'value3'
        ));

        // append match pipeline to previous
        $pipelines->match(array(
            'field3' => 'value3merged',
            'field4' => 'value4'
        ));

        // insert new sort pipeline
        $pipelines->sort(array(
            'field5' => 'value5'
        ));

        // insert new group pipeline
        $pipelines->group(array(
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
            ), $pipelines->toArray());
    }

    /**
     * Check if pipeline added as new or appended to previouse on same operator
     */
    public function testPipelineToString()
    {

        $pipelines = new AggregatePipelines($this->collection);

        // insert new match pipeline
        $pipelines->match(array(
            'field1' => 'value1'
        ));

        // insert new project pipeline
        $pipelines->project(array(
            'field2' => 'value2'
        ));

        // insert new match pipeline
        $pipelines->match(array(
            'field3' => 'value3'
        ));

        // append match pipeline to previous
        $pipelines->match(array(
            'field3' => 'value3merged',
            'field4' => 'value4'
        ));

        // insert new sort pipeline
        $pipelines->sort(array(
            'field5' => 'value5'
        ));

        // insert new group pipeline
        $pipelines->group(array(
            '_id' => '$groupField',
            'field6' => array(
                '$sum' => 1
            )
        ));

        $validJson = '[{"$match":{"field1":"value1"}},{"$project":{"field2":"value2"}},{"$match":{"field3":"value3merged","field4":"value4"}},{"$sort":{"field5":"value5"}},{"$group":{"_id":"$groupField","field6":{"$sum":1}}}]';
        $this->assertEquals($validJson, $pipelines->__toString());
    }

    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testErrorOnEmptyIDInGroup()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->group(array(
            'field' => 'value'
        ));
    }

    public function testSkipLimit()
    {
        $pipelines = new AggregatePipelines($this->collection);

        $pipelines->skip(11)->limit(23);

        $this->assertEquals('[{"$skip":11},{"$limit":23}]', (string) $pipelines);
    }

    public function testMatchGroupAggregation()
    {
        $this->collection->insertMultiple(array(
            array('order' => 1, 'item' => 1, 'amount' => 110, 'category' => 1),
            array('order' => 1, 'item' => 2, 'amount' => 120, 'category' => 1),
            array('order' => 1, 'item' => 3, 'amount' => 130, 'category' => 2),
            array('order' => 2, 'item' => 1, 'amount' => 210, 'category' => 1),
            array('order' => 2, 'item' => 2, 'amount' => 220, 'category' => 1),
            array('order' => 2, 'item' => 3, 'amount' => 230, 'category' => 2),
        ));

        $result = $this->collection->aggregate(function($pipelines) {
            /* @var $pipelines \Sokil\Mongo\AggregatePipelines */
            $pipelines
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

}
