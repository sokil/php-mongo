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

use Sokil\Mongo\Enum\FieldType;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\Point;
use Sokil\Mongo\Type\TypeChecker;

/**
 * This class represents all expressions used to query document from collection
 *
 * @link http://docs.mongodb.org/manual/reference/operator/query/
 */
class Expression implements ArrayableInterface
{
    /**
     * @deprecated Since 1.22 using this property is NOT ALLOWED. Use getters and setters instead.
     * @var array
     */
    protected $_expression = array();

    /**
     * Create new instance of expression
     * @return Expression
     */
    public function expression()
    {
        return new self;
    }

    /**
     * @param string $field
     * @param string|array $value
     *
     * @return Expression
     */
    public function where($field, $value)
    {
        if (!isset($this->_expression[$field]) || !is_array($value) || !is_array($this->_expression[$field])) {
            $this->_expression[$field] = $value;
        } else {
            $this->_expression[$field] = array_merge_recursive($this->_expression[$field], $value);
        }

        return $this;
    }

    /**
     * Filter empty field
     *
     * @param string $field
     *
     * @return Expression
     */
    public function whereEmpty($field)
    {
        return $this->where(
            '$or',
            array(
                array($field => null),
                array($field => ''),
                array($field => array()),
                array($field => array('$exists' => false))
            )
        );
    }

    public function whereNotEmpty($field)
    {
        return $this->where('$nor', array(
            array($field => null),
            array($field => ''),
            array($field => array()),
            array($field => array('$exists' => false))
        ));
    }

    public function whereGreater($field, $value)
    {
        return $this->where($field, array('$gt' => $value));
    }

    public function whereGreaterOrEqual($field, $value)
    {
        return $this->where($field, array('$gte' => $value));
    }

    public function whereLess($field, $value)
    {
        return $this->where($field, array('$lt' => $value));
    }

    public function whereLessOrEqual($field, $value)
    {
        return $this->where($field, array('$lte' => $value));
    }

    public function whereNotEqual($field, $value)
    {
        return $this->where($field, array('$ne' => $value));
    }

    /**
     * Selects the documents where the value of a
     * field equals any value in the specified array.
     *
     * @param string $field
     * @param array $values
     * @return Expression
     */
    public function whereIn($field, array $values)
    {
        return $this->where($field, array('$in' => $values));
    }

    public function whereNotIn($field, array $values)
    {
        return $this->where($field, array('$nin' => $values));
    }

    public function whereExists($field)
    {
        return $this->where($field, array('$exists' => true));
    }

    public function whereNotExists($field)
    {
        return $this->where($field, array('$exists' => false));
    }

    public function whereHasType($field, $type)
    {
        return $this->where($field, array('$type' => (int) $type));
    }

    public function whereDouble($field)
    {
        return $this->whereHasType($field, FieldType::DOUBLE);
    }

    public function whereString($field)
    {
        return $this->whereHasType($field, FieldType::STRING);
    }

    public function whereObject($field)
    {
        return $this->whereHasType($field, FieldType::OBJECT);
    }

    public function whereBoolean($field)
    {
        return $this->whereHasType($field, FieldType::BOOLEAN);
    }

    public function whereArray($field)
    {
        return $this->whereJsCondition('Array.isArray(this.' . $field . ')');
    }

    public function whereArrayOfArrays($field)
    {
        return $this->whereHasType($field, FieldType::ARRAY_TYPE);
    }

    public function whereObjectId($field)
    {
        return $this->whereHasType($field, FieldType::OBJECT_ID);
    }

    public function whereDate($field)
    {
        return $this->whereHasType($field, FieldType::DATE);
    }

    public function whereNull($field)
    {
        return $this->whereHasType($field, FieldType::NULL);
    }

    public function whereJsCondition($condition)
    {
        return $this->where('$where', $condition);
    }

    public function whereLike($field, $regex, $caseInsensitive = true)
    {
        // regex
        $expression = array(
            '$regex'    => $regex,
        );

        // options
        $options = '';

        if ($caseInsensitive) {
            $options .= 'i';
        }

        $expression['$options'] = $options;

        // query
        return $this->where($field, $expression);
    }

    /**
     * Find documents where the value of a field is an array
     * that contains all the specified elements.
     * This is equivalent of logical AND.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/query/all/
     *
     * @param string $field point-delimited field name
     * @param array $values
     * @return Expression
     */
    public function whereAll($field, array $values)
    {
        return $this->where($field, array('$all' => $values));
    }

    /**
     * Find documents where the value of a field is an array
     * that contains none of the specified elements.
     * This is equivalent of logical AND.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/query/all/
     *
     * @param string $field point-delimited field name
     * @param array $values
     * @return Expression
     */
    public function whereNoneOf($field, array $values)
    {
        return $this->where($field, array(
            '$not' => array(
                '$all' => $values
            ),
        ));
    }

    /**
     * Find documents where the value of a field is an array
     * that contains any of the specified elements.
     * This is equivalent of logical AND.
     *
     * @param string $field point-delimited field name
     * @param array $values
     * @return Expression
     */
    public function whereAny($field, array $values)
    {
        return $this->whereIn($field, $values);
    }

    /**
     * Matches documents in a collection that contain an array field with at
     * least one element that matches all the specified query criteria.
     *
     * @param string $field point-delimited field name
     * @param \Sokil\Mongo\Expression|callable|array $expression
     *
     * @return Expression
     *
     * @throws Exception
     */
    public function whereElemMatch($field, $expression)
    {
        if (is_callable($expression)) {
            $expression = call_user_func($expression, $this->expression());
        }

        if ($expression instanceof Expression) {
            $expression = $expression->toArray();
        } elseif (!is_array($expression)) {
            throw new Exception('Wrong expression passed');
        }

        return $this->where($field, array('$elemMatch' => $expression));
    }

    /**
     * Matches documents in a collection that contain an array field with elements
     * that do not matches all the specified query criteria.
     *
     * @param string $field
     * @param Expression|callable|array $expression
     *
     * @return Expression
     */
    public function whereElemNotMatch($field, $expression)
    {
        return $this->whereNot($this->expression()->whereElemMatch($field, $expression));
    }

    /**
     * Selects documents if the array field is a specified size.
     *
     * @param string $field
     * @param integer $length
     * @return Expression
     */
    public function whereArraySize($field, $length)
    {
        return $this->where($field, array('$size' => (int) $length));
    }

    /**
     * Selects the documents that satisfy at least one of the expressions
     *
     * @param array|\Sokil\Mongo\Expression $expressions Array of Expression instances or comma delimited expression list
     *
     * @return Expression
     */
    public function whereOr($expressions = null /**, ...**/)
    {
        if ($expressions instanceof Expression) {
            $expressions = func_get_args();
        }

        return $this->where('$or', array_map(function (Expression $expression) {
            return $expression->toArray();
        }, $expressions));
    }

    /**
     * Select the documents that satisfy all the expressions in the array
     *
     * @param array|\Sokil\Mongo\Expression $expressions Array of Expression instances or comma delimited expression list
     * @return Expression
     */
    public function whereAnd($expressions = null /**, ...**/)
    {
        if ($expressions instanceof Expression) {
            $expressions = func_get_args();
        }

        return $this->where('$and', array_map(function (Expression $expression) {
            return $expression->toArray();
        }, $expressions));
    }

    /**
     * Selects the documents that fail all the query expressions in the array
     *
     * @param array|\Sokil\Mongo\Expression $expressions Array of Expression instances or comma delimited expression list
     * @return Expression
     */
    public function whereNor($expressions = null /**, ...**/)
    {
        if ($expressions instanceof Expression) {
            $expressions = func_get_args();
        }

        return $this->where(
            '$nor',
            array_map(
                function (Expression $expression) {
                    return $expression->toArray();
                },
                $expressions
            )
        );
    }

    public function whereNot(Expression $expression)
    {
        foreach ($expression->toArray() as $field => $value) {
            if (TypeChecker::isExpression($value) || TypeChecker::isRegex($value)) {
                // $not acceptable only for operators-expressions or regexps
                $this->where($field, array('$not' => $value));
            } else {
                // for single values use $ne
                $this->whereNotEqual($field, $value);
            }
        }

        return $this;
    }

    /**
     * Select documents where the value of a field divided by a divisor has the specified remainder (i.e. perform a modulo operation to select documents)
     *
     * @param string $field
     * @param int $divisor
     * @param int $remainder
     */
    public function whereMod($field, $divisor, $remainder)
    {
        $this->where($field, array(
            '$mod' => array((int) $divisor, (int) $remainder),
        ));

        return $this;
    }

    /**
     * Perform fulltext search
     *
     * @link https://docs.mongodb.org/manual/reference/operator/query/text/
     * @link https://docs.mongodb.org/manual/tutorial/specify-language-for-text-index/
     *
     * If a collection contains documents or embedded documents that are in different languages,
     * include a field named language in the documents or embedded documents and specify as its value the language
     * for that document or embedded document.
     *
     * The specified language in the document overrides the default language for the text index.
     * The specified language in an embedded document override the language specified in an enclosing document or
     * the default language for the index.
     *
     * Case Insensitivity:
     * @link https://docs.mongodb.org/manual/reference/operator/query/text/#text-operator-case-sensitivity
     *
     * Diacritic Insensitivity:
     * @link https://docs.mongodb.org/manual/reference/operator/query/text/#text-operator-diacritic-sensitivity
     *
     * @param $search A string of terms that MongoDB parses and uses to query the text index. MongoDB performs a
     *  logical OR search of the terms unless specified as a phrase.
     * @param $language Optional. The language that determines the list of stop words for the search and the
     *  rules for the stemmer and tokenizer. If not specified, the search uses the default language of the index.
     *  If you specify a language value of "none", then the text search uses simple tokenization
     *  with no list of stop words and no stemming.
     * @param bool|false $caseSensitive Allowed from v.3.2 A boolean flag to enable or disable case
     *  sensitive search. Defaults to false; i.e. the search defers to the case insensitivity of the text index.
     * @param bool|false $diacriticSensitive Allowed from v.3.2 A boolean flag to enable or disable diacritic
     *  sensitive search against version 3 text indexes. Defaults to false; i.e. the search defers to the diacritic
     *  insensitivity of the text index. Text searches against earlier versions of the text index are inherently
     *  diacritic sensitive and cannot be diacritic insensitive. As such, the $diacriticSensitive option has no
     *  effect with earlier versions of the text index.
     * @return $this
     */
    public function whereText(
        $search,
        $language = null,
        $caseSensitive = null,
        $diacriticSensitive = null
    ) {
        $this->_expression['$text'] = array(
            '$search' => $search,
        );

        if ($language) {
            $this->_expression['$text']['$language'] = $language;
        }

        // Version 3.2 feature
        if ($caseSensitive) {
            $this->_expression['$text']['$caseSensitive'] = (bool) $caseSensitive;
        }

        // Version 3.2 feature
        if ($diacriticSensitive) {
            $this->_expression['$text']['$diacriticSensitive'] = (bool) $diacriticSensitive;
        }

        return $this;
    }

    /**
     * Find document near points in flat surface
     *
     * @param string $field
     * @param float $longitude
     * @param float $latitude
     * @param int|array $distance distance from point in meters. Array distance
     *  allowed only in MongoDB 2.6
     * @return Expression
     */
    public function nearPoint($field, $longitude, $latitude, $distance)
    {
        $point = new \GeoJson\Geometry\Point(array(
            (float) $longitude,
            (float) $latitude
        ));

        $near = array(
            '$geometry' => $point->jsonSerialize(),
        );

        if (is_array($distance)) {
            if (!empty($distance[0])) {
                $near['$minDistance'] = (int) $distance[0];
            }
            if (!empty($distance[1])) {
                $near['$maxDistance'] = (int) $distance[1];
            }
        } else {
            $near['$maxDistance'] = (int) $distance;
        }

        $this->where($field, array('$near' => $near));

        return $this;
    }

    /**
     * Find document near points in spherical surface
     *
     * @param string $field
     * @param float $longitude
     * @param float $latitude
     * @param int|array $distance distance from point in meters. Array distance
     *  allowed only in MongoDB 2.6
     * @return Expression
     */
    public function nearPointSpherical($field, $longitude, $latitude, $distance)
    {
        $point = new Point(array(
            (float) $longitude,
            (float) $latitude
        ));

        $near = array(
            '$geometry' => $point->jsonSerialize(),
        );

        if (is_array($distance)) {
            if (!empty($distance[0])) {
                $near['$minDistance'] = (int) $distance[0];
            }
            if (!empty($distance[1])) {
                $near['$maxDistance'] = (int) $distance[1];
            }
        } else {
            $near['$maxDistance'] = (int) $distance;
        }

        $this->where($field, array('$nearSphere' => $near));

        return $this;
    }

    /**
     * Selects documents whose geospatial data intersects with a specified
     * GeoJSON object; i.e. where the intersection of the data and the
     * specified object is non-empty. This includes cases where the data
     * and the specified object share an edge. Uses spherical geometry.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/query/geoIntersects/
     *
     * @param string $field
     * @param Geometry $geometry
     * @return Expression
     */
    public function intersects($field, Geometry $geometry)
    {
        $this->where($field, array(
            '$geoIntersects' => array(
                '$geometry' => $geometry->jsonSerialize(),
            ),
        ));

        return $this;
    }

    /**
     * Selects documents with geospatial data that exists entirely within a specified shape.
     *
     * @link http://docs.mongodb.org/manual/reference/operator/query/geoWithin/
     * @param string $field
     * @param Geometry $geometry
     * @return Expression
     */
    public function within($field, Geometry $geometry)
    {
        $this->where($field, array(
            '$geoWithin' => array(
                '$geometry' => $geometry->jsonSerialize(),
            ),
        ));

        return $this;
    }

    /**
     * Select documents with geospatial data within circle defined
     * by center point and radius in flat surface
     *
     * @param string $field
     * @param float $longitude
     * @param float $latitude
     * @param float $radius
     * @return Expression
     */
    public function withinCircle($field, $longitude, $latitude, $radius)
    {
        $this->where($field, array(
            '$geoWithin' => array(
                '$center' => array(
                    array($longitude, $latitude),
                    $radius,
                ),
            ),
        ));

        return $this;
    }

    /**
     * Select documents with geospatial data within circle defined
     * by center point and radius in spherical surface
     *
     * To calculate distance in radians
     * @see http://docs.mongodb.org/manual/tutorial/calculate-distances-using-spherical-geometry-with-2d-geospatial-indexes/
     *
     * @param string $field
     * @param float $longitude
     * @param float $latitude
     * @param float $radiusInRadians in radians.
     * @return Expression
     */
    public function withinCircleSpherical($field, $longitude, $latitude, $radiusInRadians)
    {
        $this->where($field, array(
            '$geoWithin' => array(
                '$centerSphere' => array(
                    array($longitude, $latitude),
                    $radiusInRadians,
                ),
            ),
        ));

        return $this;
    }

    /**
     * Return documents that are within the bounds of the rectangle, according
     * to their point-based location data.
     *
     * Based on grid coordinates and does not query for GeoJSON shapes.
     *
     * Use planar geometry, so 2d index may be used but not required
     *
     * @param string $field
     * @param array $bottomLeftCoordinate Bottom left coordinate of box
     * @param array $upperRightCoordinate Upper right coordinate of box
     * @return Expression
     */
    public function withinBox($field, array $bottomLeftCoordinate, array $upperRightCoordinate)
    {
        $this->where($field, array(
            '$geoWithin' => array(
                '$box' => array(
                    $bottomLeftCoordinate,
                    $upperRightCoordinate,
                ),
            ),
        ));

        return $this;
    }

    /**
     * Return documents that are within the polygon, according
     * to their point-based location data.
     *
     * Based on grid coordinates and does not query for GeoJSON shapes.
     *
     * Use planar geometry, so 2d index may be used but not required
     *
     * @param string $field
     * @param array $points array of coordinates
     * @return Expression
     */
    public function withinPolygon($field, array $points)
    {
        $this->where($field, array(
            '$geoWithin' => array(
                '$polygon' => $points,
            ),
        ));

        return $this;
    }

    public function toArray()
    {
        return $this->_expression;
    }

    public function merge(Expression $expression)
    {
        $this->_expression = array_merge_recursive($this->_expression, $expression->toArray());
        return $this;
    }

    /**
     * Transform expression in different formats to canonical array form
     *
     * @param callable|array|Expression $mixed
     *
     * @return array
     *
     * @throws \Sokil\Mongo\Exception
     */
    public static function convertToArray($mixed)
    {
        // get expression from callable
        if (is_callable($mixed)) {
            $callable = $mixed;
            $mixed = new self();
            call_user_func($callable, $mixed);
        }

        // get expression array
        if ($mixed instanceof ArrayableInterface && $mixed instanceof self) {
            $mixed = $mixed->toArray();
        } elseif (!is_array($mixed)) {
            throw new Exception(sprintf(
                'Mixed must be instance of "\Sokil\Mongo\Expression", array or callable that accepts "\Sokil\Mongo\Expression", "%s" given',
                gettype($mixed) === "object" ? get_class($mixed) : gettype($mixed)
            ));
        }

        return $mixed;
    }
}
