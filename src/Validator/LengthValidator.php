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

class LengthValidator extends \Sokil\Mongo\Validator
{

    public function validateField(Structure $document, $fieldName, array $params)
    {
        $value = $document->get($fieldName);

        if (!$value) {
            return;
        }

        $length = mb_strlen($value);

        // check if field is of specified length
        if (isset($params['is'])) {
            if ($length === $params['is']) {
                return;
            }

            if (!isset($params['message'])) {
                $params['message'] = sprintf(
                    'Field "%s" length not equal to %s in model %s',
                    $fieldName,
                    $params['is'],
                    get_called_class()
                );
            }

            $document->addError($fieldName, $this->getName(), $params['message']);
            return;
        }

        // check if fied is shorter than required
        if (isset($params['min'])) {
            if ($length < $params['min']) {
                if (!isset($params['messageTooShort'])) {
                    $params['messageTooShort'] = sprintf(
                        'Field "%s" length is shorter than %s in model %s',
                        $fieldName,
                        $params['min'],
                        get_called_class()
                    );
                }

                $document->addError($fieldName, $this->getName(), $params['messageTooShort']);
                return;
            }
        }

        // check if fied is longer than required
        if (isset($params['max'])) {
            if ($length > $params['max']) {
                if (!isset($params['messageTooLong'])) {
                    $params['messageTooLong'] = sprintf(
                        'Field "%s" length is longer than %s in model %s',
                        $fieldName,
                        $params['max'],
                        get_called_class()
                    );
                }

                $document->addError($fieldName, $this->getName(), $params['messageTooLong']);
                return;
            }
        }
    }
}
