<?php

namespace Sokil\Mongo;

class AggregatePipelinesTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private static $collection;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        self::$collection = $database->getCollection('phpmongo_test_collection');
    }
    
    public function testPipelineAppendFewGroups() {
        $pipelines  = new AggregatePipelines;
        
        $pipelines->group(array(
            '_id'   => '$field1',
            'group1'  => array('$sum' => '$field2'),
            'group2'  => array('$sum' => '$field3'),
        ));
        
        $pipelines->group(array(
            '_id'   => array('id1' => '$_id', 'id2' => '$group1'),
            'field' => array('$sum' => '$group2')
        ));
        
        $this->assertEquals(
            array(
                array('$group' => array(
                    '_id'   => '$field1',
                    'group1'  => array('$sum' => '$field2'),
                    'group2'  => array('$sum' => '$field3'),
                )),
                array('$group' => array(
                    '_id'   => array('id1' => '$_id', 'id2' => '$group1'),
                    'field' => array('$sum' => '$group2')
                )),
            ), $pipelines->toArray());
    }
    
    /**
     * Check if pipeline added as new or appended to previouse on same operator
     * 
     * @covers \Sokil\Mongo\AggregatePipeline::_add
     */
    public function testPipelineAppend() {
        
        $pipelines = self::$collection->createPipeline();
        
        // insert new match pipeline
        $pipelines->match(array(
            'field1'    => 'value1'
        ));
        
        // insert new project pipeline
        $pipelines->project(array(
            'field2'    => 'value2'
        ));
        
        // insert new match pipeline
        $pipelines->match(array(
            'field3'    => 'value3'
        ));
        
        // append match pipeline to previous
        $pipelines->match(array(
            'field3'    => 'value3merged',
            'field4'    => 'value4'
        ));
        
        // insert new sort pipeline
        $pipelines->sort(array(
            'field5'    => 'value5'
        ));
        
        // insert new group pipeline
        $pipelines->group(array(
            '_id'       => '$groupField',
            'field6'    => array('$sum' => 1)
        ));
        
        $this->assertEquals(array(
            array('$match'      => array('field1' => 'value1')),
            array('$project'    => array('field2' => 'value2')),
            array('$match'      => array(
                'field3' => 'value3merged',
                'field4' => 'value4',
            )),
            array('$sort'       => array('field5' => 'value5')),
            array('$group'      => array('_id' => '$groupField', 'field6' => array('$sum' => 1))),
        ), $pipelines->toArray());
        
    }
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testErrorOnEmptyIDInGroup() {
        $pipelines  = new AggregatePipelines;
        
        $pipelines->group(array(
            'field' => 'value'
        ));
    }

}
