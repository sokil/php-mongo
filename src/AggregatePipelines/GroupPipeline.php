<?php

namespace Sokil\Mongo\AggregatePipelines;

class GroupPipeline
{
    private $pipeline = array();

    public function setId($id)
    {
        $this->pipeline['_id'] = $id;
        return $this;
    }

    /**
     * Calculates and returns the sum of all the numeric values that result
     * from applying a specified expression to each document in a group of
     * documents that share the same group by key. $sum ignores
     * non-numeric values.
     *
     * @see http://docs.mongodb.org/manual/meta/aggregation-quick-reference/#aggregation-expressions
     * 
     * @param string $field
     * @param literal|callable|\Sokil\Mongo\AggregatePipelines\Expression $expression Expression
     * @return \Sokil\Mongo\AggregatePipelines\GroupPipeline
     */
    public function sum($field, $expression)
    {
        if(is_callable($expression)) {
            $expressionConfigurator = $expression;
            $expression = new Expression;
            call_user_func($expressionConfigurator, $expression);
        }

        if($expression instanceof Expression) {
            $expression = $expression->toArray();
        }

        $this->pipeline[$field]['$sum'] = $expression;

        return $this;
    }

    public function toArray()
    {
        return $this->pipeline;
    }
}