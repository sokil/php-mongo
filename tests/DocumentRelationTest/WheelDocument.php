<?php

namespace Sokil\Mongo\DocumentRelationTest;

class WheelDocument extends \Sokil\Mongo\Document
{
    protected $data = array(
        'car_id' => null,
    );
    
    public function relations()
    {
        return array(
            'car'   => array(self::RELATION_BELONGS, 'cars', 'car_id'),
        );
    }
}