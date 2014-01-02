<?php

namespace Sokil\Mongo;

class Document extends Structure
{
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
    
    public function __construct(array $data = null) {
        
        $this->beforeConstruct();
        
        parent::__construct($data);
        
        $this->afterConstruct();
    }
    
    public function __toString()
    {
        return (string) $this->getId();
    }
    
    public function beforeConstruct() {}
    
    public function afterConstruct() {}
    
    public function beforeSave() {}
    
    public function afterSave() {}
    
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
        else if(!isset($this->_updateOperators['$push'][$fieldName]['$each'])) {
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
    
    public function _addIncUpdateOperation($selector, $value)
    {
        // check if update operations already added
        $oldIncrementValue = $this->getUpdateOperation('$inc', $selector);
        if($oldIncrementValue) {
            $value = $oldIncrementValue + $value;
        }
        
        $this->_updateOperators['$inc'][$selector] = $value;
        
        return $this;
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
     * @param type $selector
     * @param type $value
     * @return \Sokil\Mongo\Document
     */
    public function set($selector, $value)
    {
        parent::set($selector, $value);
        
        // if document saved - save through update
        if($this->getId()) {
            $this->_addSetUpdateOperation($selector, $value);
        }
        
        return $this;
    }
    
    /**
     * Push argument as single element to field value
     * 
     * @param string $selector
     * @param mixed $value
     */
    public function push($selector, $value)
    {
        $oldValue = $this->get($selector);
        
        if($value instanceof Structure) {
            $value = $value->toArray();
        }
        
        // field not exists
        if(!$oldValue) {
            if($this->getId()) {
                $this->_addPushUpdateOperation($selector, $value);
            }
            $value = array($value);
        }
        // field already exist and has single value
        elseif(!is_array($oldValue)) {
            $value = array_merge((array) $oldValue, array($value));
            if($this->getId()) {
                $this->_addSetUpdateOperation($selector, $value);
            }
        }
        // field exists and is array
        else {
            if($this->getId()) {
                $this->_addPushUpdateOperation($selector, $value);
            }
            $value = array_merge($oldValue, array($value));
        }
        
        // update local data
        parent::set($selector, $value);
    }
    
    /**
     * Push each element of argument's array as single element to field value
     * 
     * @param type $selector
     * @param array $value
     */
    public function pushFromArray($selector, array $value)
    {
        $oldValue = $this->get($selector);
        
        if($value instanceof Structure) {
            $value = $value->toArray();
        }
        
        // field not exists
        if(!$oldValue) {
            if($this->getId()) {
                $this->_addPushUpdateOperation($selector, array('$each' => $value));
            }
            
        }
        // field already exist and has single value
        else if(!is_array($oldValue)) {
            $value = array_merge((array) $oldValue, $value);
            if($this->getId()) {
                $this->_addSetUpdateOperation($selector, $value);
            }
        }
        // field already exists and is array
        else {
            if($this->getId()) {
                $this->_addPushUpdateOperation($selector, array('$each' => $value));
            }
            $value = array_merge($oldValue, $value);
        }
        
        // update local data
        parent::set($selector, $value);
    }
    
    public function increment($selector, $value = 1)
    {
        parent::set($selector, (int) $this->get($selector) + $value);
        
        if($this->getId()) {
            $this->_addIncUpdateOperation($selector, $value);
        }

        
        return $this; 
    }
    
    public function decrement($selector, $value = 1)
    {
        return $this->increment($selector, -1 * $value);
    }
}