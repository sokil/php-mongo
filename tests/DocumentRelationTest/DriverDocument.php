<?php

namespace Sokil\Mongo\DocumentRelationTest;

class DriverDocument extends \Sokil\Mongo\Document
{    
    public function relations()
    {
        return array(
            'cars'    => array(self::RELATION_MANY_MANY, 'cars', 'driver_id'),
        );
    }
}