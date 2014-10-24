<?php

namespace Sokil\Mongo;

use \Symfony\Component\EventDispatcher\EventDispatcher;

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
    
    const RELATION_HAS_ONE = 'HAS_ONE';
    const RELATION_BELONGS = 'BELONGS';
    const RELATION_HAS_MANY = 'HAS_MANY';
    const RELATION_MANY_MANY = 'MANY_MANY';

    private $resolvedRelationIds = array();

    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $_collection;
    protected $_scenario;

    /**
     * @var array validator errors
     */
    private $_errors = array();

    /**
     * @var array manually added validator errors
     */
    private $_triggeredErrors = array();

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher Event Dispatcher instance
     */
    private $_eventDispatcher;

    /**
     * @var \Sokil\Mongo\Operator Modification operator instance
     */
    private $_operator;

    /**
     *
     * @var array list of defined behaviors
     */
    private $_behaviors = array();
    private $_validatorNamespaces = array(
        '\Sokil\Mongo\Validator',
    );

    /**
     * @param \Sokil\Mongo\Collection $collection instance of Mongo collection
     * @param array $data mongo document
     * @param array $options options of object initialization
     */
    public function __construct(Collection $collection, array $data = null, array $options = array())
    {
        $this->_collection = $collection;

        $this->_init();

        $this->beforeConstruct();

        if (isset($options['stored']) && $options['stored'] === true) {
            // load stored
            if ($data) {
                $this->mergeUnmodified($data);
            }
        } else {
            // create unstored
            if ($data) {
                $this->merge($data);
            }
        }

        $this->_eventDispatcher->dispatch('afterConstruct');
    }
    
    /**
     * Add own namespace of validators
     * 
     * @param type $namespace
     * @return \Sokil\Mongo\Document
     */
    public function addValidatorNamespace($namespace)
    {
        $this->_validatorNamespaces[] = rtrim($namespace, '\\');
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
        return $this->_collection;
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
        $this->_errors = array();
        $this->_triggeredErrors = array();

        // reset behaviors
        $this->clearBehaviors();

        // init delegates
        $this->_init();

        return $this;
    }
    
    /**
     * Reload data from db and reset all unsaved data
     */
    public function refresh()
    {        
        $data = $this
            ->getCollection()
            ->find()
            ->asArray()
            ->byId($this->getId())
            ->findOne();
        
        $this->_data = $data;
        
        $this->_originalData = $data;
        
        $this->_modifiedFields = array();
        
        $this->_operator = new Operator;
        
        return $this;
    }

    /**
     * Initialise relative classes
     */
    private function _init()
    {
        $this->_eventDispatcher = new EventDispatcher;
        $this->_operator = new Operator;

        $this->attachBehaviors($this->behaviors());
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    public function __call($name, $arguments)
    {

        // behaviors
        foreach ($this->_behaviors as $behavior) {
            if (!method_exists($behavior, $name)) {
                continue;
            }

            return call_user_func_array(array($behavior, $name), $arguments);
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
        if ($this->_isRelationExists($name)) {
            // resolve relation
            return $this->getRelated($name);
        } else {
            // get document parameter
            return parent::__get($name);
        }
    }

    public function belongsToCollection(Collection $collection)
    {
        // check connection
        if($collection->getDatabase()->getClient()->getDsn() !== $this->_collection->getDatabase()->getClient()->getDsn()) {
            return false;
        }
        
        // check database
        if ($collection->getDatabase()->getName() !== $this->_collection->getDatabase()->getName()) {
            return false;
        }

        // check collection
        return $collection->getName() == $this->_collection->getName();
    }

    /**
     * @return array relation description
     */
    public function relations()
    {
        // [relationName => [relationType, targetCollection, reference], ...]
        return array();
    }

    /**
     * Check if relation with specified name configured
     * @param string $name
     * @return boolean
     */
    private function _isRelationExists($name)
    {
        $relations = $this->relations();

        return isset($relations[$name]);
    }

    /**
     * Get related documents
     * @param string $relationName name of relation
     */
    public function getRelated($relationName)
    {
        // get relation config
        $relations = $this->relations();
        
        // check if relation exists
        if (!isset($relations[$relationName])) {
            throw new Exception('Relation with name "' . $relationName . '" not found');
        }
        
        // get relation metadata
        $relation = $relations[$relationName];

        $relationType = $relation[0];
        $targetCollectionName = $relation[1];
        
        // get target collection
        $targetCollection = $this->_collection
            ->getDatabase()
            ->getCollection($targetCollectionName);
        
        // check if relation already resolved
        if (isset($this->resolvedRelationIds[$relationName])) {
            if(is_array($this->resolvedRelationIds[$relationName])) {
                // has_many, many_many
                return $targetCollection->getDocumentsFromDocumentPool($this->resolvedRelationIds[$relationName]);
            } else {
                //has_one, belongs
                return $targetCollection->getDocumentFromDocumentPool($this->resolvedRelationIds[$relationName]);
            }
        }

        switch ($relationType) {
            
            default:
                throw new Exception('Unsupported relation type "' . $relationType . '" when resolve relation "' . $relationName . '"');
                
            case self::RELATION_HAS_ONE:
                $internalField = '_id';
                $externalField = $relation[2];

                $document = $targetCollection
                    ->find()
                    ->where($externalField, $this->get($internalField))
                    ->findOne();

                $this->resolvedRelationIds[$relationName] = (string) $document->getId();
                
                return $document;

            case self::RELATION_BELONGS:
                $internalField = $relation[2];

                $document = $targetCollection->getDocument($this->get($internalField));

                $this->resolvedRelationIds[$relationName] = (string) $document->getId();
                    
                return $document;

            case self::RELATION_HAS_MANY:
                $internalField = '_id';
                $externalField = $relation[2];

                $documents = $targetCollection
                    ->find()
                    ->where($externalField, $this->get($internalField))
                    ->findAll();
                
                foreach($documents as $document) {
                    $this->resolvedRelationIds[$relationName][] = (string) $document->getId();
                }

                return $documents;

            case self::RELATION_MANY_MANY:
                $isRelationListStoredInternally = isset($relation[3]) && $relation[3];
                if ($isRelationListStoredInternally) {
                    // relation list stored in this document
                    $internalField = $relation[2];
                    $relatedIdList = $this->get($internalField);
                    if (!$relatedIdList) {
                        return array();
                    }

                    $externalField = '_id';

                    $documents = $targetCollection
                        ->find()
                        ->whereIn($externalField, $relatedIdList)
                        ->findAll();
                    
                } else {
                    // relation list stored in external document
                    $internalField = '_id';
                    $externalField = $relation[2];

                    $documents = $targetCollection
                        ->find()
                        ->where($externalField, $this->get($internalField))
                        ->findAll();
                }
                
                foreach($documents as $document) {
                    $this->resolvedRelationIds[$relationName][] = (string) $document->getId();
                }

                return $documents;
        }
    }

    public function addRelation($relationName, Document $document)
    {
        if (!$this->_isRelationExists($relationName)) {
            throw new \Exception('Relation "' . $relationName . '" not configured');
        }

        $relations = $this->relations();
        $relation = $relations[$relationName];

        list($relationType, $relatedCollectionName, $field) = $relation;

        $relatedCollection = $this
            ->getCollection()
            ->getDatabase()
            ->getCollection($relatedCollectionName);

        if (!$relatedCollection->hasDocument($document)) {
            throw new Exception('Document must belongs to related collection');
        }

        switch ($relationType) {

            case self::RELATION_BELONGS:
                if (!$document->isStored()) {
                    throw new Exception('Document ' . get_class($document) . ' must be saved before adding relation');
                }
                $this->set($field, $document->getId());
                break;

            case self::RELATION_HAS_ONE;
                if (!$this->isStored()) {
                    throw new Exception('Document ' . get_class($this) . ' must be saved before adding relation');
                }
                $document->set($field, $this->getId())->save();
                break;

            case self::RELATION_HAS_MANY:
                if (!$this->isStored()) {
                    throw new Exception('Document ' . get_class($this) . ' must be saved before adding relation');
                }
                $document->set($field, $this->getId())->save();
                break;

            case self::RELATION_MANY_MANY:
                $isRelationListStoredInternally = isset($relation[3]) && $relation[3];
                if ($isRelationListStoredInternally) {
                    $this->push($field, $document->getId())->save();
                } else {
                    $document->push($field, $this->getId())->save();
                }
                break;

            default:
                throw new Exception('Unsupported relation type "' . $relationType . '" when resolve relation "' . $relationName . '"');
        }

        return $this;
    }

    public function removeRelation($relationName, Document $document = null)
    {
        if (!$this->_isRelationExists($relationName)) {
            throw new \Exception('Relation ' . $relationName . ' not configured');
        }

        $relations = $this->relations();
        $relation = $relations[$relationName];

        list($relationType, $relatedCollectionName, $field) = $relation;

        $relatedCollection = $this
            ->getCollection()
            ->getDatabase()
            ->getCollection($relatedCollectionName);

        if ($document && !$relatedCollection->hasDocument($document)) {
            throw new Exception('Document must belongs to related collection');
        }

        switch ($relationType) {

            case self::RELATION_BELONGS:
                $this->unsetField($field)->save();
                break;

            case self::RELATION_HAS_ONE;
                $document = $this->getRelated($relationName);
                if (!$document) {
                    // relation not exists
                    return $this;
                }
                $document->unsetField($field)->save();
                break;

            case self::RELATION_HAS_MANY:
                if (!$document) {
                    throw new Exception('Related document must be defined');
                }
                $document->unsetField($field)->save();
                break;


            case self::RELATION_MANY_MANY:
                if (!$document) {
                    throw new Exception('Related document must be defined');
                }
                $isRelationListStoredInternally = isset($relation[3]) && $relation[3];
                if ($isRelationListStoredInternally) {
                    $this->pull($field, $document->getId())->save();
                } else {
                    $document->pull($field, $this->getId())->save();
                }
                break;

            default:
                throw new Exception('Unsupported relation type "' . $relationType . '" when resolve relation "' . $relationName . '"');
        }

        return $this;
    }

    /**
     * Manually trigger defined events
     * @param string $eventName event name
     * @return \Sokil\Mongo\Event
     */
    public function triggerEvent($eventName, \Sokil\Mongo\Event $event = null)
    {
        if(!$event) {
            $event = new Event;
        }
        
        $event->setTarget($this);

        return $this->_eventDispatcher->dispatch($eventName, $event);
    }

    /**
     * Attach event handler
     * @param string $event event name
     * @param callable|array|string $handler event handler
     * @return \Sokil\Mongo\Document
     */
    public function attachEvent($event, $handler)
    {
        $this->_eventDispatcher->addListener($event, $handler);
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
        return $this->_eventDispatcher->hasListeners($event);
    }

    public function onAfterConstruct($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('afterConstruct', $handler, $priority);
        return $this;
    }

    public function onBeforeValidate($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('beforeValidate', $handler, $priority);
        return $this;
    }

    public function onAfterValidate($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('afterValidate', $handler, $priority);
        return $this;
    }

    public function onValidateError($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('validateError', $handler, $priority);
        return $this;
    }

    public function onBeforeInsert($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('beforeInsert', $handler, $priority);
        return $this;
    }

    public function onAfterInsert($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('afterInsert', $handler, $priority);
        return $this;
    }

    public function onBeforeUpdate($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('beforeUpdate', $handler, $priority);
        return $this;
    }

    public function onAfterUpdate($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('afterUpdate', $handler, $priority);
        return $this;
    }

    public function onBeforeSave($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('beforeSave', $handler, $priority);
        return $this;
    }

    public function onAfterSave($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('afterSave', $handler, $priority);
        return $this;
    }

    public function onBeforeDelete($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('beforeDelete', $handler, $priority);
        return $this;
    }

    public function onAfterDelete($handler, $priority = 0)
    {
        $this->_eventDispatcher->addListener('afterDelete', $handler, $priority);
        return $this;
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


        return $this;
    }

    public function isStored()
    {
        return $this->get('_id') && !$this->isModified('_id');
    }

    public function setScenario($scenario)
    {
        $this->_scenario = $scenario;

        return $this;
    }

    public function getScenario()
    {
        return $this->_scenario;
    }

    public function setNoScenario()
    {
        $this->_scenario = null;

        return $this;
    }

    public function isScenario($scenario)
    {
        return $scenario === $this->_scenario;
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
        
        foreach ($this->_validatorNamespaces as $namespace) {
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
        $this->_errors = array();

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
     * @throws \Sokil\Mongo\Document\Exception\Validate
     * @return \Sokil\Mongo\Document
     */
    public function validate()
    {
        if($this->triggerEvent('beforeValidate')->isCancelled()) {
            return $this;
        }

        if (!$this->isValid()) {
            $exception = new \Sokil\Mongo\Document\Exception\Validate('Document not valid');
            $exception->setDocument($this);

            $this->triggerEvent('validateError');

            throw $exception;
        }

        $this->triggerEvent('afterValidate');
        
        return $this;
    }

    public function hasErrors()
    {
        return ($this->_errors || $this->_triggeredErrors);
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
        return array_merge_recursive($this->_errors, $this->_triggeredErrors);
    }

    /**
     * Add validator error from validator classes and methods. This error 
     * reset on every revalidation
     * 
     * @param type $fieldName
     * @param type $ruleName
     * @param type $message
     * @return \Sokil\Mongo\Document
     */
    public function addError($fieldName, $ruleName, $message)
    {
        $this->_errors[$fieldName][$ruleName] = $message;
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
        $this->_errors = array_merge_recursive($this->_errors, $errors);
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
        $this->_triggeredErrors[$fieldName][$ruleName] = $message;
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
        $this->_triggeredErrors = array_merge_recursive($this->_triggeredErrors, $errors);
        return $this;
    }

    /**
     * Remove custom errors
     * 
     * @return \Sokil\Mongo\Document
     */
    public function clearTriggeredErrors()
    {
        $this->_triggeredErrors = array();
        return $this;
    }

    public function behaviors()
    {
        return array();
    }

    public function attachBehaviors(array $behaviors)
    {
        foreach ($behaviors as $name => $behavior) {

            if (!($behavior instanceof Behavior)) {
                if (empty($behavior['class'])) {
                    throw new Exception('Behavior class not specified');
                }

                $className = $behavior['class'];
                unset($behavior['class']);

                $behavior = new $className($behavior);
            }

            $this->attachBehavior($name, $behavior);
        }

        return $this;
    }

    public function attachBehavior($name, Behavior $behavior)
    {
        $behavior->setOwner($this);

        $this->_behaviors[$name] = $behavior;

        return $this;
    }

    public function clearBehaviors()
    {
        $this->_behaviors = array();
        return $this;
    }

    public function getOperator()
    {
        return $this->_operator;
    }

    public function isModificationOperatorDefined()
    {
        return $this->_operator->isDefined();
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
            $this->_operator->set($fieldName, $value);
        }

        return $this;
    }

    public function unsetField($fieldName)
    {
        if (!$this->has($fieldName)) {
            return $this;
        }

        parent::unsetField($fieldName);

        if ($this->getId()) {
            $this->_operator->unsetField($fieldName);
        }

        return $this;
    }

    /**
     * @deprecated use self::merge() instead
     * @param array $data
     * @return \Sokil\Mongo\Structure
     */
    public function fromArray(array $data)
    {
        $this->merge($data);
        return $this;
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
            $this->_operator->set($selector, $this->get($selector));
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
                $this->_operator->push($fieldName, $value);
            }
            $value = array($value);
        } // field already exist and has single value
        elseif (!is_array($oldValue)) {
            $value = array_merge((array) $oldValue, array($value));
            if ($this->getId()) {
                $this->_operator->set($fieldName, $value);
            }
        } // field exists and is array
        else {
            if ($this->getId()) {
                // check if array because previous $set operation on single value was executed
                $setValue = $this->_operator->get('$set', $fieldName);
                if ($setValue) {
                    $setValue[] = $value;
                    $this->_operator->set($fieldName, $setValue);
                } else {
                    $this->_operator->push($fieldName, $value);
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
                $this->_operator->pushEach($fieldName, $values);
            } elseif (!is_array($oldValue)) {
                $values = array_merge((array) $oldValue, $values);
                $this->_operator->set($fieldName, $values);
            } else {
                $this->_operator->pushEach($fieldName, $values);
                $values = array_merge($oldValue, $values);
            }
        } else {
            if ($oldValue) {
                $values = array_merge((array) $oldValue, $values);
            }
        }

        // update local data
        parent::set($fieldName, $values);
    }

    /**
     * Push each element of argument's array as single element to field value
     *
     * @deprecated since 1.6.0 use self::pushEach() instead
     * @param string $fieldName point-delimited field name
     * @param array $values
     */
    public function pushFromArray($fieldName, array $values)
    {
        return $this->pushEach($fieldName, $values);
    }

    /**
     * Removes from an existing array all instances of a value or values that match a specified query
     * 
     * @param string $fieldName
     * @param integer|string|array|\Sokil\Mongo\Expression $expression
     * @return \Sokil\Mongo\Document
     */
    public function pull($fieldName, $expression)
    {
        $this->_operator->pull($fieldName, $expression);
        return $this;
    }

    public function increment($fieldName, $value = 1)
    {
        parent::set($fieldName, (int) $this->get($fieldName) + $value);

        if ($this->getId()) {
            $this->_operator->increment($fieldName, $value);
        }


        return $this;
    }

    public function decrement($fieldName, $value = 1)
    {
        return $this->increment($fieldName, -1 * $value);
    }

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
        if($this->triggerEvent('beforeSave')->isCancelled()) {
            return $this;
        }

        // update
        if ($this->isStored()) {

            if($this->triggerEvent('beforeUpdate')->isCancelled()) {
                return $this;
            }

            $updateOperations = $this->getOperator()->getAll();

            $status = $this->getCollection()->getMongoCollection()->update(
                array('_id' => $this->getId()), $updateOperations
            );

            if ($status['ok'] != 1) {
                throw new Exception('Update error: ' . $status['err'] . ': ' . $status['errmsg']);
            }

            if ($this->getOperator()->isReloadRequired()) {
                $data = $this->getCollection()->getMongoCollection()->findOne(array('_id' => $this->getId()));
                $this->merge($data);
            }

            $this->getOperator()->reset();


            $this->triggerEvent('afterUpdate');
        } // insert
        else {

            if($this->triggerEvent('beforeInsert')->isCancelled()) {
                return $this;
            }

            $data = $this->toArray();

            // save data
            $this->getCollection()->insert($data);

            // set id
            $this->defineId($data['_id']);

            // event
            $this->triggerEvent('afterInsert');
        }

        // handle afterSave event
        $this->triggerEvent('afterSave');

        // set document as not modified
        $this->_modifiedFields = array();

        // set new original data
        $this->_originalData = $this->_data;

        return $this;
    }

    public function isSaveRequired()
    {
        return !$this->isStored() || $this->isModified() || $this->isModificationOperatorDefined();
    }

    public function delete()
    {
        $this->_collection->deleteDocument($this);
    }

}
