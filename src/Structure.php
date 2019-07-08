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

use GeoJson\Geometry\Geometry;
use Sokil\Mongo\Document\InvalidDocumentException;
use Sokil\Mongo\Type\TypeChecker;

class Structure implements
    ArrayableInterface,
    \JsonSerializable
{
    /**
     * @deprecated use self::$schema to define initial data and getters or setters to get or set field's values.
     * @var array
     */
    protected $_data = array();

    /**
     * Document's data
     * @var array
     */
    private $data = array();

    /**
     * Initial document's data
     * @var array
     */
    protected $schema = array();

    /**
     *
     * @var array original data.
     */
    private $originalData = array();

    /**
     *
     * @var array modified fields.
     */
    private $modifiedFields = array();

    /**
     * Name of scenario, used for validating fields
     * @var string
     */
    private $scenario;

    /**
     *
     * @var array list of namespaces
     */
    private $validatorNamespaces = array(
        '\Sokil\Mongo\Validator',
    );


    /**
     * @var array validator errors
     */
    private $errors = array();

    /**
     * @var array manually added validator errors
     */
    private $triggeredErrors = array();

    /**
     * @param array|null $data data to initialise structure
     * @param bool $notModified define if data set as modified or not
     */
    public function __construct(
        array $data = null,
        $notModified = true
    ) {
        // self::$data and self::$schema instead of deprecated self::$_data
        if (!empty($this->_data)) {
            $this->schema = $this->_data;
        }

        $this->_data = &$this->data;

        // define initial data with schema
        $this->data = $this->schema;
        $this->originalData = $this->data;

        // execute before construct callable
        $this->beforeConstruct();

        // initialize with passed data
        if ($data) {
            if ($notModified) {
                // set as not modified
                $this->replace($data);
            } else {
                // set as modified
                $this->merge($data);
            }
        }
    }

    /**
     * Event handler, called before running constructor.
     * May be overridden in child classes
     */
    public function beforeConstruct()
    {
        // override this to add some functionality on structure instance initialization
    }

    /**
     * Cloning not allowed because cloning of object not clone related aggregates of this object, so
     * cloned object has links to original aggregates. This is difficult to handle.
     */
    final public function __clone()
    {
        throw new \RuntimeException('Cloning not allowed');
    }

    /**
     * IMPORTANT! Do not use this method
     *
     * This method allow set data of document in external code.
     * e.g. link data of document to GridFS file matadata.
     * Modification of document's data will modify external data too.
     * Note that also the opposite case also right - modification of external data will
     * modify document's data directly, so document may be in unconsisted state.
     *
     * @param array $data reference to data in external code
     * @return Structure
     */
    protected function setDataReference(array &$data)
    {
        $this->data = &$data;
        return $this;
    }

    public function __get($name)
    {
        // get first-level value
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * @param string $selector
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($selector, $default = null)
    {
        if (false === strpos($selector, '.')) {
            return isset($this->data[$selector]) ? $this->data[$selector] : $default;
        }

        $value = $this->data;
        foreach (explode('.', $selector) as $field) {
            if (!isset($value[$field])) {
                return $default;
            }

            $value = $value[$field];
        }

        return $value;
    }

    /**
     * Get structure object from a document's value
     *
     * @param string $selector
     * @param string|callable $className string class name or closure, which accept data and return string class name
     * @return object representation of document with class, passed as argument
     * @throws \Sokil\Mongo\Exception
     */
    public function getObject($selector, $className = '\Sokil\Mongo\Structure')
    {
        $data = $this->get($selector);
        if (!$data) {
            return null;
        }

        // get class name from callable
        if (is_callable($className)) {
            $className = $className($data);
        }

        // prepare structure
        $structure =  new $className();
        if (!($structure instanceof Structure)) {
            throw new Exception('Wrong structure class specified');
        }

        return $structure->merge($data);
    }

    /**
     * Get list of structure objects from list of values in mongo document
     *
     * @param string $selector
     * @param string|callable $className Structure class name or closure, which accept data and return string class name of Structure
     *
     * @return object|array representation of document with class, passed as argument
     *
     * @throws \Sokil\Mongo\Exception
     */
    public function getObjectList($selector, $className = '\Sokil\Mongo\Structure')
    {
        $data = $this->get($selector);
        if (!$data || !is_array($data)) {
            return array();
        }

        // class name is string
        if (is_string($className)) {
            $list = array_map(
                function ($dataItem) use ($className) {
                    $listItemStructure = new $className();
                    if (!($listItemStructure instanceof Structure)) {
                        throw new Exception('Wrong structure class specified');
                    }
                    $listItemStructure->mergeUnmodified($dataItem);
                    return $listItemStructure;
                },
                $data
            );

            return $list;
        }

        // class name id callable
        if (is_callable($className)) {
            return array_map(function ($dataItem) use ($className) {
                $classNameString = $className($dataItem);
                $listItemStructure = new $classNameString;
                if (!($listItemStructure instanceof Structure)) {
                    throw new Exception('Wrong structure class specified');
                }

                return $listItemStructure->merge($dataItem);
            }, $data);
        }

        throw new Exception('Wrong class name specified. Use string or closure');
    }

    /**
     * Handle setting params through public property
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Store value to specified selector in local cache
     *
     * @param string $selector point-delimited field selector
     * @param mixed $value value
     *
     * @return Structure
     *
     * @throws Exception
     */
    public function set($selector, $value)
    {
        $value = self::prepareToStore($value);

        // modify
        $arraySelector = explode('.', $selector);
        $chunksNum = count($arraySelector);

        // optimize one-level selector search
        if (1 == $chunksNum) {
            // update only if new value different from current
            if (!isset($this->data[$selector]) || $this->data[$selector] !== $value) {
                // modify
                $this->data[$selector] = $value;
                // mark field as modified
                $this->modifiedFields[] = $selector;
            }

            return $this;
        }

        // selector is nested
        $section = &$this->data;

        for ($i = 0; $i < $chunksNum - 1; $i++) {
            $field = $arraySelector[$i];

            if (!isset($section[$field])) {
                $section[$field] = array();
            } elseif (!is_array($section[$field])) {
                throw new Exception('Assigning sub-document to scalar value not allowed');
            }

            $section = &$section[$field];
        }

        // update only if new value different from current
        if (!isset($section[$arraySelector[$chunksNum - 1]]) || $section[$arraySelector[$chunksNum - 1]] !== $value) {
            // modify
            $section[$arraySelector[$chunksNum - 1]] = $value;
            // mark field as modified
            $this->modifiedFields[] = $selector;
        }

        return $this;
    }

    /**
     * Check if structure has field identified by selector
     *
     * @param string $selector
     *
     * @return bool
     */
    public function has($selector)
    {
        $pointer = &$this->data;

        foreach (explode('.', $selector) as $field) {
            if (!array_key_exists($field, $pointer)) {
                return false;
            }

            $pointer = &$pointer[$field];
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * Normalize variable to be able to store in database
     *
     * @param mixed $value
     *
     * @return array
     *
     * @throws InvalidDocumentException
     */
    public static function prepareToStore($value)
    {
        // if array - try to prepare every value
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::prepareToStore($v);
            }

            return $value;
        }

        // if scalar - return it
        if (!is_object($value)) {
            return $value;
        }

        // if internal mongo types - pass it as is
        if (TypeChecker::isInternalType($value)) {
            return $value;
        }

        // do not convert geo-json to array
        if ($value instanceof Geometry) {
            return $value->jsonSerialize();
        }

        // structure
        if ($value instanceof Structure) {
            // validate structure
            if (!$value->isValid()) {
                $exception = new InvalidDocumentException('Embedded document not valid');
                $exception->setDocument($value);
                throw $exception;
            }

            // get array from structure
            return $value->toArray();
        }

        // other objects convert to array
        return (array) $value;
    }

    public function unsetField($selector)
    {
        // modify
        $arraySelector = explode('.', $selector);
        $chunksNum = count($arraySelector);

        // optimize one-level selector search
        if (1 == $chunksNum) {
            // check if field exists
            if (isset($this->data[$selector])) {
                // unset field
                unset($this->data[$selector]);
                // mark field as modified
                $this->modifiedFields[] = $selector;
            }

            return $this;
        }

        // find section
        $section = &$this->data;

        for ($i = 0; $i < $chunksNum - 1; $i++) {
            $field = $arraySelector[$i];

            if (!isset($section[$field])) {
                return $this;
            }

            $section = &$section[$field];
        }

        // check if field exists
        if (isset($section[$arraySelector[$chunksNum - 1]])) {
            // unset field
            unset($section[$arraySelector[$chunksNum - 1]]);
            // mark field as modified
            $this->modifiedFields[] = $selector;
        }

        return $this;
    }

    /**
     * If field not exist - set value.
     * If field exists and is not array - convert to array and append
     * If field -s array - append
     *
     * @param string $selector
     * @param mixed $value
     * @return \Sokil\Mongo\Structure
     */
    public function append($selector, $value)
    {
        $oldValue = $this->get($selector);
        if ($oldValue) {
            if (!is_array($oldValue)) {
                $oldValue = (array) $oldValue;
            }
            $oldValue[] = $value;
            $value = $oldValue;
        }

        $this->set($selector, $value);
        return $this;
    }

    /**
     * If selector passed, return true if field is modified.
     * If selector omitted, return true if document is modified.
     *
     * @param string|null $selector
     * @return bool
     */
    public function isModified($selector = null)
    {
        if (empty($this->modifiedFields)) {
            return false;
        }

        if (empty($selector)) {
            return (bool) $this->modifiedFields;
        }

        foreach ($this->modifiedFields as $modifiedField) {
            if (preg_match('/^' . $selector . '($|.)/', $modifiedField)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getModifiedFields()
    {
        return $this->modifiedFields;
    }

    /**
     * @return array
     */
    public function getOriginalData()
    {
        return $this->originalData;
    }

    public function toArray()
    {
        return $this->data;
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * Recursive function to merge data for Structure::mergeUnmodified()
     *
     * @param array $target
     * @param array $source
     */
    private function mergeUnmodifiedPartial(array &$target, array $source)
    {
        foreach ($source as $key => $value) {
            if (is_array($value) && isset($target[$key])) {
                $this->mergeUnmodifiedPartial($target[$key], $value);
            } else {
                $target[$key] = $value;
            }
        }
    }

    /**
     * Merge array to current structure without setting modification mark
     *
     * @param array $data
     * @return \Sokil\Mongo\Structure
     */
    public function mergeUnmodified(array $data)
    {
        $this->mergeUnmodifiedPartial($this->data, $data);
        $this->mergeUnmodifiedPartial($this->originalData, $data);

        return $this;
    }

    /**
     * Check if array is sequential list
     * @param array $array
     */
    private function isEmbeddedDocument($array)
    {
        return is_array($array) && (array_values($array) !== $array);
    }

    /**
     * Recursive function to merge data for Structure::merge()
     *
     * @param array $document
     * @param array $updatedDocument
     * @param string $prefix
     */
    private function mergePartial(array &$document, array $updatedDocument, $prefix = null)
    {
        foreach ($updatedDocument as $key => $newValue) {
            // if original data is embedded document and value also - then merge
            if (is_array($newValue) && isset($document[$key]) && $this->isEmbeddedDocument($document[$key])) {
                $this->mergePartial($document[$key], $newValue, $prefix . $key . '.');
            } // in other cases just set new value
            else {
                $document[$key] = $newValue;
                $this->modifiedFields[] = $prefix . $key;
            }
        }
    }

    /**
     * Merge array to current structure with setting modification mark
     *
     * @param array $data
     * @return \Sokil\Mongo\Structure
     */
    public function merge(array $data)
    {
        $this->mergePartial($this->data, $data);
        return $this;
    }

    /**
     * Replace data of document with passed.
     * Document became unmodified
     *
     * @param array $data new document data
     */
    public function replace(array $data)
    {
        $this->data = $data;
        $this->apply();

        return $this;
    }

    /**
     * Replace modified fields with original
     * @return $this
     */
    public function reset()
    {
        $this->data = $this->originalData;
        $this->modifiedFields = array();

        return $this;
    }

    /**
     * Apply modified document fields as original
     *
     * @return $this
     */
    protected function apply()
    {
        $this->originalData = $this->data;
        $this->modifiedFields = array();

        return $this;
    }

    /**
     * Validation rules
     * @return array
     */
    public function rules()
    {
        return array();
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

    public function hasErrors()
    {
        return (!empty($this->errors) || !empty($this->triggeredErrors));
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
     * reset on every re-validation
     *
     * @param string $fieldName dot-notated field name
     * @param string $ruleName name of validation rule
     * @param string $message error message
     *
     * @return Structure
     */
    public function addError($fieldName, $ruleName, $message)
    {
        $this->errors[$fieldName][$ruleName] = $message;

        return $this;
    }

    /**
     * Add errors
     *
     * @param array $errors
     *
     * @return Structure
     */
    public function addErrors(array $errors)
    {
        $this->errors = array_merge_recursive($this->errors, $errors);

        return $this;
    }

    /**
     * Add custom error which not reset after validation
     *
     * @param string $fieldName
     * @param string $ruleName
     * @param string $message
     *
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
     * Clear triggered and validation errors
     * @return $this
     */
    public function clearErrors()
    {
        $this->errors = array();
        $this->triggeredErrors = array();
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

    /**
     * Add own namespace of validators
     *
     * @param string $namespace
     *
     * @return Structure
     */
    public function addValidatorNamespace($namespace)
    {
        $this->validatorNamespaces[] = rtrim($namespace, '\\');
        return $this;
    }

    private function getValidatorClassNameByRuleName($ruleName)
    {
        if (false !== strpos($ruleName, '_')) {
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
     * Check if filled model params is valid
     *
     * @return boolean
     *
     * @throws Exception
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
                if (!$validator instanceof Validator) {
                    throw new Exception('Validator class must implement \Sokil\Mongo\Validator class');
                }

                $validator->validate($this, $fields, $params);
            }
        }

        return !$this->hasErrors();
    }
}
