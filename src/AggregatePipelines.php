<?php

namespace Sokil\Mongo;

use Sokil\Mongo\AggregatePipelines\GroupPipeline;

class AggregatePipelines
{

    private $pipelines = array();

    /**
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    private function add($operator, $pipeline)
    {
        $lastIndex = count($this->pipelines) - 1;

        if (!$this->pipelines || !isset($this->pipelines[$lastIndex][$operator]) || $operator == '$group') {
            $this->pipelines[] = array($operator => $pipeline);
        } else {
            $this->pipelines[$lastIndex][$operator] = array_merge($this->pipelines[$lastIndex][$operator], $pipeline);
        }
    }

    /**
     * Filter documents by expression
     *
     * @param array|\Sokil\Mongo\Expression $expression
     * @return \Sokil\Mongo\AggregatePipelines
     * @throws \Sokil\Mongo\Exception
     */
    public function match($expression)
    {
        if (is_callable($expression)) {
            $expressionConfigurator = $expression;
            $expression = new Expression();
            call_user_func($expressionConfigurator, $expression);
            $expression = $expression->toArray();
        } elseif (!is_array($expression)) {
            throw new Exception('Must be array or instance of Expression');
        }

        $this->add('$match', $expression);
        return $this;
    }

    /**
     * Passes along the documents with only the specified fields to the next
     * stage in the pipeline. The specified fields can be existing fields
     * from the input documents or newly computed fields.
     *
     * @param array $pipeline
     * @return \Sokil\Mongo\AggregatePipelines
     */
    public function project(array $pipeline)
    {
        $this->add('$project', $pipeline);
        return $this;
    }

    /**
     * Groups documents by some specified expression and outputs to the next 
     * stage a document for each distinct grouping. The output documents 
     * contain an _id field which contains the distinct group by key. The 
     * output documents can also contain computed fields that hold the values 
     * of some accumulator expression grouped by the $group‘s _id field. $group 
     * does not order its output documents.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/group/
     * 
     * @param array|callable $pipeline
     * @return \Sokil\Mongo\AggregatePipelines
     * @throws \Sokil\Mongo\Exception
     */
    public function group($pipeline)
    {
        if (is_callable($pipeline)) {
            $configurator = $pipeline;
            $pipeline = new GroupPipeline();
            call_user_func($configurator, $pipeline);
            $pipeline = $pipeline->toArray();
        } elseif(!is_array($pipeline)) {
            throw new Exception('Group pipeline must be array or instance of Sokil\Mongo\AggregatePipelines\GroupPipeline');
        }
        
        if (!isset($pipeline['_id'])) {
            throw new Exception('Group field in _id key must be specified');
        }

        $this->add('$group', $pipeline);
        return $this;
    }

    public function sort(array $sortFields)
    {
        $this->add('$sort', $sortFields);
        return $this;
    }

    public function toArray()
    {
        return $this->pipelines;
    }

    public function limit($limit)
    {
        $this->add('$limit', (int) $limit);
        return $this;
    }

    public function skip($skip)
    {
        $this->add('$skip', (int) $skip);
        return $this;
    }

    public function aggregate()
    {
        return $this->collection->aggregate($this);
    }

    public function __toString()
    {
        return json_encode($this->pipelines);
    }

}
