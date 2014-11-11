<?php

namespace Sokil\Mongo\Validator;

class BetweenValidator extends \Sokil\Mongo\Validator
{

    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        $value = $document->get($fieldName);
        if (!$value) {
            return;
        }

        if (!isset($params['min'])) {
            throw new Exception('Minimum value of range not specified');
        }
        
        if (!isset($params['max'])) {
            throw new Exception('Maximum value of range not specified');
        }
        
        if($value < $params['min']) {
            if(empty($params['minMessage'])) {
                $params['minMessage'] = 'Field "' . $fieldName . '" less than minimal value of range in ' . get_called_class();
            }
            $document->addError($fieldName, $this->getName(), $params['minMessage']);
        }
        
        if($value > $params['max']) {
            if(empty($params['maxMessage'])) {
                $params['maxMessage'] = 'Field "' . $fieldName . '" less than minimal value of range in ' . get_called_class();
            }
            $document->addError($fieldName, $this->getName(), $params['maxMessage']);
        }

    }

}
