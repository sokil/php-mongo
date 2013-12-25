<?php

namespace Sokil\Mongo;

class Document
{
    protected $_data = array();
    
    protected $_scenario;
    
    /**
     *
     * @var array validator errors
     */
    private $_errors = array();
    
    private $_updateOperators = array();
    
    public function __construct(array $data = null)
    {        
        if($data) {
            $this->fromArray($data);
        }
    }
    
    public function getId()
    {
        return isset($this->_data['_id']) ? $this->_data['_id'] : null;
    }
    
    public function setId($id) {
        if($id instanceof \MongoId) {
            $this->_data['_id'] = $id;
        }
        
        else {
            $this->_data['_id'] = new \MongoId((string) $id);
        }
        
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
        return $this->_errors;
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
        return $this->_errors;
    }

    public function triggerError($fieldName, $rule, $message)
    {
        $this->_errors[$fieldName][$rule] = $message;

        return $this;
    }

    protected function triggerErrors(array $errors)
    {
        $this->_errors = array_merge($this->_errors, $errors);

        return $this;
    }
    
    public function __get($name)
    {
        return $this->get($name);
    }
    
    public function get($selector)
    {
        if(false === strpos($selector, '.')) {
            return  isset($this->_data[$selector]) ? $this->_data[$selector] : null;
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
     * Handle setting params through public property
     * 
     * @param type $name
     * @param type $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
    
    /**
     * Store value to specified selector in local cache
     * 
     * @param type $selector
     * @param type $value
     * @return \Sokil\Mongo\Document
     * @throws Exception
     */
    private function _set($selector, $value)
    {        
        $arraySelector = explode('.', $selector);
        $chunksNum = count($arraySelector);
        
        // optimize one-level selector search
        if(1 == $chunksNum) {
            $this->_data[$selector] = $value;
            
            return $this;
        }
        
        // selector is nested
        $section = &$this->_data;

        for($i = 0; $i < $chunksNum - 1; $i++) {

            $field = $arraySelector[$i];

            if(!isset($section[$field])) {
                $section[$field] = array();
            }

            $section = &$section[$field];
        }
        
        // update local field
        $section[$arraySelector[$chunksNum - 1]] = $value;
        
        return $this;
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
        $this->_set($selector, $value);
        
        // if document saved - save through update
        if($this->getId()) {
            $this->addUpdateOperation('$set', $selector, $value);            
        }
        
        return $this;
    }
    
    private function addUpdateOperation($operation, $fieldName, $value)
    {        
        if(!isset($this->_updateOperators[$operation])) {
            $this->_updateOperators[$operation] = array();
        }
        
        $this->_updateOperators[$operation][$fieldName] = $value;
        
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
    
    public function getUpdateOperations()
    {
        return $this->_updateOperators;
    }
    
    public function push($selector, $value, $each = true)
    {
        $oldValue = $this->get($selector);
        
        if($oldValue && !is_array($oldValue)) {
            $value = array_merge((array) $oldValue, (array) $value);
            $this->addUpdateOperation('$set', $selector, $value);
        }
        else {
            if($this->getId()) {
                if($each && is_array($value)) {
                    $this->addUpdateOperation('$push', $selector, array('$each' => $value));
                }
                else {
                    $this->addUpdateOperation('$push', $selector, $value);
                }
            }
        }
        
        $this->_set($selector, $value);
    }
    
    public function increment($selector, $value = 1)
    {
        return $this->addUpdateOperation('$inc', $selector, $value);
    }
    
    public function decrement($selector, $value = 1)
    {
        return $this->addUpdateOperation('$inc', $selector, -1 * abs($value));
    }
    
    public function toArray()
    {
        return $this->_data;
    }
    
    public function fromArray(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
        
        // if document loaded from array - save entire document instead of sending commands
        $this->resetUpdateOperations();
        
        return $this;
    }
}