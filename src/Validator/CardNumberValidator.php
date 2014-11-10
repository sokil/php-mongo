<?php

namespace Sokil\Mongo\Validator;

class CardNumberValidator extends \Sokil\Mongo\Validator
{
    private function getMod($cardNumber)
    {
        $digitList = str_split($cardNumber);
        
        for($i = 0; $i < count($digitList); $i = $i + 2) {
            $digit = $digitList[$i] * 2;
            if($digit > 9) {
                $digit -= 9;
            }
            $digitList[$i] = $digit;
        }
        
        return array_sum($digitList) % 10;
    }
    
    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }
        
        if(0 === $this->getMod($document->get($fieldName))) {
            return;
        }
        
        if (!isset($params['message'])) {
            $params['message'] = 'Value of field "' . $fieldName . '" is not valid card number at ' . get_called_class();
        }
        
        $document->addError($fieldName, $this->getName(), $params['message']);
    }
}
