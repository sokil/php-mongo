<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo;

class ResultSet implements \Iterator, \Countable, \ArrayAccess
{
    protected $documents = array();

    public function __construct(array $documents = null)
    {
        if ($documents) {
            $this->documents = $documents;
        }
    }

    public function map($callable)
    {
        return new ResultSet(array_map($callable, $this->documents));
    }

    public function filter($callable)
    {
        return new ResultSet(array_filter($this->documents, $callable));
    }

    public function each($callable)
    {
        foreach ($this->documents as $id => $document) {
            call_user_func($callable, $document, $id, $this);
        }

        return $this;
    }

    /**
     *
     * @param callable $callable apply arguments [$accumulator, $document]
     * @param mixed $initial
     * @return mixed
     */
    public function reduce($callable, $initial = null)
    {
        return array_reduce($this->documents, $callable, $initial);
    }

    /**
     * Count documents in result set
     * @return int count
     */
    public function count()
    {
        return count($this->documents);
    }

    public function keys()
    {
        return array_keys($this->documents);
    }

    public function values()
    {
        return array_values($this->documents);
    }

    /**
     * Return the current element
     * @return array|\Sokil\Mongo\Document
     */
    public function current()
    {
        return current($this->documents);
    }

    /**
     * Move forward to next element
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->documents);
    }

    /**
     * Return the key of the current element
     * @return scalar scalar on success, or NULL on failure.
     */
    public function key()
    {
        return key($this->documents);
    }

    /**
     * Checks if current position is valid
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns TRUE on success or FALSE on failure.
     */
    public function valid()
    {
        return key($this->documents) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->documents);
    }

    /**
     * Whether a offset exists
     * @param mixed $offset
     * An offset to check for.
     * @return boolean TRUE on success or FALSE on failure.
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->documents);
    }

    /**
     * Offset to retrieve
     * @param mixed $offset The offset to retrieve.
     * @return array|\Sokil\Mongo\Document
     */
    public function offsetGet($offset)
    {
        return $this->documents[$offset];
    }

    /**
     * Offset to set
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @return void No value is returned.
     */
    public function offsetSet($offset, $value)
    {
        $this->documents[$offset] = $value;
    }

    /**
     * Offset to unset
     * @param mixed $offset The offset to unset.
     * @return void No value is returned.
     */
    public function offsetUnset($offset)
    {
        unset($this->documents[$offset]);
    }

    public function toArray()
    {
        return $this->documents;
    }
}
