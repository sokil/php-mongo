<?php

namespace Sokil\Mongo\DocumentRelationTest;

class WheelsCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null)
    {
        return '\Sokil\Mongo\DocumentRelationTest\WheelDocument';
    }
}