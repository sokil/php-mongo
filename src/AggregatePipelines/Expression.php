<?php

namespace Sokil\Mongo\AggregatePipelines;

/**
 * @link http://docs.mongodb.org/manual/meta/aggregation-quick-reference/#expressions
 */
class Expression
{
    private $expression = array();

    /**
     * @kibk http://docs.mongodb.org/manual/reference/operator/aggregation/add/
     * @param array<literal|callable|\Sokil\Mongo\AggregatePipelines\Expression> $expressions
     */
    public function add($expressions)
    {
        $this->expression['$add'] = (func_num_args() === 1)
            ? $expressions
            : func_get_args();

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/divide/
     * @param literal|callable|\Sokil\Mongo\AggregatePipelines\Expression $expression1
     * @param literal|callable|\Sokil\Mongo\AggregatePipelines\Expression $expression2
     */
    public function divide($expression1, $expression2)
    {
        $this->expression['$divide'] = array(
            $expression1,
            $expression2
        );

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/mod/
     * @param literal|callable|\Sokil\Mongo\AggregatePipelines\Expression $expression1
     * @param literal|callable|\Sokil\Mongo\AggregatePipelines\Expression $expression2
     */
    public function mod($expression1, $expression2)
    {
        $this->expression['$mod'] = array(
            $expression1,
            $expression2
        );

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/multiply/
     * @param array<literal|callable|\Sokil\Mongo\AggregatePipelines\Expression> $expressions
     */
    public function multiply($expressions)
    {
        $this->expression['$multiply'] = $expressions;

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/subtract/
     * @param literal|callable|\Sokil\Mongo\AggregatePipelines\Expression $expression1
     * @param literal|callable|\Sokil\Mongo\AggregatePipelines\Expression $expression2
     */
    public function subtract($expression1, $expression2)
    {
        $this->expression['$subtract'] = array(
            $expression1,
            $expression2
        );

        return $this;
    }

    public function toArray()
    {
        return $this->expression;
    }
}