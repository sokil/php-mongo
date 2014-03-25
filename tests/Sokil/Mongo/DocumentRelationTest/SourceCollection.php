<?php

namespace Sokil\Mongo\DocumentRelationTest;

class SourceCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null)
    {
        return '\Sokil\Mongo\DocumentRelationTest\SourceDocument';
    }
}