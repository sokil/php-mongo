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

class UrlValidator extends \Sokil\Mongo\Validator
{

    public function validateField(Structure $document, $fieldName, array $params)
    {
        $value = $document->get($fieldName);
        
        // check only if set
        if (!$value) {
            return;
        }

        // check if url valid
        $isValidUrl = (bool) filter_var($value, FILTER_VALIDATE_URL);
        if (!$isValidUrl) {
            if (!isset($params['message'])) {
                $params['message'] = 'Value of field "'
                    . $fieldName
                    . '" is not valid url in model '
                    . get_called_class();
            }

            $document->addError($fieldName, $this->getName(), $params['message']);
            return;
        }
        
        // ping not required - so url is valid
        if (empty($params['ping'])) {
            return;
        }
        
        // ping required
        $domainToCheck = rtrim(parse_url($value, PHP_URL_HOST), '.') . '.';
        $dnsRecordExists = checkdnsrr($domainToCheck);

        // network not allowed
        if ($dnsRecordExists === false) {
            throw new \RuntimeException('Error getting DNS record to validated url');
        }

        // empty array - host not found
        if (is_array($dnsRecordExists) && empty($dnsRecordExists)) {
            if (!isset($params['message'])) {
                $params['message'] = 'Value of field "'
                    . $fieldName
                    . '" is valid url but host is unreachable in model '
                    . get_called_class();
            }

            $document->addError($fieldName, $this->getName(), $params['message']);
            return;
        }

        if ($this->isUrlAccessible($value)) {
            return;
        }
        
        if (!isset($params['message'])) {
            $params['message'] = 'Value of field "'
                . $fieldName
                . '" is valid url but page not found '
                . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }

    /**
     * @param string $url
     * @return bool
     */
    private function isUrlAccessible($url)
    {
        $headers = get_headers($url, true);

        $isAccessible = false;
        $i = 0;
        while (true) {
            if (false !== strpos($headers[$i], '200')) {
                $isAccessible = true;
                break;
            }

            $i++;
            if (!isset($headers[$i])) {
                break;
            }
        }

        // page is accessible
        return $isAccessible;
    }
}
