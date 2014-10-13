<?php

namespace Sokil\Mongo\Validator;

class EmailValidator extends \Sokil\Mongo\Validator
{

    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        $value = $document->get($fieldName);
        
        if (!$value) {
            return;
        }

        $isValidEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
        $isValidMX = true;

        if ($isValidEmail && !empty($params['mx'])) {
            list(, $host) = explode('@', $value);
            $isValidMX = checkdnsrr($host, 'MX');
        }
        
        if ($isValidEmail && $isValidMX) {
            return;
        }

        if (!isset($params['message'])) {
            $params['message'] = 'Value of field "' . $fieldName . '" is not email in model ' . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }

}
