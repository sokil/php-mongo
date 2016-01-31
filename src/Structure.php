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

class Structure implements
    ArrayableInterface,
    \JsonSerializable
{
    protected $_data = array();

    /**
     *
     * @var original data.
     */
    private $originalData = array();

    /**
     *
     * @var modified fields.
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

    public function __get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    public function get($selector)
    {
        if(false === strpos($selector, '.')) {
            return isset($this->_data[$selector]) ? $this->_data[$selector] : null;
        }

        $value = $this->_data;
        foreach(explode('.', $selector) as $field)
        {
            if(!isset($value[$field])) {
                return null;
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
    public function getObject($selector, $className)
    {
        $data = $this->get($selector);
        if(!$data) {
            return null;
        }

        // get class name from callable
        if(is_callable($className)) {
            $className = $className($data);
        }

        // prepare structure
        $structure =  new $className();
        if(!($structure instanceof Structure)) {
            throw new Exception('Wrong structure class specified');
        }

        return clone $structure->merge($data);
    }

    /**
     * Get list of structure objects from list of values in mongo document
     *
     * @param string $selector
     * @param string|callable $className Structure class name or closure, which accept data and return string class name of Structure
     * @return object representation of document with class, passed as argument
     * @throws \Sokil\Mongo\Exception
     */
    public function getObjectList($selector, $className)
    {
        $data = $this->get($selector);
        if(!$data || !is_array($data)) {
            return array();
        }

        // class name is string
        if(is_string($className)) {

            $structure = new $className();
            if(!($structure instanceof Structure)) {
                throw new Exception('Wrong structure class specified');
            }

            return array_map(function($dataItem) use($structure) {
                return clone $structure->mergeUnmodified($dataItem);
            }, $data);
        }

        // class name id callable
        if(is_callable($className)) {

            $structurePrototypePool = array();

            return array_map(function($dataItem) use($structurePrototypePool, $className) {

                // get Structure class name from callable
                $classNameString = $className($dataItem);

                // create structure prototype
                if(empty($structurePrototypePool[$classNameString])) {
                    $structurePrototypePool[$classNameString] = new $classNameString;
                    if(!($structurePrototypePool[$classNameString] instanceof Structure)) {
                        throw new Exception('Wrong structure class specified');
                    }
                }

                // instantiate structure from related prototype
                $structure = clone $structurePrototypePool[$classNameString];

                return $structure->merge($dataItem);
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
     * @return \Sokil\Mongo\Document
     * @throws Exception
     */
    public function set($selector, $value)
    {
        $value = self::prepareToStore($value);

        // modify
        $arraySelector = explode('.', $selector);
        $chunksNum = count($arraySelector);

        // optimize one-level selector search
        if(1 == $chunksNum) {

            // update only if new value different from current
            if(!isset($this->_data[$selector]) || $this->_data[$selector] !== $value) {
                // modify
                $this->_data[$selector] = $value;
                // mark field as modified
                $this->modifiedFields[] = $selector;
            }

            return $this;
        }

        // selector is nested
        $section = &$this->_data;

        for($i = 0; $i < $chunksNum - 1; $i++) {

            $field = $arraySelector[$i];

            if(!isset($section[$field])) {
                $section[$field] = array();
            } elseif(!is_array($section[$field])) {
                throw new Exception('Assigning sub-document to scalar value not allowed');
            }

            $section = &$section[$field];
        }

        // update only if new value different from current
        if(!isset($section[$arraySelector[$chunksNum - 1]]) || $section[$arraySelector[$chunksNum - 1]] !== $value) {
            // modify
            $section[$arraySelector[$chunksNum - 1]] = $value;
            // mark field as modified
            $this->modifiedFields[] = $selector;
        }

        return $this;
    }

    public function has($selector)
    {
        $pointer = &$this->_data;

        foreach(explode('.', $selector) as $field) {
            if(!array_key_exists($field, $pointer)) {
                return false;
            }

            $pointer = &$pointer[$field];
        }

        return true;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public static function prepareToStore($value)
    {
        // if array - try to prepare every value
        if(is_array($value)) {
            foreach($value as $k => $v) {
                $value[$k] = self::prepareToStore($v);
            }

            return $value;
        }

        // if scalar - return it
        if(!is_object($value)) {
            return $value;
        }

        // if internal mongo types - pass it as is
        if(in_array(get_class($value), array('MongoId', 'MongoCode', 'MongoDate', 'MongoRegex', 'MongoBinData', 'MongoInt32', 'MongoInt64', 'MongoDBRef', 'MongoMinKey', 'MongoMaxKey', 'MongoTimestamp'))) {
            return $value;
        }

        // do not convert geo-json to array
        if($value instanceof \GeoJson\Geometry\Geometry) {
            return $value->jsonSerialize();
        }

        // structure
        if($value instanceof Structure) {
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
        if(1 == $chunksNum) {
            // check if field exists
            if(isset($this->_data[$selector])) {
                // unset field
                unset($this->_data[$selector]);
                // mark field as modified
                $this->modifiedFields[] = $selector;
            }

            return $this;
        }

        // find section
        $section = &$this->_data;

        for($i = 0; $i < $chunksNum - 1; $i++) {

            $field = $arraySelector[$i];

            if(!isset($section[$field])) {
                return $this;
            }

            $section = &$section[$field];
        }

        // check if field exists
        if(isset($section[$arraySelector[$chunksNum - 1]])) {
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
     * @param type $selector
     * @param type $value
     * @return \Sokil\Mongo\Structure
     */
    public function append($selector, $value)
    {
        $oldValue = $this->get($selector);
        if($oldValue) {
            if(!is_array($oldValue)) {
                $oldValue = (array) $oldValue;
            }
            $oldValue[] = $value;
            $value = $oldValue;
        }

        $this->set($selector, $value);
        return $this;
    }

    public function isModified($selector = null)
    {
        if(!$this->modifiedFields) {
            return false;
        }

        if(!$selector) {
            return (bool) $this->modifiedFields;
        }

        foreach($this->modifiedFields as $modifiedField) {
            if(preg_match('/^' . $selector . '($|.)/', $modifiedField)) {
                return true;
            }
        }

        return false;
    }

    public function getModifiedFields()
    {
        return $this->modifiedFields;
    }

    public function getOriginalData()
    {
        return $this->originalData;
    }

    public function toArray()
    {
        return $this->_data;
    }

    public function jsonSerialize()
    {
        return $this->_data;
    }

    /**
     * Recursive function to merge data for Structure::mergeUnmodified()
     *
     * @param array $target
     * @param array $source
     */
    private function mergeUnmodifiedPartial(array &$target, array $source)
    {
        foreach($source as $key => $value) {
            if(is_array($value) && isset($target[$key])) {
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
        $this->mergeUnmodifiedPartial($this->_data, $data);
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
        foreach($updatedDocument as $key => $newValue) {
            // if original data is embedded document and value also - then merge
            if(is_array($newValue) && isset($document[$key]) && $this->isEmbeddedDocument($document[$key])) {
                $this->mergePartial($document[$key], $newValue, $prefix . $key . '.');
            }
            // in other cases just set new value
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
        $this->mergePartial($this->_data, $data);
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
        $this->_data = $data;
        $this->apply();

        return $this;
    }
    
    public function reset()
    {
        $this->_data = $this->originalData;
        $this->modifiedFields = array();

        return $this;
    }
    
    public function apply()
    {
        $this->originalData = $this->_data;
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
     * @param type $namespace
     * @return \Sokil\Mongo\Document
     */
    public function addValidatorNamespace($namespace)
    {
        $this->validatorNamespaces[] = rtrim($namespace, '\\');
        return $this;
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

}
