<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo\Type;
use Sokil\Mongo\Expression;

/**
 * Internal helper class to check if value has some type
 */
class TypeChecker
{
    /**
     * Check if value belongs to internal Mongo type, converted by driver to scalars
     *
     * @param mixed $value
     * @return bool
     */
    public static function isInternalType($value)
    {
        if (!is_object($value)) {
            return false;
        }

        if (class_exists('\MongoDB\BSON\Type')) {
            // mongodb extension for PHP 7
            return $value instanceof \MongoDB\BSON\Type;
        } else {
            // legacy mongo extension for PHP 5
            $mongoTypes = array(
                'MongoId',
                'MongoCode',
                'MongoDate',
                'MongoRegex',
                'MongoBinData',
                'MongoInt32',
                'MongoInt64',
                'MongoDBRef',
                'MongoMinKey',
                'MongoMaxKey',
                'MongoTimestamp'
            );

            return in_array(
                get_class($value),
                $mongoTypes
            );
        }
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isRegex($value)
    {
        if (!is_object($value)) {
            return false;
        }

        if (class_exists('\MongoDB\BSON\Regex')) {
            return $value instanceof \MongoDB\BSON\Regex;
        } else {
            return get_class($value) === 'MongoRegex';
        }

    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isExpression($value)
    {
        return ($value instanceof Expression) || self::isHashMap($value);
    }

    /**
     * Check if php array is hash map
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function isHashMap($value)
    {
        return is_array($value) && is_string(key($value));
    }
}
