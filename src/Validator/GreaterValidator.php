<?php

namespace Sokil\Mongo\Validator;

class GreaterValidator extends \Sokil\Mongo\Validator
{

    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        $value = $document->get($fieldName);
        if (!$value) {
            return;
        }

        if (!isset($params['than'])) {
            throw new Exception('Maximum value not specified');
        }

        if($value <= $params['than']) {
            if(empty($params['message'])) {
                $params['message'] = 'Field "' . $fieldName . '" must be greater than specified value in ' . get_called_class();
            }
            $document->addError($fieldName, $this->getName(), $params['message']);
        }

    }
}
