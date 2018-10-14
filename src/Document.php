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

use Sokil\Mongo\Document\InvalidOperationException;
use Sokil\Mongo\Document\RelationManager;
use Sokil\Mongo\Document\RevisionManager;
use Sokil\Mongo\Document\InvalidDocumentException;
use Sokil\Mongo\Collection\Definition;
use Sokil\Mongo\Document\OptimisticLockFailureException;
use Sokil\Mongo\Exception\WriteException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use GeoJson\Geometry\Geometry;

/**
 * Instance of this class is a representation of one document from collection.
 *
 * @link https://github.com/sokil/php-mongo#document-schema Document schema
 * @link https://github.com/sokil/php-mongo#create-new-document Create new document
 * @link https://github.com/sokil/php-mongo#get-and-set-data-in-document get and set data
 * @link https://github.com/sokil/php-mongo#storing-document Saving document
 * @link https://github.com/sokil/php-mongo#document-validation Validation
 * @link https://github.com/sokil/php-mongo#deleting-collections-and-documents Deleting documents
 * @link https://github.com/sokil/php-mongo#events Event handlers
 * @link https://github.com/sokil/php-mongo#behaviors Behaviors
 * @link https://github.com/sokil/php-mongo#relations Relations
 *
 * @method \Sokil\Mongo\Document onAfterConstruct(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onBeforeValidate(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onAfterValidate(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onValidateError(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onBeforeInsert(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onAfterInsert(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onBeforeUpdate(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onAfterUpdate(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onBeforeSave(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onAfterSave(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onBeforeDelete(callable $handler, int $priority = 0)
 * @method \Sokil\Mongo\Document onAfterDelete(callable $handler, int $priority = 0)
 *
 * @author Dmytro Sokil <dmytro.sokil@gmail.com>
 */
class Document extends Structure
{
    const EVENT_NAME_BEFORE_VALIDATE = 'beforeValidate';
    const EVENT_NAME_VALIDATE_ERROR = 'validateError';
    const EVENT_NAME_AFTER_VALIDATE = 'afterValidate';
    const EVENT_NAME_BEFORE_INSERT = 'beforeInsert';
    const EVENT_NAME_AFTER_INSERT = 'afterInsert';
    const EVENT_NAME_BEFORE_UPDATE = 'beforeUpdate';
    const EVENT_NAME_AFTER_UPDATE = 'afterUpdate';
    const EVENT_NAME_BEFORE_SAVE = 'beforeSave';
    const EVENT_NAME_AFTER_SAVE = 'afterSave';
    const EVENT_NAME_BEFORE_DELETE = 'beforeDelete';
    const EVENT_NAME_AFTER_DELETE = 'afterDelete';

    /**
     *
     * @var \Sokil\Mongo\Document\RelationManager
     */
    private $relationManager;

    const RELATION_HAS_ONE = 'HAS_ONE';
    const RELATION_BELONGS = 'BELONGS';
    const RELATION_HAS_MANY = 'HAS_MANY';
    const RELATION_MANY_MANY = 'MANY_MANY';

    /**
     *
     * @var \Sokil\Mongo\Document\RevisionManager
     */
    private $revisionManager;

    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher Event Dispatcher instance
     */
    private $eventDispatcher;

    /**
     * @var \Sokil\Mongo\Operator Modification operator instance
     */
    private $operator;

    /**
     *
     * @var array list of defined behaviors
     */
    private $behaviors = array();

    /**
     *
     * @var array document options
     */
    private $options;

    /**
     * @param Collection $collection instance of Mongo collection
     * @param array $data mongo document
     * @param array $options options of object initialization
     */
    public function __construct(
        Collection $collection,
        array $data = null,
        array $options = array()
    ) {
        // link to collection
        $this->collection = $collection;

        // configure document with options
        $this->options = $options;

        // init document
        $this->initDelegates();

        // initialize with data
        parent::__construct($data, $this->getOption('stored'));

        // use versioning
        if ($this->getOption('versioning')) {
            $this->getRevisionManager()->listen();
        }

        // execute after construct event handlers
        $this->triggerEvent('afterConstruct');
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    /**
     * Get instance of collection
     * @return \Sokil\Mongo\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Reset all data passed to object in run-time, like events, behaviors,
     * data modifications, etc. to the state just after open or save document
     *
     * @return \Sokil\Mongo\Document
     */
    public function reset()
    {
        // reset structure
        parent::reset();

        // reset errors
        $this->clearErrors();

        // reset behaviors
        $this->clearBehaviors();

        // init delegates
        $this->initDelegates();

        return $this;
    }

    /**
     * Reload data from db and reset all unsaved data
     */
    public function refresh()
    {
        $data = $this->collection
            ->getMongoCollection()
            ->findOne(array(
                '_id' => $this->getId()
            ));

        $this->replace($data);

        $this->operator->reset();

        return $this;
    }

    /**
     * Initialise relative classes
     */
    private function initDelegates()
    {
        // start event dispatching
        $this->eventDispatcher = new EventDispatcher;
        
        // create operator
        $this->operator = $this->getCollection()->operator();

        // attach behaviors
        $this->attachBehaviors($this->behaviors());
        if ($this->hasOption('behaviors')) {
            $this->attachBehaviors($this->getOption('behaviors'));
        }
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        // behaviors
        foreach ($this->behaviors as $behavior) {
            if (!method_exists($behavior, $name)) {
                continue;
            }

            return call_user_func_array(array($behavior, $name), $arguments);
        }

        // adding event
        if ('on' === substr($name, 0, 2)) {
            // prepend event name to function args
            $addListenerArguments = $arguments;
            array_unshift($addListenerArguments, lcfirst(substr($name, 2)));
            // add listener
            call_user_func_array(
                array($this->eventDispatcher, 'addListener'),
                $addListenerArguments
            );
            
            return $this;
        }

        // getter
        if ('get' === strtolower(substr($name, 0, 3))) {
            return $this->get(lcfirst(substr($name, 3)));
        }

        // setter
        if ('set' === strtolower(substr($name, 0, 3)) && isset($arguments[0])) {
            return $this->set(lcfirst(substr($name, 3)), $arguments[0]);
        }

        throw new Exception(sprintf('Document has no method "%s"', $name));
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function __get($name)
    {
        if ($this->getRelationManager()->isRelationExists($name)) {
            // resolve relation
            return $this->getRelationManager()->getRelated($name);
        } else {
            // get document parameter
            return parent::__get($name);
        }
    }

    /**
     * Set geo data as GeoJson object
     *
     * Requires MongoDB version 2.4 or above with 2dsparse index version 1
     * to use Point, LineString and Polygon.
     *
     * Requires MongoDB version 2.6 or above with 2dsparse index version 2
     * to use MultiPoint, MultiLineString, MultiPolygon and GeometryCollection.
     *
     * @link http://geojson.org/
     * @param string $field
     * @param \GeoJson\Geometry\Geometry $geometry
     * @return \Sokil\Mongo\Document
     */
    public function setGeometry($field, Geometry $geometry)
    {
        return $this->set($field, $geometry);
    }

    /**
     * Set point as longitude and latitude
     *
     * Requires MongoDB version 2.4 or above with 2dsparse index version 1
     * to use Point, LineString and Polygon.
     *
     * @link http://docs.mongodb.org/manual/core/2dsphere/#point
     * @param string $field
     * @param float $longitude
     * @param float $latitude
     * @return \Sokil\Mongo\Document
     */
    public function setPoint($field, $longitude, $latitude)
    {
        return $this->setGeometry(
            $field,
            new \GeoJson\Geometry\Point(array(
                $longitude,
                $latitude
            ))
        );
    }

    /**
     * Set point as longitude and latitude in legacy format
     *
     * May be used 2d index
     *
     * @link http://docs.mongodb.org/manual/core/2d/#geospatial-indexes-store-grid-coordinates
     * @param string $field
     * @param float $longitude
     * @param float $latitude
     * @return \Sokil\Mongo\Document
     */
    public function setLegacyPoint($field, $longitude, $latitude)
    {
        return $this->set(
            $field,
            array($longitude, $latitude)
        );
    }

    /**
     * Set line string as array of points
     *
     * Requires MongoDB version 2.4 or above with 2dsparse index version 1
     * to use Point, LineString and Polygon.
     *
     * @link http://docs.mongodb.org/manual/core/2dsphere/#linestring
     * @param string $field
     * @param array $pointArray array of points
     * @return \Sokil\Mongo\Document
     */
    public function setLineString($field, array $pointArray)
    {
        return $this->setGeometry(
            $field,
            new \GeoJson\Geometry\LineString($pointArray)
        );
    }

    /**
     * Set polygon as array of line rings.
     *
     * Line ring is closed line string (first and last point same).
     * Line string is array of points.
     *
     * Requires MongoDB version 2.4 or above with 2dsparse index version 1
     * to use Point, LineString and Polygon.
     *
     * @link http://docs.mongodb.org/manual/core/2dsphere/#polygon
     * @param string $field
     * @param array $lineRingsArray array of line rings
     * @return \Sokil\Mongo\Document
     */
    public function setPolygon($field, array $lineRingsArray)
    {
        return $this->setGeometry(
            $field,
            new \GeoJson\Geometry\Polygon($lineRingsArray)
        );
    }

    /**
     * Set multi point as array of points
     *
     * Requires MongoDB version 2.6 or above with 2dsparse index version 2
     * to use MultiPoint, MultiLineString, MultiPolygon and GeometryCollection.
     *
     * @link http://docs.mongodb.org/manual/core/2dsphere/#multipoint
     * @param string $field
     * @param array $pointArray array of point arrays
     * @return \Sokil\Mongo\Document
     */
    public function setMultiPoint($field, $pointArray)
    {
        return $this->setGeometry(
            $field,
            new \GeoJson\Geometry\MultiPoint($pointArray)
        );
    }

    /**
     * Set multi line string as array of line strings
     *
     * Requires MongoDB version 2.6 or above with 2dsparse index version 2
     * to use MultiPoint, MultiLineString, MultiPolygon and GeometryCollection.
     *
     * http://docs.mongodb.org/manual/core/2dsphere/#multilinestring
     * @param string $field
     * @param array $lineStringArray array of line strings
     * @return \Sokil\Mongo\Document
     */
    public function setMultiLineString($field, $lineStringArray)
    {
        return $this->setGeometry(
            $field,
            new \GeoJson\Geometry\MultiLineString($lineStringArray)
        );
    }

    /**
     * Set multy polygon as array of polygons.
     *
     * Polygon is array of line rings.
     * Line ring is closed line string (first and last point same).
     * Line string is array of points.
     *
     * Requires MongoDB version 2.6 or above with 2dsparse index version 2
     * to use MultiPoint, MultiLineString, MultiPolygon and GeometryCollection.
     *
     * @link http://docs.mongodb.org/manual/core/2dsphere/#multipolygon
     * @param string $field
     * @param array $polygonsArray array of polygons
     * @return \Sokil\Mongo\Document
     */
    public function setMultyPolygon($field, array $polygonsArray)
    {
        return $this->setGeometry(
            $field,
            new \GeoJson\Geometry\MultiPolygon($polygonsArray)
        );
    }

    /**
     * Set collection of different geometries
     *
     * Requires MongoDB version 2.6 or above with 2dsparse index version 2
     * to use MultiPoint, MultiLineString, MultiPolygon and GeometryCollection.
     *
     * @link http://docs.mongodb.org/manual/core/2dsphere/#geometrycollection
     * @param string $field
     * @param array $geometryCollection
     * @return \Sokil\Mongo\Document
     */
    public function setGeometryCollection($field, array $geometryCollection)
    {
        return $this->setGeometry(
            $field,
            new \GeoJson\Geometry\GeometryCollection($geometryCollection)
        );
    }

    /**
     * Override in child class to define relations
     * @return array relation description
     */
    protected function relations()
    {
        // [relationName => [relationType, targetCollection, reference], ...]
        return array();
    }

    /**
     * Relation definition through mapping is more prior to defined in class
     * @return array definition of relations
     */
    public function getRelationDefinition()
    {
        $relations = $this->getOption('relations');
        if (!is_array($relations)) {
            return $this->relations();
        }

        return $relations + $this->relations();
    }

    /**
     *
     * @return \Sokil\Mongo\Document\RelationManager
     */
    private function getRelationManager()
    {
        if ($this->relationManager) {
            return $this->relationManager;
        }

        $this->relationManager = new RelationManager($this);

        return $this->relationManager;
    }

    /**
     * Get related documents
     * @param string $relationName
     * @return array|\Sokil\Mongo\Document related document or array of documents
     */
    public function getRelated($relationName)
    {
        return $this->getRelationManager()->getRelated($relationName);
    }

    public function addRelation($relationName, Document $document)
    {
        $this->getRelationManager()->addRelation($relationName, $document);

        return $this;
    }

    public function removeRelation($relationName, Document $document = null)
    {
        $this->getRelationManager()->removeRelation($relationName, $document);

        return $this;
    }

    /**
     * Manually trigger defined events
     * @param string $eventName event name
     * @return \Sokil\Mongo\Event
     */
    public function triggerEvent($eventName, Event $event = null)
    {
        if (!$event) {
            $event = new Event;
        }

        $event->setTarget($this);

        return $this->eventDispatcher->dispatch($eventName, $event);
    }

    /**
     * Attach event handler
     * @param string $event event name
     * @param callable|array|string $handler event handler
     * @return \Sokil\Mongo\Document
     */
    public function attachEvent($event, $handler, $priority = 0)
    {
        $this->eventDispatcher->addListener($event, $handler, $priority);
        return $this;
    }

    /**
     * Check if event attached
     *
     * @param string $event event name
     * @return bool
     */
    public function hasEvent($event)
    {
        return $this->eventDispatcher->hasListeners($event);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->get('_id');
    }

    /**
     * Get normalized id of document.
     * If id is valid ObjectId (string consisting of exactly 24 hexadecimal characters), convert it to \MongoId.
     *
     * @param \MongoId|string|int $id document identifier
     *
     * @return \MongoId|string|int
     */
    private function normalizeDocumentId($id)
    {
        if (\MongoId::isValid($id) && !$id instanceof \MongoId) {
            return new \MongoId($id);
        } else {
            return $id;
        }
    }

    /**
     * Used to define id of stored document.
     * This id must be already present in db
     *
     * @param \MongoId|string|int $id document identifier
     *
     * @return Document
     */
    public function defineId($id)
    {
        $this->mergeUnmodified(array('_id' => $this->normalizeDocumentId($id)));
        return $this;
    }

    /**
     * Used to define id of not stored document or chane id of stored document.
     *
     * @param \MongoId|string $id id of document
     *
     * @return Document
     */
    public function setId($id)
    {
        return $this->set('_id', $this->normalizeDocumentId($id));
    }

    /**
     * Check if document is stored
     *
     * @return bool
     */
    public function isStored()
    {
        return $this->get('_id') && !$this->isModified('_id');
    }

    /**
     * Validate document
     *
     * @throws InvalidDocumentException
     * @throws Exception
     *
     * @return Document
     */
    public function validate()
    {
        if ($this->triggerEvent(self::EVENT_NAME_BEFORE_VALIDATE)->isCancelled()) {
            return $this;
        }

        if (!$this->isValid()) {
            $exception = new InvalidDocumentException('Document not valid');
            $exception->setDocument($this);

            $this->triggerEvent(self::EVENT_NAME_VALIDATE_ERROR);

            throw $exception;
        }

        $this->triggerEvent(self::EVENT_NAME_AFTER_VALIDATE);

        return $this;
    }

    public function behaviors()
    {
        return array();
    }

    public function attachBehaviors(array $behaviors)
    {
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehavior($name, $behavior);
        }

        return $this;
    }

    /**
     *
     * @param string $name unique name of attached behavior
     * @param string|array|\Sokil\Mongo\Behavior $behavior Behavior instance or behavior definition
     * @return \Sokil\Mongo\Document
     * @throws Exception
     */
    public function attachBehavior($name, $behavior)
    {
        if (is_string($behavior)) {
            // behavior defined as string
            $className = $behavior;
            $behavior = new $className();
        } elseif (is_array($behavior)) {
            // behavior defined as array
            if (empty($behavior['class'])) {
                throw new Exception('Behavior class not specified');
            }
            $className = $behavior['class'];
            unset($behavior['class']);
            $behavior = new $className($behavior);
        } elseif (!($behavior instanceof Behavior)) {
            // behavior bust be Behavior instance, but something else found
            throw new Exception('Wrong behavior specified with name ' . $name);
        }

        $behavior->setOwner($this);

        $this->behaviors[$name] = $behavior;

        return $this;
    }

    public function clearBehaviors()
    {
        $this->behaviors = array();
        return $this;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function isModificationOperatorDefined()
    {
        return $this->operator->isDefined();
    }

    /**
     * Update value in local cache and in DB
     *
     * @param string $fieldName point-delimited field name*
     * @param mixed $value value to store
     *
     * @return Document
     *
     * @throws Exception
     */
    public function set($fieldName, $value)
    {
        parent::set($fieldName, $value);

        // if document saved - save through update
        if ($this->getId()) {
            $this->operator->set($fieldName, $value);
        }

        return $this;
    }

    /**
     * Remove field
     *
     * @param string $fieldName field name
     * @return \Sokil\Mongo\Document
     */
    public function unsetField($fieldName)
    {
        if (!$this->has($fieldName)) {
            return $this;
        }

        parent::unsetField($fieldName);

        if ($this->getId()) {
            $this->operator->unsetField($fieldName);
        }

        return $this;
    }

    public function __unset($fieldName)
    {
        $this->unsetField($fieldName);
    }

    /**
     * Get reference to document
     *
     * @throws Exception
     * @return array
     */
    public function createReference()
    {
        $documentId = $this->getId();
        if (null === $documentId) {
            throw new Exception('Document must be stored to get DBRef');
        }

        return $this
            ->getCollection()
            ->getMongoCollection()
            ->createDBRef($documentId);
    }

    /**
     * Store DBRef to specified field
     *
     * @param $name
     * @param Document $document
     *
     * @return Document
     *
     * @throws Exception
     */
    public function setReference($name, Document $document)
    {
        return $this->set(
            $name,
            $document->createReference()
        );
    }

    /**
     * Get document by reference
     *
     * @param string    $name   name of field where reference stored
     * @return null|Document
     */
    public function getReferencedDocument($name)
    {
        $reference = $this->get($name);
        if (null === $reference) {
            return null;
        }

        return $this->collection
            ->getDatabase()
            ->getDocumentByReference($reference);
    }

    /**
     * Push reference to list
     *
     * @param string $name
     * @param Document $document
     *
     * @return Document
     *
     * @throws Exception
     */
    public function pushReference($name, Document $document)
    {
        return $this->push(
            $name,
            $document->createReference()
        );
    }

    /**
     * Get document by reference
     *
     * @param string    $name   name of field where reference stored
     * @return null|Document
     *
     * @throws Exception
     */
    public function getReferencedDocumentList($name)
    {
        $referenceList = $this->get($name);
        if (null === $referenceList) {
            return null;
        }

        if (!isset($referenceList[0])) {
            throw new Exception('List of references not found');
        }

        // build list of referenced collections and ids
        $documentIdList = array();
        foreach ($referenceList as $reference) {
            if (empty($reference['$ref']) || empty($reference['$id'])) {
                throw new Exception(sprintf(
                    'Invalid reference in list for document %s in field %s',
                    $this->getId(),
                    $name
                ));
            }

            $documentIdList[$reference['$ref']][] = $reference['$id'];
        }

        // get list
        $documentList = array();
        $database = $this->collection->getDatabase();
        foreach ($documentIdList as $collectionName => $documentIdList) {
            $documentList += $database->getCollection($collectionName)->find()->byIdList($documentIdList)->findAll();
        }

        return $documentList;
    }

    /**
     * @param array $data
     * @return Document
     */
    public function merge(array $data)
    {
        if ($this->isStored()) {
            foreach ($data as $fieldName => $value) {
                $this->set($fieldName, $value);
            }
        } else {
            parent::merge($data);
        }

        return $this;
    }

    /**
     * If field not exist - set value.
     * If field exists and is not array - convert to array and append
     * If field is array - append
     *
     * @param string $selector
     * @param mixed $value
     * @return Document
     */
    public function append($selector, $value)
    {
        parent::append($selector, $value);

        // if document saved - save through update
        if ($this->getId()) {
            $this->operator->set($selector, $this->get($selector));
        }

        return $this;
    }

    /**
     * Push argument as single element to field value
     *
     * @param string $fieldName
     * @param mixed $value
     * @return \Sokil\Mongo\Document
      */
    public function push($fieldName, $value)
    {
        $oldValue = $this->get($fieldName);

        // check if old value is list or sub document
        // on sub document throw exception
        if (is_array($oldValue)) {
            $isSubDocument = (array_keys($oldValue) !== range(0, count($oldValue) - 1));
            if ($isSubDocument) {
                throw new InvalidOperationException(sprintf(
                    'The field "%s" must be an array but is of type Object',
                    $fieldName
                ));
            }
        }

        // prepare new value
        $value = Structure::prepareToStore($value);

        // field not exists
        if (!$oldValue) {
            if ($this->getId()) {
                $this->operator->push($fieldName, $value);
            }
            $value = array($value);
        } // field already exist and has single value
        elseif (!is_array($oldValue)) {
            $value = array_merge((array) $oldValue, array($value));
            if ($this->getId()) {
                $this->operator->set($fieldName, $value);
            }
        } // field exists and is array
        else {
            if ($this->getId()) {
                $setValue = $this->operator->get('$set', $fieldName);
                if ($setValue) {
                    $setValue[] = $value;
                    $this->operator->set($fieldName, $setValue);
                } else {
                    $this->operator->push($fieldName, $value);
                }
            }
            $value = array_merge($oldValue, array($value));
        }

        // update local data
        parent::set($fieldName, $value);

        return $this;
    }

    /**
     * Push each element of argument's array as single element to field value
     *
     * @param string $fieldName
     * @param array $values
     * @return \Sokil\Mongo\Document
     */
    public function pushEach($fieldName, array $values)
    {
        $oldValue = $this->get($fieldName);

        if ($this->getId()) {
            if (!$oldValue) {
                $this->operator->pushEach($fieldName, $values);
            } elseif (!is_array($oldValue)) {
                $values = array_merge((array) $oldValue, $values);
                $this->operator->set($fieldName, $values);
            } else {
                $this->operator->pushEach($fieldName, $values);
                $values = array_merge($oldValue, $values);
            }
        } else {
            if ($oldValue) {
                $values = array_merge((array) $oldValue, $values);
            }
        }

        // update local data
        parent::set($fieldName, $values);

        return $this;
    }

    public function addToSet($fieldName, $value)
    {
        $set = $this->get($fieldName);

        // prepare new value
        $value = Structure::prepareToStore($value);

        // add to set
        if (empty($set)) {
            $updatedSet = array($value);
            if ($this->getId()) {
                $this->operator->addToSet($fieldName, $value);
            }
        } elseif (!is_array($set)) {
            if ($set === $value) {
                return $this;
            }
            $updatedSet = array($set, $value);
            if ($this->getId()) {
                $this->operator->set($fieldName, $updatedSet);
            }
        } elseif (array_keys($set) !== range(0, count($set) - 1)) {
            // check if old value is list or sub document
            // on sub document throw exception
            throw new InvalidOperationException(sprintf(
                'The field "%s" must be an array but is of type Object',
                $fieldName
            ));
        } else {
            // check if already in set
            if (in_array($value, $set, true)) {
                return $this;
            }
            $updatedSet = array_merge($set, array($value));
            if ($this->getId()) {
                $setValue = $this->operator->get('$set', $fieldName);
                if ($setValue) {
                    $this->operator->set($fieldName, $updatedSet);
                } else {
                    $this->operator->addToSet($fieldName, $value);
                }
            }
        }

        parent::set($fieldName, $updatedSet);

        return $this;
    }

    public function addToSetEach($fieldName, array $values)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Removes from an existing array all instances of a value or
     * values that match a specified query
     *
     * @param integer|string|array|\Sokil\Mongo\Expression|callable $expression
     * @param mixed|\Sokil\Mongo\Expression|callable $value
     * @return \Sokil\Mongo\Document
     */
    public function pull($expression, $value = null)
    {
        $this->operator->pull($expression, $value);
        return $this;
    }

    /**
     * @param string $fieldName
     * @param int $value
     *
     * @return self
     *
     * @throws Exception
     */
    public function increment($fieldName, $value = 1)
    {
        parent::set($fieldName, (int) $this->get($fieldName) + $value);

        if ($this->getId()) {
            $this->operator->increment($fieldName, $value);
        }


        return $this;
    }

    public function decrement($fieldName, $value = 1)
    {
        return $this->increment($fieldName, -1 * $value);
    }

    public function bitwiceAnd($field, $value)
    {
        parent::set($field, (int) $this->get($field) & $value);

        if ($this->getId()) {
            $this->operator->bitwiceAnd($field, $value);
        }

        return $this;
    }

    public function bitwiceOr($field, $value)
    {
        parent::set($field, (int) $this->get($field) | $value);

        if ($this->getId()) {
            $this->operator->bitwiceOr($field, $value);
        }

        return $this;
    }

    public function bitwiceXor($field, $value)
    {
        $oldFieldValue = (int) $this->get($field);
        $newValue = $oldFieldValue ^ $value;

        parent::set($field, $newValue);

        if ($this->getId()) {
            if (version_compare($this->getCollection()->getDatabase()->getClient()->getDbVersion(), '2.6', '>=')) {
                $this->operator->bitwiceXor($field, $value);
            } else {
                $this->operator->set($field, $newValue);
            }
        }

        return $this;
    }

    /**
     * Internal method to insert document
     */
    private function internalInsert()
    {
        if ($this->triggerEvent(self::EVENT_NAME_BEFORE_INSERT)->isCancelled()) {
            return;
        }

        $document = $this->toArray();

        // try write document
        try {
            $this
                ->collection
                ->getMongoCollection()
                ->insert($document);
        } catch (\Exception $e) {
            throw new WriteException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        // set id
        $this->defineId($document['_id']);

        // after insert event
        $this->triggerEvent(self::EVENT_NAME_AFTER_INSERT);
    }

    /**
     * Internal method to update document
     *
     * @throws WriteException
     * @throws OptimisticLockFailureException
     */
    private function internalUpdate()
    {
        if ($this->triggerEvent(self::EVENT_NAME_BEFORE_UPDATE)->isCancelled()) {
            return;
        }

        // locking
        $query = array('_id' => $this->getId());
        if ($this->getOption('lock') === Definition::LOCK_OPTIMISTIC) {
            $query['__version__'] = $this->get('__version__');
            $this->getOperator()->increment('__version__');
        }

        // update
        try {
            $status = $this
                ->collection
                ->getMongoCollection()
                ->update(
                    $query,
                    $this->getOperator()->toArray()
                );
        } catch (\Exception $e) {
            throw new WriteException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        // check update status
        if ($status['ok'] != 1) {
            throw new WriteException(
                sprintf(
                    'Update error: %s: %s',
                    $status['err'],
                    $status['errmsg']
                )
            );
        }

        // check if document modified due to specified lock
        if ($this->getOption('lock') === Definition::LOCK_OPTIMISTIC) {
            if ($status['n'] === 0) {
                throw new OptimisticLockFailureException;
            }
        }

        if ($this->getOperator()->isReloadRequired()) {
            $this->refresh();
        } else {
            $this->getOperator()->reset();
        }

        $this->triggerEvent(self::EVENT_NAME_AFTER_UPDATE);
    }

    /**
     * Save document
     *
     * @param bool $validate
     *
     * @return Document
     *
     * @throws WriteException
     * @throws Exception
     */
    public function save($validate = true)
    {
        // if document already in db and not modified - skip this method
        if (!$this->isSaveRequired()) {
            return $this;
        }

        if ($validate) {
            $this->validate();
        }

        // handle beforeSave event
        if ($this->triggerEvent(self::EVENT_NAME_BEFORE_SAVE)->isCancelled()) {
            return $this;
        }

        // write document
        if ($this->isStored()) {
            $this->internalUpdate();
        } else {
            $this->internalInsert();
        }

        // handle afterSave event
        $this->triggerEvent(self::EVENT_NAME_AFTER_SAVE);

        // set document unmodified
        $this->apply();
        
        return $this;
    }

    /**
     * Check if document require save
     *
     * @return bool
     */
    public function isSaveRequired()
    {
        return !$this->isStored() || $this->isModified() || $this->isModificationOperatorDefined();
    }

    /**
     * Delete document
     *
     * @return Document
     *
     * @throws Exception
     */
    public function delete()
    {
        if ($this->triggerEvent(self::EVENT_NAME_BEFORE_DELETE)->isCancelled()) {
            return $this;
        }

        $status = $this->collection->getMongoCollection()->remove(array(
            '_id'   => $this->getId(),
        ));

        if (true !== $status && $status['ok'] != 1) {
            throw new Exception(sprintf('Delete document error: %s', $status['err']));
        }

        $this->triggerEvent(self::EVENT_NAME_AFTER_DELETE);

        // drop from document's pool
        $this->getCollection()->removeDocumentFromDocumentPool($this);

        return $this;
    }

    /**
     * Get revisions manager
     *
     * @return \Sokil\Mongo\Document\RevisionManager
     */
    public function getRevisionManager()
    {
        if (!$this->revisionManager) {
            $this->revisionManager = new RevisionManager($this);
        }

        return $this->revisionManager;
    }

    /**
     * @deprecated since 1.13.0 use self::getRevisionManager()->getRevisions()
     */
    public function getRevisions($limit = null, $offset = null)
    {
        return $this->getRevisionManager()->getRevisions($limit, $offset);
    }

    /**
     * @deprecated since 1.13.0 use self::getRevisionManager()->getRevision()
     */
    public function getRevision($id)
    {
        return $this->getRevisionManager()->getRevision($id);
    }

    /**
     * @deprecated since 1.13.0 use self::getRevisionManager()->getRevisionsCount()
     */
    public function getRevisionsCount()
    {
        return $this->getRevisionManager()->getRevisionsCount();
    }

    /**
     * @deprecated since 1.13.0 use self::getRevisionManager()->clearRevisions()
     */
    public function clearRevisions()
    {
        $this->getRevisionManager()->clearRevisions();
        return $this;
    }
}
