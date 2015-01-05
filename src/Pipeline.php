<?php

namespace Sokil\Mongo;

use Sokil\Mongo\Pipeline\GroupStage;

class Pipeline
{

    private $stages = array();

    /**
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    private function addStage($operator, $stage)
    {
        $lastIndex = count($this->stages) - 1;

        if (!$this->stages || !isset($this->stages[$lastIndex][$operator]) || $operator == '$group') {
            $this->stages[] = array($operator => $stage);
        } else {
            $this->stages[$lastIndex][$operator] = array_merge($this->stages[$lastIndex][$operator], $stage);
        }
    }

    /**
     * Filter documents by expression
     *
     * @param array|\Sokil\Mongo\Expression $expression
     * @return \Sokil\Mongo\Pipeline
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

        $this->addStage('$match', $expression);
        return $this;
    }

    /**
     * Passes along the documents with only the specified fields to the next
     * stage in the pipeline. The specified fields can be existing fields
     * from the input documents or newly computed fields.
     *
     * @param array $pipeline
     * @return \Sokil\Mongo\Pipeline
     */
    public function project(array $pipeline)
    {
        $this->addStage('$project', $pipeline);
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
     * @param array|callable $stage
     * @return \Sokil\Mongo\Pipeline
     * @throws \Sokil\Mongo\Exception
     */
    public function group($stage)
    {
        if (is_callable($stage)) {
            $configurator = $stage;
            $stage = new GroupStage();
            call_user_func($configurator, $stage);
        }
        
        if($stage instanceof GroupStage) {
            $stage = $stage->toArray();
        }

        if(!is_array($stage)) {
            throw new Exception('Group stage must be array or instance of Sokil\Mongo\Pipeline\GroupStage or callabe');
        }
        
        if (!isset($stage['_id'])) {
            throw new Exception('Group field in _id key must be specified');
        }

        $this->addStage('$group', $stage);
        return $this;
    }

    public function sort(array $sortFields)
    {
        $this->addStage('$sort', $sortFields);
        return $this;
    }

    public function limit($limit)
    {
        $this->addStage('$limit', (int) $limit);
        return $this;
    }

    public function skip($skip)
    {
        $this->addStage('$skip', (int) $skip);
        return $this;
    }

    public function aggregate()
    {
        return $this->collection->aggregate($this);
    }

    public function toArray()
    {
        return $this->stages;
    }

    public function __toString()
    {
        return json_encode($this->stages);
    }

}