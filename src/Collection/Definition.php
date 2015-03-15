<?php

namespace Sokil\Mongo\Collection;

class Definition
{
    const DEFAULT_COLLECTION_CLASS = '\Sokil\Mongo\Collection';
    const DEFAULT_GRIDFS_CLASS = '\Sokil\Mongo\GridFS';

    /**
     * Fully qualified collectin class
     */
    private $class = self::DEFAULT_COLLECTION_CLASS;

    /**
     * Fully qualified document class
     */
    private $documentClass = '\Sokil\Mongo\Document';

    /**
     * Using document versioning
     */
    private $versioning	= false;

    /**
     * Index definition
     */
    private $index = null;

    /**
     * Fully qualified expression class for custom query builder
     */
    private $expressionClass = '\Sokil\Mongo\Expression';

    /**
     * List of behaviors, attached to every document
     */
    private $behaviors = null;

    /**
     * Number of documents to return in each batch of response
     */
    private $batchSize = null;

    public function __construct(array $definition = null)
    {
        if($definition) {
            $this->merge($definition);
        }
    }

    public function merge(array $definition)
    {
        foreach($definition as $name => $value) {
            $this->{$name} = $value;
        }

        return $this;
    }
}