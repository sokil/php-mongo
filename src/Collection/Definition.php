<?php

namespace Sokil\Mongo\Collection;

class Definition
{
    const DEFAULT_COLLECTION_CLASS  = '\Sokil\Mongo\Collection';
    const DEFAULT_GRIDFS_CLASS      = '\Sokil\Mongo\GridFS';

    const LOCK_NONE         = 'NONE';
    const LOCK_OPTIMISTIC   = 'OPTIMISTIC';
    const LOCK_PESSIMISTIC  = 'PESSIMISTIC';

    /**
     * Fully qualified collection class
     *
     * @var string
     */
    private $class;

    /**
     *
     * @var bool is collection GridFs
     */
    private $gridfs;

    /**
     * Fully qualified document class or callable that returns classname
     * @var string|callable
     */
    private $documentClass = '\Sokil\Mongo\Document';

    /**
     * @var bool Using document versioning
     */
    private $versioning = false;

    /**
     * @var array List of relations
     */
    private $relations = array();

    /**
     * @var array Index definition
     */
    private $index = array();

    /**
     * Fully qualified expression class for custom query builder
     */
    private $expressionClass = '\Sokil\Mongo\Expression';

    /**
     * List of behaviors, attached to every document
     */
    private $behaviors = array();

    /**
     * Number of documents to return in each batch of response
     */
    private $batchSize;

    /**
     * Instructs the driver to stop waiting for a response and throw a
     * MongoCursorTimeoutException after a set time
     * A timeout can be set at any time and will affect subsequent queries on
     * the cursor, including fetching more results from the database.
     * @link http://php.net/manual/en/mongocursor.timeout.php
     * @var int time in milliseconds
     */
    private $cursorClientTimeout;

    /**
     * Server-side timeout for a query
     * Specifies a cumulative time limit in milliseconds to be allowed
     * by the server for processing operations on the cursor.
     * @link http://php.net/manual/en/mongocursor.maxtimems.php
     * @var int time in milliseconds
     */
    private $cursorServerTimeout;

    private $regexp;

    private $capped;

    private $size;

    private $max;

    private $lock = self::LOCK_NONE;

    private $documentPool = true;

    private $options = array();

    public function __construct(array $definition = null)
    {
        if ($definition) {
            $this->merge($definition);
        }
    }

    public function __get($name)
    {
        return $this->getOption($name);
    }

    public function __set($name, $value)
    {
        $this->setOption($name, $value);
    }

    public function merge(array $definition)
    {
        foreach ($definition as $name => $value) {
            $this->setOption($name, $value);
        }

        return $this;
    }

    public function setOption($name, $value)
    {
        $method = 'set' . $name;
        if (method_exists($this, $method)) {
            call_user_func(array($this, $method), $value);
        } elseif (property_exists($this, $name)) {
            $this->{$name} = $value;
        } else {
            $this->options[$name] = $value;
        }


        return $this;
    }

    public function getOption($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function setClass($class)
    {
        $this->class = rtrim($class, '\\');
        return $this;
    }

    public function getClass()
    {
        if (!$this->class) {
            $this->class = $this->gridfs
                ? self::DEFAULT_GRIDFS_CLASS
                : self::DEFAULT_COLLECTION_CLASS;
        }

        return $this->class;
    }

    /**
     * Get fully qualified class name of expression for current collection instance
     *
     * @return string
     */
    public function getExpressionClass()
    {
        return $this->expressionClass;
    }

    public function getMongoCollectionOptions()
    {
        $result = array(
            'capped'    => $this->capped,
            'size'      => $this->size,
            'max' => $this->max,
        );
        if (isset($this->options['validator'])) {
            $result['validator'] = $this->options['validator'];
        }
        return $result;
    }

    public function getOptions()
    {
        return array(
            'class'                 => $this->class,
            'gridfs'                => $this->gridfs,
            'documentClass'         => $this->documentClass,
            'versioning'            => $this->versioning,
            'index'                 => $this->index,
            'relations'             => $this->relations,
            'expressionClass'       => $this->expressionClass,
            'behaviors'             => $this->behaviors,
            'batchSize'             => $this->batchSize,
            'regexp'                => $this->regexp,
            'capped'                => $this->capped,
            'size'                  => $this->size,
            'max'                   => $this->max,
            'lock'                  => $this->lock,
            'documentPool'          => $this->documentPool,
            'cursorClientTimeout'   => $this->cursorClientTimeout,
            'cursorServerTimeout'   => $this->cursorServerTimeout,
        ) + $this->options;
    }
}
