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

use Sokil\Mongo\Document\RelationManager;
use Sokil\Mongo\Document\RevisionManager;
use Sokil\Mongo\Document\InvalidDocumentException;
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
    const FIELD_TYPE_DOUBLE = 1;
    const FIELD_TYPE_STRING = 2;
    const FIELD_TYPE_OBJECT = 3;
    const FIELD_TYPE_ARRAY = 4;
    const FIELD_TYPE_BINARY_DATA = 5;
    const FIELD_TYPE_UNDEFINED = 6; // deprecated
    const FIELD_TYPE_OBJECT_ID = 7;
    const FIELD_TYPE_BOOLEAN = 8;
    const FIELD_TYPE_DATE = 9;
    const FIELD_TYPE_NULL = 10;
    const FIELD_TYPE_REGULAR_EXPRESSION = 11;
    const FIELD_TYPE_JAVASCRIPT = 13;
    const FIELD_TYPE_SYMBOL = 14;
    const FIELD_TYPE_JAVASCRIPT_WITH_SCOPE = 15;
    const FIELD_TYPE_INT32 = 16;
    const FIELD_TYPE_TIMESTAMP = 17;
    const FIELD_TYPE_INT64 = 18;
    const FIELD_TYPE_MIN_KEY = 255;
    const FIELD_TYPE_MAX_KEY = 127;

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
    
    private $saveStrategyName = 'Common';
    
    /**
     *
     * @var \Sokil\Mongo\Document\SaveStrategy
     */
    private $saveStrategy;

    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    /**
     * Name of scenario, used for validating fields
     * @var string
     */
    private $scenario;

    /**
     * @var array validator errors
     */
    private $errors = array();

    /**
     * @var array manually added validator errors
     */
    private $triggeredErrors = array();

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
     * @var array list of namespaces
     */
    private $validatorNamespaces = array(
        '\Sokil\Mongo\Validator',
    );

    /**
     *
     * @var array document options
     */
    private $options;

    /**
     * @param \Sokil\Mongo\Collection $collection instance of Mongo collection
     * @param array $data mongo document
     * @param array $options options of object initialization
     */
    public function __construct(Collection $collection, array $data = null, array $options = array())
    {
        $this->collection = $collection;

        // configure document with options
        $this->options = $options;

        // init document
        $this->initDocument();

        // execute before construct callable
        $this->beforeConstruct();

        // set data
        if ($data) {
            if ($this->getOption('stored')) {
                // load stored
                $this->replace($data);
            } else {
                // create unstored
                $this->merge($data);
            }
        }

        // use versioning
        if($this->getOption('versioning')) {
            $this->getRevisionManager()->listen();
        }

        // execure after construct event handlers
        $this->eventDispatcher->dispatch('afterConstruct');
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
     * Add own namespace of validators
     *
     * @param type $namespace
     * @return \Sokil\Mongo\Document
     */
    public function addValidatorNamespace($namespace)
    {
        $this->validatorNamespaces[] = rtrim($namespace, '\\');
        return $this;
    }

    /**
     * Event handler, called before running constructor.
     * May be overridden in child classes
     */
    public function beforeConstruct()
    {

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
        $this->errors = array();
        $this->triggeredErrors = array();

        // reset behaviors
        $this->clearBehaviors();

        // init delegates
        $this->initDocument();

        return $this;
    }

    /**
     * Reload data from db and reset all unsaved data
     */
    public function refresh()
    {
        // get data omitting cache
        $data = $this
            ->getCollection()
            ->getMongoCollection()
            ->findOne(array(
                '_id' => $this->getId(),
            ));

        $this->replace($data);

        $this->operator = $this->getCollection()->operator();

        return $this;
    }

    /**
     * Initialise relative classes
     */
    private function initDocument()
    {
        // start event dispatching
        $this->eventDispatcher = new EventDispatcher;
        
        // create operator
        $this->operator = $this->getCollection()->operator();

        // attacj behaviors
        $this->attachBehaviors($this->behaviors());
        if($this->hasOption('behaviors')) {
            $this->attachBehaviors($this->getOption('behaviors'));
        }
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

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
        if('on' === substr($name, 0, 2)) {
            // prepent ebent name to function args
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

        throw new Exception('Document has no method "' . $name . '"');
    }

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
     * Check if document belongs to specified collection
     *
     * @deprecated since 1.12.8 Use Collection::hasDocument()
     * @param \Sokil\Mongo\Collection $collection collection instance
     * @return boolean
     */
    public function belongsToCollection(Collection $collection)
    {
        return $collection->hasDocument($this);
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
        if(!is_array($relations)) {
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
        if($this->relationManager) {
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
        if(!$event) {
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

    public function getId()
    {
        return $this->get('_id');
    }

    /**
     * Used to define id of stored document. This id must be already present in db
     *
     * @param \MongoId|string $id id of document
     * @return \Sokil\Mongo\Document
     */
    public function defineId($id)
    {

        if ($id instanceof \MongoId) {
            $this->_data['_id'] = $id;
            return $this;
        }

        try {
            $this->_data['_id'] = new \MongoId($id);
        } catch (\MongoException $e) {
            $this->_data['_id'] = $id;
        }

        return $this;
    }

    /*
     * Used to define id of unstored document. This db is manual
     */

    public function setId($id)
    {

        if ($id instanceof \MongoId) {
            return $this->set('_id', $id);
        }

        try {
            return $this->set('_id', new \MongoId($id));
        } catch (\MongoException $e) {
            return $this->set('_id', $id);
        }
    }

    public function isStored()
    {
        return $this->get('_id') && !$this->isModified('_id');
    }

    public function setScenario($scenario)
    {
        $this->scenario = $scenario;
        return $this;
    }

    public function getScenario()
    {
        return $this->scenario;
    }

    public function setNoScenario()
    {
        $this->scenario = null;
        return $this;
    }

    public function isScenario($scenario)
    {
        return $scenario === $this->scenario;
    }

    public function rules()
    {
        return array();
    }

    private function getValidatorClassNameByRuleName($ruleName)
    {
        if(false !== strpos($ruleName, '_')) {
            $className = implode('', array_map('ucfirst', explode('_', strtolower($ruleName))));
        } else {
            $className = ucfirst(strtolower($ruleName));
        }

        foreach ($this->validatorNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className . 'Validator';
            if (class_exists($fullyQualifiedClassName)) {
                return $fullyQualifiedClassName;
            }
        }

        throw new Exception('Validator with name ' . $ruleName . ' not found');
    }

    /**
     * check if filled model params is valid
     * @return boolean
     */
    public function isValid()
    {
        $this->errors = array();

        foreach ($this->rules() as $rule) {
            $fields = array_map('trim', explode(',', $rule[0]));
            $ruleName = $rule[1];
            $params = array_slice($rule, 2);

            // check scenario
            if (!empty($rule['on'])) {
                $onScenarios = explode(',', $rule['on']);
                if (!in_array($this->getScenario(), $onScenarios)) {
                    continue;
                }
            }

            if (!empty($rule['except'])) {
                $exceptScenarios = explode(',', $rule['except']);
                if (in_array($this->getScenario(), $exceptScenarios)) {
                    continue;
                }
            }

            if (method_exists($this, $ruleName)) {
                // method
                foreach ($fields as $field) {
                    $this->{$ruleName}($field, $params);
                }
            } else {
                // validator class
                $validatorClassName = $this->getValidatorClassNameByRuleName($ruleName);

                /* @var $validator \Sokil\Mongo\Validator */
                $validator = new $validatorClassName;
                if (!$validator instanceof \Sokil\Mongo\Validator) {
                    throw new Exception('Validator class must implement \Sokil\Mongo\Validator class');
                }

                $validator->validate($this, $fields, $params);
            }
        }

        return !$this->hasErrors();
    }

    /**
     *
     * @throws \Sokil\Mongo\Document\InvalidDocumentException
     * @return \Sokil\Mongo\Document
     */
    public function validate()
    {
        if($this->triggerEvent('beforeValidate')->isCancelled()) {
            return $this;
        }

        if (!$this->isValid()) {
            $exception = new InvalidDocumentException('Document not valid');
            $exception->setDocument($this);

            $this->triggerEvent('validateError');

            throw $exception;
        }

        $this->triggerEvent('afterValidate');

        return $this;
    }

    public function hasErrors()
    {
        return ($this->errors || $this->triggeredErrors);
    }

    /**
     * get list of validation errors
     *
     * Format: $errors['fieldName']['rule'] = 'message';
     *
     * @return array list of validation errors
     */
    public function getErrors()
    {
        return array_merge_recursive($this->errors, $this->triggeredErrors);
    }

    /**
     * Add validator error from validator classes and methods. This error
     * reset on every revalidation
     *
     * @param string $fieldName dot-notated field name
     * @param string $ruleName name of validation rule
     * @param string $message error message
     * @return \Sokil\Mongo\Document
     */
    public function addError($fieldName, $ruleName, $message)
    {
        $this->errors[$fieldName][$ruleName] = $message;

        // Deprecated. Related to bug when suffix not removed from class.
        // Added for back compatibility and will be removed in next versions
        $this->errors[$fieldName][$ruleName . 'validator'] = $message;
        
        return $this;
    }

    /**
     * Add errors
     *
     * @param array $errors
     * @return \Sokil\Mongo\Document
     */
    public function addErrors(array $errors)
    {
        $this->errors = array_merge_recursive($this->errors, $errors);
        return $this;
    }

    /**
     * Add custom error which not reset after validation
     *
     * @param type $fieldName
     * @param type $ruleName
     * @param type $message
     * @return \Sokil\Mongo\Document
     */
    public function triggerError($fieldName, $ruleName, $message)
    {
        $this->triggeredErrors[$fieldName][$ruleName] = $message;
        return $this;
    }

    /**
     * Add custom errors
     *
     * @param array $errors
     * @return \Sokil\Mongo\Document
     */
    public function triggerErrors(array $errors)
    {
        $this->triggeredErrors = array_merge_recursive($this->triggeredErrors, $errors);
        return $this;
    }

    /**
     * Remove custom errors
     *
     * @return \Sokil\Mongo\Document
     */
    public function clearTriggeredErrors()
    {
        $this->triggeredErrors = array();
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
        if(is_string($behavior)) {
            // behavior defined as string
            $className = $behavior;
            $behavior = new $className();
        } elseif(is_array($behavior)) {
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
     * @param string $fieldName point-delimited field name
     * @param mixed $value value to store
     * @return \Sokil\Mongo\Document
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
     * If field -s array - append
     *
     * @param type $selector
     * @param type $value
     * @return \Sokil\Mongo\Structure
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

        if ($value instanceof Structure) {
            $value = $value->toArray();
        }

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
                // check if array because previous $set operation on single value was executed
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
            if(version_compare($this->getCollection()->getDatabase()->getClient()->getDbVersion(), '2.6', '>=')) {
                $this->operator->bitwiceXor($field, $value);
            } else {
                $this->operator->set($field, $newValue);
            }
        }

        return $this;
    }
    
    public function save($validate = true)
    {
        // create save strategy
        if(!$this->saveStrategy) {
            $strategyClassName = '\Sokil\Mongo\Document\SaveStrategy\\' . $this->saveStrategyName;
            if(!class_exists($strategyClassName)) {
                throw new Exception('Wrong strategy specified');
            }
            $this->saveStrategy = new $strategyClassName($this);
        }
        
        // save document
        $this->saveStrategy->save($validate);
        
        return $this;
    }

    public function isSaveRequired()
    {
        return !$this->isStored() || $this->isModified() || $this->isModificationOperatorDefined();
    }

    public function delete()
    {
        $this->collection->deleteDocument($this);
    }

    /**
     *
     * @return \Sokil\Mongo\RevisionManager
     */
    public function getRevisionManager()
    {
        if(!$this->revisionManager) {
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
