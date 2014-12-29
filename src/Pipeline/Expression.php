<?php

namespace Sokil\Mongo\Pipeline;

/**
 * @link http://docs.mongodb.org/manual/meta/aggregation-quick-reference/#expressions
 */
class Expression
{
    private $expression = array();

    /**
     * @kibk http://docs.mongodb.org/manual/reference/operator/aggregation/add/
     * @param array<literal|callable|\Sokil\Mongo\Pipeline\Expression> $expressions may me specified as one array of expressions and as list of expressions
     */
    public function add($expressions)
    {
        if(func_num_args() > 1) {
            $expressions = func_get_args();
        }

        $this->expression['$add'] = self::normalizeList($expressions);

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/divide/
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression1
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression2
     */
    public function divide($expression1, $expression2)
    {
        $this->expression['$divide'] = self::normalizeList(array(
            $expression1,
            $expression2
        ));

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/mod/
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression1
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression2
     */
    public function mod($expression1, $expression2)
    {
        $this->expression['$mod'] = self::normalizeList(array(
            $expression1,
            $expression2
        ));

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/multiply/
     * @param array<literal|callable|\Sokil\Mongo\Pipeline\Expression> $expressions may me specified as one array of expressions and as list of expressions
     */
    public function multiply($expressions)
    {
        if(func_num_args() > 1) {
            $expressions = func_get_args();
        }
        
        $this->expression['$multiply'] = self::normalizeList($expressions);

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/subtract/
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression1
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression2
     */
    public function subtract($expression1, $expression2)
    {
        $this->expression['$subtract'] = self::normalizeList(array(
            $expression1,
            $expression2
        ));

        return $this;
    }

    /**
     * Convert expressions specified in different formats to canonical form
     * 
     * @param array $expressions
     */
    public static function normalizeList(array $expressions)
    {
        foreach($expressions as $i => $expression) {
            $expressions[$i] = self::normalize($expression);
        }

        return $expressions;
    }

    /**
     * Convert expression specified in different formats to canonical form
     *
     * @param type $expression
     * @return type
     */
    public static function normalize($expression)
    {
        if(is_callable($expression)) {
            $expressionConfigurator = $expression;
            $expression = new Expression;
            call_user_func($expressionConfigurator, $expression);
        }

        if($expression instanceof Expression) {
            $expression = $expression->toArray();
        }

        return $expression;
    }
    
    public function toArray()
    {
        return $this->expression;
    }
}