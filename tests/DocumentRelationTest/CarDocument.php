<?php

namespace Sokil\Mongo\DocumentRelationTest;

class CarDocument extends \Sokil\Mongo\Document
{    
    public function relations()
    {
        return array(
            'engine'    => array(self::RELATION_HAS_ONE, 'engines', 'car_id'),
            'wheels'    => array(self::RELATION_HAS_MANY, 'wheels', 'car_id'),
            'drivers'   => array(self::RELATION_MANY_MANY, 'drivers', 'driver_id', true),
        );
    }
}