<?php

namespace Sokil\Mongo\EmbeddedDocumentTest;

use Sokil\Mongo\Structure;

class ProfileDocument extends Structure
{
    protected $schema = array(
        'name' => null,
        'birth' => array(
            'year' => null,
            'month' => null,
            'day' => null,
        )
    );

    public function rules()
    {
        return array(
            array('name', 'required', 'message' => 'REQUIRED_FIELD_EMPTY_MESSAGE'),
        );
    }
}