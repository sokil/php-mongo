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
        if($documents) {
            $this->documents = $documents;
        }
    }

    public function map($callable)
    {
        $result = array();

        foreach ($this->documents as $id => $document) {
            $result[$id] = $callable($document);
        }

        return new ResultSet($result);
    }

    public function filter($callable)
    {
        $result = array();

        foreach ($this->documents as $id => $document) {
            if (!$callable($document)) {
                continue;
            }

            $result[$id] = $document;
        }

        return new ResultSet($result);
    }

    public function each($callable)
    {
        foreach($this->documents as $id => $document) {
            call_user_func($callable, $document, $id, $this);
        }

        return $this;
    }

    /**
     * Count documents in result set
     * @return int count
     */
    public function count()
    {
        return count($this->documents);
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return array|\Sokil\Mongo\Document
     */
    public function current()
    {
        return current($this->documents);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->documents);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return scalar scalar on success, or <b>NULL</b> on failure.
     */
    public function key()
    {
        return key($this->documents);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function valid()
    {
        return key($this->documents) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->documents);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->documents);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return array|\Sokil\Mongo\Document
     */
    public function offsetGet($offset)
    {
        return $this->documents[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void No value is returned.
     */
    public function offsetSet($offset, $value)
    {
        $this->documents[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
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
