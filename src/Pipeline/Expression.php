<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo\Pipeline;

use Sokil\Mongo\ArrayableInterface;

/**
 * Expressions used in Aggregation Framework
 *
 * @link http://docs.mongodb.org/manual/meta/aggregation-quick-reference/#expressions
 *
 * @author Dmytro Sokil <dmytro.sokil@gmail.com>
 */
class Expression implements ArrayableInterface
{
    private $expression = array();

    /**
     * Returns true only when all its expressions evaluate to true.
     * Accepts any number of argument expressions.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/and
     */
    public function all()
    {
        $expressions = func_get_args();

        $this->expression['$and'] = self::normalizeEach($expressions);

        return $this;
    }

    /**
     * Returns the boolean value that is the opposite of its argument expression.
     * Accepts a single argument expression.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/not
     */
    public function not($expression)
    {
        $this->expression['$not'] = self::normalize($expression);
        return $this;
    }

    /**
     * Returns true when any of its expressions evaluates to true.
     * Accepts any number of argument expressions.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/or
     */
    public function any()
    {
        $expressions = func_get_args();

        $this->expression['$or'] = self::normalizeEach($expressions);

        return $this;
    }

    /**
     * @kibk http://docs.mongodb.org/manual/reference/operator/aggregation/add/
     * @param array<literal|callable|\Sokil\Mongo\Pipeline\Expression> $expressions may me specified as one array of expressions and as list of expressions
     */
    public function add($expressions)
    {
        if (func_num_args() > 1) {
            $expressions = func_get_args();
        }

        $this->expression['$add'] = self::normalizeEach($expressions);

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/divide/
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression1
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression2
     */
    public function divide($expression1, $expression2)
    {
        $this->expression['$divide'] = self::normalizeEach(array(
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
        $this->expression['$mod'] = self::normalizeEach(array(
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
        if (func_num_args() > 1) {
            $expressions = func_get_args();
        }
        
        $this->expression['$multiply'] = self::normalizeEach($expressions);

        return $this;
    }

    /**
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/subtract/
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression1
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression2
     */
    public function subtract($expression1, $expression2)
    {
        $this->expression['$subtract'] = self::normalizeEach(array(
            $expression1,
            $expression2
        ));

        return $this;
    }

    /**
     * Convert expressions specified in different formats to canonical form
     *
     * @param array<callable|\Sokil\Mongo\Expression> $expressions
     */
    public static function normalizeEach(array $expressions)
    {
        foreach ($expressions as $i => $expression) {
            $expressions[$i] = self::normalize($expression);
        }

        return $expressions;
    }

    /**
     * Convert expression specified in different formats to canonical form
     *
     * @param Expression|callable $expression
     * @return array
     */
    public static function normalize($expression)
    {
        if (is_callable($expression)) {
            $expressionConfigurator = $expression;
            $expression = new Expression;
            call_user_func($expressionConfigurator, $expression);
        }

        if ($expression instanceof Expression) {
            $expression = $expression->toArray();
        } elseif (is_array($expression)) {
            foreach ($expression as $fieldName => $value) {
                $expression[$fieldName] = self::normalize($value);
            }
        }

        return $expression;
    }
    
    public function toArray()
    {
        return $this->expression;
    }
}
