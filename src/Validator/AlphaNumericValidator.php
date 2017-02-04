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
 * Alphanumeric values validator
 *
 * @author Dmytro Sokil <dmytro.sokil@gmail.com>
 */
class AlphaNumericValidator extends \Sokil\Mongo\Validator
{

    public function validateField(Structure $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }

        if (preg_match('/^\w+$/', $document->get($fieldName))) {
            return;
        }
        
        if (!isset($params['message'])) {
            $params['message'] = 'Field "' . $fieldName . '" not alpha-numeric in model ' . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }
}
