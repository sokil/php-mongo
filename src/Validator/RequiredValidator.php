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

class RequiredValidator extends \Sokil\Mongo\Validator
{
    public function validateField(Structure $document, $fieldName, array $params)
    {
        if ($document->get($fieldName)) {
            return;
        }
        
        $errorMessage = isset($params['message'])
            ? $params['message']
            : 'Field "' . $fieldName . '" required in model ' . get_class($document);

        $document->addError(
            $fieldName,
            $this->getName(),
            $errorMessage
        );
    }
}
