<?php

namespace Sokil\Mongo\Validator;

class In extends \Sokil\Mongo\Validator
{

    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }

        if (in_array($document->get($fieldName), $params['range'])) {
            return;
        }
        
        if (!isset($params['message'])) {
            $rule['message'] = 'Field "' . $fieldName . '" not in range of allowed values in model ' . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $rule['message']);
        
    }

}
