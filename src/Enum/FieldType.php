<?php

/**
 * This file is part of the PHPMongo package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\Mongo\Enum;

class FieldType
{
    const DOUBLE = 1;
    const STRING = 2;
    const OBJECT = 3;
    const ARRAY_TYPE = 4;
    const BINARY_DATA = 5;
    const UNDEFINED = 6; // deprecated
    const OBJECT_ID = 7;
    const BOOLEAN = 8;
    const DATE = 9;
    const NULL = 10;
    const REGULAR_EXPRESSION = 11;
    const JAVASCRIPT = 13;
    const SYMBOL = 14;
    const JAVASCRIPT_WITH_SCOPE = 15;
    const INT32 = 16;
    const TIMESTAMP = 17;
    const INT64 = 18;
    const MIN_KEY = 255;
    const MAX_KEY = 127;
}
