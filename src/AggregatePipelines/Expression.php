<?php

namespace Sokil\Mongo\AggregatePipelines;

/**
 * @link http://docs.mongodb.org/manual/meta/aggregation-quick-reference/#expressions
 */
class Expression
{
    private $expression = array();

    /**
     * @param array<literal|callable|\Sokil\Mongo\AggregatePipelines\Expression> $expressions
     */
    public function multiply($expressions)
    {
        $this->expression['$multiply'] = $expressions;
    }

    public function toArray()
    {
        return $this->expression;
    }
}