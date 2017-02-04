<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo\Validator;

use Sokil\Mongo\Structure;

/**
 * Credit card number validator based on Luhn algorithm
 *
 * @author Dmytro Sokil <dmytro.sokil@gmail.com>
 */
class CardNumberValidator extends \Sokil\Mongo\Validator
{
    private function getMod($cardNumber)
    {
        $digitList = str_split($cardNumber);
        $digitListLength = count($digitList);
        
        for ($i = 0; $i < $digitListLength; $i = $i + 2) {
            $digit = $digitList[$i] * 2;
            if ($digit > 9) {
                $digit -= 9;
            }
            $digitList[$i] = $digit;
        }
        
        return array_sum($digitList) % 10;
    }
    
    public function validateField(Structure $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }
        
        $carsNumber = $document->get($fieldName);
        
        if (is_numeric($carsNumber) && 0 === $this->getMod($carsNumber)) {
            return;
        }
        
        if (!isset($params['message'])) {
            $params['message'] = 'Value of field "' . $fieldName . '" is not valid card number at ' . get_called_class();
        }
        
        $document->addError($fieldName, $this->getName(), $params['message']);
    }
}
