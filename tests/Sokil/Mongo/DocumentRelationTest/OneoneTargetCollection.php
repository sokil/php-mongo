<?php

namespace Sokil\Mongo\DocumentRelationTest;

class OneoneTargetCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null)
    {
        return '\Sokil\Mongo\DocumentRelationTest\OneoneTargetDocument';
    }
}