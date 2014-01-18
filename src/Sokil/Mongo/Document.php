<?php

namespace Sokil\Mongo;

class Document extends Structure
{
    const FIELD_TYPE_DOUBLE                   = 1;
    const FIELD_TYPE_STRING                   = 2;
    const FIELD_TYPE_OBJECT                   = 3;
    const FIELD_TYPE_ARRAY                    = 4;
    const FIELD_TYPE_BINARY_DATA              = 5;
    const FIELD_TYPE_UNDEFINED                = 6;  // deprecated
    const FIELD_TYPE_OBJECT_ID                = 7;
    const FIELD_TYPE_BOOLEAN                  = 8;
    const FIELD_TYPE_DATE                     = 9;
    const FIELD_TYPE_NULL                     = 10;
    const FIELD_TYPE_REGULAR_EXPRESSION       = 11;
    const FIELD_TYPE_JAVASCRIPT               = 13;
    const FIELD_TYPE_SYMBOL                   = 14;
    const FIELD_TYPE_JAVASCRIPT_WITH_SCOPE    = 15;
    const FIELD_TYPE_INT32                    = 16;
    const FIELD_TYPE_TIMESTAMP                = 17;
    const FIELD_TYPE_INT64                    = 18;
    const FIELD_TYPE_MIN_KEY                  = 255;
    const FIELD_TYPE_MAX_KEY                  = 127;
    
    protected $_scenario;
    
    /**
     *
     * @var array validator errors
     */
    private $_errors = array();
    
    /**
     *
     * @var array manually added validator errors
     */
    private $_triggeredErors = array();
    
    /**
     *
     * @var array list of update operations
     */
    private $_updateOperators = array();
    
    private $_events = array();
    
    public function __construct(array $data = null) {
        
        $this->triggerEvent('beforeConstruct');
        
        parent::__construct($data);
        
        $this->triggerEvent('afterConstruct');
    }
    
    public function __toString()
    {
        return (string) $this->getId();
    }
    
    public function attachEvent($event, $handler)
    {
        $this->_events[$event][] = $handler;
        return $this;
    }
    
    public function triggerEvent($event)
    {
        if(empty($this->_events[$event])) {
            return $this;
        }
        
        foreach($this->_events[$event] as $handler) {
            call_user_func($handler);
        }
        
        return $this;
    }
    
    public function onBeforeConstruct($handler) 
    {
        $this->_events['beforeConstruct'][] = $handler;
        return $this;
    }
    
    public function onAfterConstruct($handler)
    {
        $this->_events['afterConstruct'][] = $handler;
        return $this;
    }
    
    public function onBeforeInsert($handler)
    {
        $this->_events['beforeInsert'][] = $handler;
        return $this;
    }
    
    public function onAfterInsert($handler)
    {
        $this->_events['afterInsert'][] = $handler;
        return $this;
    }
    
    public function onBeforeUpdate($handler)
    {
        $this->_events['beforeUpdate'][] = $handler;
        return $this;
    }
    
    public function onAfterUpdate($handler)
    {
        $this->_events['afterUpdate'][] = $handler;
        return $this;
    }
    
    public function onBeforeSave($handler)
    {
        $this->_events['beforeSave'][] = $handler;
        return $this;
    }
    
    public function onAfterSave($handler)
    {
        $this->_events['afterSave'][] = $handler;
        return $this;
    }
    
    public function onBeforeDelete($handler)
    {
        $this->_events['beforeDelete'][] = $handler;
        return $this;
    }
    
    public function onAfterDelete($handler)
    {
        $this->_events['afterDelete'][] = $handler;
        return $this;
    }
    
    public function getId()
    {
        return $this->get('_id');
    }
    
    public function setId($id) {
        
        if(!($id instanceof \MongoId)) {
            $id = new \MongoId($id);
        }
        
        parent::set('_id', $id);
        
        return $this;
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
    
    /**
     * check if filled model params is valid
     * @return boolean
     */
    public function isValid()
    {
        $this->_errors = array();

        foreach($this->rules() as $rule)
        {
            $fields = array_map('trim', explode(',', $rule[0]));

            // check scenario
            if(!empty($rule['on'])) {
                $onScenarios = explode(',', $rule['on']);
                if(!in_array($this->getScenario(),  $onScenarios)) {
                    continue;
                }
            }

            if(!empty($rule['except'])) {
                $exceptScenarios = explode(',', $rule['except']);
                if(in_array($this->getScenario(),  $exceptScenarios)) {
                    continue;
                }
            }

            // validate
            switch($rule[1]) {
                case 'required':

                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" required in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'equals':

                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }
                        
                        if($this->get($field) !== $rule['to']) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" must be equals to "' . $rule['to'] . '" in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;
                    
                case 'not_equals':

                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }
                        
                        if($this->get($field) === $rule['to']) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" must not be equals to "' . $rule['to'] . '" in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'in':
                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }

                        if(!in_array($this->get($field), $rule['range'])) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" not in range of alloved values in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'numeric':
                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }
                        
                        if(!is_numeric($this->get($field))) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" not numeric in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'null':
                    foreach($fields as $field) {                        
                        if(null !== $this->get($field)) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" must be null in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'regexp':
                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }

                        if(!preg_match($rule['pattern'], $this->get($field))) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" not match regexp ' . $rule['pattern'] . ' in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;
                    
                case 'email':
                    foreach($fields as $field) {
                        $value = $this->get($field);
                        if(!$value) {
                            continue;
                        }

                        $isValidEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                        $isValidMX = true;
                        
                        if($isValidEmail && !empty($rule['mx'])) {
                            $isValidMX =  checkdnsrr(explode('@', $value)[1], 'MX');
                        }
                        
                        if(!$isValidEmail || !$isValidMX) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Value of field "' . $field . '" is not email in model ' . get_called_class();
                            }
                            
                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;
            }
        }

        return !$this->hasErrors();
    }
    
    /**
     * 
     * @throws \Sokil\Mongo\Document\Exception\Validate
     */
    public function validate()
    {
        if(!$this->isValid()) {
            throw new \Sokil\Mongo\Document\Exception\Validate('Document not valid');
        }
    }

    public function hasErrors()
    {
        return ($this->_errors || $this->_triggeredErors);
    }

    /**
     * get list of validation errors
     *
     * Format: $errors['fieldName']['rule'] = 'mesage';
     *
     * @return array list of validation errors
     */
    public function getErrors()
    {
        return array_merge_recursive($this->_errors, $this->_triggeredErors);
    }

    public function triggerError($fieldName, $rule, $message)
    {
        $this->_triggeredErors[$fieldName][$rule] = $message;
        return $this;
    }

    public function triggerErrors(array $errors)
    {
        $this->_triggeredErors = array_merge_recursive($this->_triggeredErors, $errors);
        return $this;
    }
    
    protected function _addSetUpdateOperation($fieldName, $value)
    {        
        if(!isset($this->_updateOperators['$set'])) {
            $this->_updateOperators['$set'] = array();
        }
        
        $this->_updateOperators['$set'][$fieldName] = $value;
        
        return $this;
    }
    
    protected function _addPushUpdateOperation($fieldName, $value)
    {
        // no $push operator found
        if(!isset($this->_updateOperators['$push'])) {
            $this->_updateOperators['$push'] = array();
        }
        
        // no field name found
        if(!isset($this->_updateOperators['$push'][$fieldName])) {
            $this->_updateOperators['$push'][$fieldName] = $value;
        }
        
        // field name found and has single value
        else if(!is_array($this->_updateOperators['$push'][$fieldName]) || !isset($this->_updateOperators['$push'][$fieldName]['$each'])) {
            $oldValue = $this->_updateOperators['$push'][$fieldName];
            $this->_updateOperators['$push'][$fieldName] = array(
                '$each' => array($oldValue, $value)
            );
        }
        
        // field name found and already $each
        else {
            $this->_updateOperators['$push'][$fieldName]['$each'][] = $value;
        }
    }
    
    protected function _addPushEachUpdateOperation($fieldName, array $value)
    {
        // no $push operator found
        if(!isset($this->_updateOperators['$push'])) {
            $this->_updateOperators['$push'] = array();
        }
        
        // no field name found
        if(!isset($this->_updateOperators['$push'][$fieldName])) {
            $this->_updateOperators['$push'][$fieldName] = array(
                '$each' => $value
            );
        }
        
        // field name found and has single value
        else if(!isset($this->_updateOperators['$push'][$fieldName]['$each'])) {
            $oldValue = $this->_updateOperators['$push'][$fieldName];
            $this->_updateOperators['$push'][$fieldName] = array(
                '$each' => array_merge(array($oldValue), $value)
            );
        }
        
        // field name found and already $each
        else {
            $this->_updateOperators['$push'][$fieldName]['$each'] = array_merge(
                $this->_updateOperators['$push'][$fieldName]['$each'],
                $value
            );
        }
    }
    
    public function _addIncUpdateOperation($fieldName, $value)
    {
        // check if update operations already added
        $oldIncrementValue = $this->getUpdateOperation('$inc', $fieldName);
        if($oldIncrementValue) {
            $value = $oldIncrementValue + $value;
        }
        
        $this->_updateOperators['$inc'][$fieldName] = $value;
        
        return $this;
    }
    
    public function _addPullUpdateOperation($fieldName, $value)
    {
        // no $push operator found
        $this->_updateOperators['$pull'][$fieldName] = $value;
    }
    
    public function hasUpdateOperations()
    {
        return (bool) $this->_updateOperators;
    }
    
    public function resetUpdateOperations()
    {
        $this->_updateOperators = array();
        return $this;
    }
    
    public function getUpdateOperation($operation, $fieldName = null)
    {
        if($fieldName) {
            return isset($this->_updateOperators[$operation][$fieldName])
                ? $this->_updateOperators[$operation][$fieldName]
                : null;
        }
        
        return isset($this->_updateOperators[$operation]) 
            ? $this->_updateOperators[$operation]
            : null;
    }
    
    public function getUpdateOperations()
    {
        return $this->_updateOperators;
    }
        
    /**
     * Update value in local cache and in DB
     * 
     * @param type $fieldName
     * @param type $value
     * @return \Sokil\Mongo\Document
     */
    public function set($fieldName, $value)
    {
        parent::set($fieldName, $value);
        
        // if document saved - save through update
        if($this->getId()) {
            $this->_addSetUpdateOperation($fieldName, $value);
        }
        
        return $this;
    }
    
    public function append($fieldName, $value)
    {
        parent::append($fieldName, $value);
        
        // if document saved - save through update
        if($this->getId()) {
            $this->_addSetUpdateOperation($fieldName, $this->get($fieldName));
        }
        
        return $this;
    }
    
    /**
     * Push argument as single element to field value
     * 
     * @param string $fieldName
     * @param mixed $value
     */
    public function push($fieldName, $value)
    {
        $oldValue = $this->get($fieldName);
        
        if($value instanceof Structure) {
            $value = $value->toArray();
        }
        
        // field not exists
        if(!$oldValue) {
            if($this->getId()) {
                $this->_addPushUpdateOperation($fieldName, $value);
            }
            $value = array($value);
        }
        // field already exist and has single value
        elseif(!is_array($oldValue)) {
            $value = array_merge((array) $oldValue, array($value));
            if($this->getId()) {
                $this->_addSetUpdateOperation($fieldName, $value);
            }
        }
        // field exists and is array
        else {
            if($this->getId()) {
                // check if array because previous $set operation on single value was executed
                $setValue = $this->getUpdateOperation('$set', $fieldName);
                if($setValue) {
                    $setValue[] = $value;
                    $this->_addSetUpdateOperation($fieldName, $setValue);
                }
                else {
                    $this->_addPushUpdateOperation($fieldName, $value);
                }
                
            }
            $value = array_merge($oldValue, array($value));
        }
        
        // update local data
        parent::set($fieldName, $value);
    }
    
    /**
     * Push each element of argument's array as single element to field value
     * 
     * @param type $fieldName
     * @param array $value
     */
    public function pushFromArray($fieldName, array $value)
    {
        $oldValue = $this->get($fieldName);
        
        if($value instanceof Structure) {
            $value = $value->toArray();
        }
        
        // field not exists
        if(!$oldValue) {
            if($this->getId()) {
                $this->_addPushUpdateOperation($fieldName, array('$each' => $value));
            }
            
        }
        // field already exist and has single value
        else if(!is_array($oldValue)) {
            $value = array_merge((array) $oldValue, $value);
            if($this->getId()) {
                $this->_addSetUpdateOperation($fieldName, $value);
            }
        }
        // field already exists and is array
        else {
            if($this->getId()) {
                $this->_addPushUpdateOperation($fieldName, array('$each' => $value));
            }
            $value = array_merge($oldValue, $value);
        }
        
        // update local data
        parent::set($fieldName, $value);
    }
    
    /**
     * 
     * @param string $fieldName
     * @param integer|string|array|\Sokil\Mongo\Expression $expression
     * @return \Sokil\Mongo\Document
     */
    public function pull($fieldName, $expression)
    {
        if($expression instanceof Expression) {
            $expression = $expression->toArray();
        }
        
        $this->_updateOperators['$pull'][$fieldName] = $expression;
        return $this;
    }
    
    public function increment($fieldName, $value = 1)
    {
        parent::set($fieldName, (int) $this->get($fieldName) + $value);
        
        if($this->getId()) {
            $this->_addIncUpdateOperation($fieldName, $value);
        }

        
        return $this; 
    }
    
    public function decrement($fieldName, $value = 1)
    {
        return $this->increment($fieldName, -1 * $value);
    }
}