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
 * Group stage accumulators
 *
 * @link http://docs.mongodb.org/manual/meta/aggregation-quick-reference/#accumulators
 *
 * @author Dmytro Sokil <dmytro.sokil@gmail.com>
 */
class GroupStage implements ArrayableInterface
{
    private $stage = array();

    public function setId($id)
    {
        $this->stage['_id'] = $id;
        return $this;
    }

    /**
     * Sum accumulator
     *
     * Calculates and returns the sum of all the numeric values that result
     * from applying a specified expression to each document in a group of
     * documents that share the same group by key. $sum ignores
     * non-numeric values.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/sum
     *
     * @param string $field
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression Expression
     * @return \Sokil\Mongo\Pipeline\GroupStage
     */
    public function sum($field, $expression)
    {
        $this->stage[$field]['$sum'] = Expression::normalize($expression);

        return $this;
    }

    /**
     * Add to set accumulator
     *
     * Returns an array of all unique values that results from applying an
     * expression to each document in a group of documents that share the
     * same group by key. Order of the elements in the output
     * array is unspecified.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/addToSet
     *
     * @param string $field
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression Expression
     * @return \Sokil\Mongo\Pipeline\GroupStage
     */
    public function addToSet($field, $expression)
    {
        $this->stage[$field]['$addToSet'] = Expression::normalize($expression);

        return $this;
    }

    /**
     * Average accumulator
     *
     * Returns the average value of the numeric values that result from
     * applying a specified expression to each document in a group of
     * documents that share the same group by key. $avg ignores
     * non-numeric values.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/avg
     *
     * @param string $field
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression Expression
     * @return \Sokil\Mongo\Pipeline\GroupStage
     */
    public function avg($field, $expression)
    {
        $this->stage[$field]['$avg'] = Expression::normalize($expression);

        return $this;
    }

    /**
     * Returns the value that results from applying an expression
     * to the first document in a group of documents that share the same
     * group by key. Only meaningful when documents are in a defined order.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/first
     *
     * @param string $field
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression Expression
     * @return \Sokil\Mongo\Pipeline\GroupStage
     */
    public function first($field, $expression)
    {
        $this->stage[$field]['$first'] = Expression::normalize($expression);

        return $this;
    }

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents that share the same group by a field.
     * Only meaningful when documents are in a defined order.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/last
     *
     * @param string $field
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression Expression
     * @return \Sokil\Mongo\Pipeline\GroupStage
     */
    public function last($field, $expression)
    {
        $this->stage[$field]['$last'] = Expression::normalize($expression);

        return $this;
    }

    /**
     * Returns the highest value that results from applying an expression
     * to each document in a group of documents that share the same group by key.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/max
     *
     * @param string $field
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression Expression
     * @return \Sokil\Mongo\Pipeline\GroupStage
     */
    public function max($field, $expression)
    {
        $this->stage[$field]['$max'] = Expression::normalize($expression);

        return $this;
    }

    /**
     * Returns the lowest value that results from applying an expression
     * to each document in a group of documents that share the same group by key.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/min
     *
     * @param string $field
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression Expression
     * @return \Sokil\Mongo\Pipeline\GroupStage
     */
    public function min($field, $expression)
    {
        $this->stage[$field]['$min'] = Expression::normalize($expression);

        return $this;
    }

    /**
     * Returns an array of all values that result from applying an expression
     * to each document in a group of documents that share the same group by key.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/aggregation/push
     *
     * @param string $field
     * @param literal|callable|\Sokil\Mongo\Pipeline\Expression $expression Expression
     * @return \Sokil\Mongo\Pipeline\GroupStage
     */
    public function push($field, $expression)
    {
        $this->stage[$field]['$push'] = Expression::normalize($expression);

        return $this;
    }

    public function toArray()
    {
        return $this->stage;
    }
}
