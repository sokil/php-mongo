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

abstract class Validator
{
    public function getName()
    {
        $class = explode('\\', get_called_class());
        return strtolower(array_pop($class));
    }
    
    abstract public function validateField(Document $document, $fieldName, array $params);
    
    public function validate(Document $document, array $fieldNameList, array $params)
    {
        foreach($fieldNameList as $fieldName) {           
            $this->validateField($document, $fieldName, $params);
        }
    }
}