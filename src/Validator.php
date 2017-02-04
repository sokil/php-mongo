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
        $class = strtolower(array_pop($class));

        // remove 'validator' suffix
        return substr($class, 0, -9);
    }
    
    abstract public function validateField(Structure $document, $fieldName, array $params);
    
    final public function validate(
        Structure $document,
        array $fieldNameList,
        array $params
    ) {
        foreach ($fieldNameList as $fieldName) {
            $this->validateField($document, $fieldName, $params);
        }
    }
}
