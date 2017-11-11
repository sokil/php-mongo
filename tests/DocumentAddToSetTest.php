<?php

namespace Sokil\Mongo;

use Sokil\Mongo\Document\InvalidOperationException;
use PHPUnit\Framework\TestCase;

class DocumentAddToSetTest extends TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp()
    {
        $client = new Client(getenv('PHPMONGO_DSN') ? getenv('PHPMONGO_DSN') : null);
        $database = $client->getDatabase('test');
        $this->collection = $database
            ->getCollection('phpmongo_test_collection')
            ->delete();
    }

    public function tearDown()
    {
        $this->collection->delete();
    }

    public function fieldValuesDataProvider()
    {
        $mongoId1 = new \MongoId();
        $mongoId2 = new \MongoId();

        $stdClass = new \stdClass();

        $structure1 = new Structure(array('param' => 'value1'), true);
        $structure2 = new Structure(array('param' => 'value2'), true);

        return array(
            // new field
            'newField_int' => array(
                null, 1, 2, array(1, 2)
            ),
            'newField_string' => array(
                null, 'string1', 'string2', array('string1', 'string2'),
            ),
            'newField_emptyStdclass' => array(
                null, $stdClass, $stdClass, array(array()),
            ),
            'newField_MongoId' => array(
                null, $mongoId1, $mongoId2, array($mongoId1, $mongoId2),
            ),
            'newField_list' => array(
                null, array(1), array(2), array(array(1), array(2)),
            ),
            'newField_list_of_list' => array(
                null, array(array(1)), array(array(2)), array(array(array(1)), array(array(2))),
            ),
            'newField_subdocument' => array(
                null,
                array('subdoc' => 1),
                array('subdoc' => 2),
                array(
                    array('subdoc' => 1),
                    array('subdoc' => 2),
                ),
            ),
            'newField_structure' => array(
                null,
                $structure1,
                $structure2,
                array(
                    array('param' => 'value1'),
                    array('param' => 'value2'),
                )
            ),
            // scalar field
            'scalarField_int' => array(
                2, 1, 2, array(2, 1)
            ),
            'scalarField_string' => array(
                'string2', 'string1', 'string2', array('string2', 'string1'),
            ),
            'scalarField_emptyStdclass' => array(
                2, $stdClass, $stdClass, array(2, array()),
            ),
            'scalarField_MongoId' => array(
                2, $mongoId1, $mongoId2, array(2, $mongoId1, $mongoId2),
            ),
            'scalarField_list' => array(
                2, array(1), array(2), array(2, array(1), array(2)),
            ),
            'scalarField_list_of_list' => array(
                2,
                array(array(1)),
                array(array(2)),
                array(2, array(array(1)), array(array(2))),
            ),
            'scalarField_subdocument' => array(
                2,
                array('subdoc' => 1),
                array('subdoc' => 2),
                array(
                    2,
                    array('subdoc' => 1),
                    array('subdoc' => 2),
                ),
            ),
            'scalarField_structure' => array(
                2,
                $structure1,
                $structure2,
                array(
                    2,
                    array('param' => 'value1'),
                    array('param' => 'value2'),
                )
            ),
            // set
            'setField_int' => array(
                array(2, 4), 1, 2, array(2, 4, 1)
            ),
            'setField_string' => array(
                array('string2', 'string4'), 'string1', 'string2', array('string2', 'string4', 'string1'),
            ),
            'setField_emptyStdclass' => array(
                array(2, 4), $stdClass, $stdClass, array(2, 4, array()),
            ),
            'setField_MongoId' => array(
                array(2, 4), $mongoId1, $mongoId2, array(2, 4, $mongoId1, $mongoId2),
            ),
            'setField_list' => array(
                array(2, 4), array(1), array(2), array(2, 4, array(1), array(2)),
            ),
            'setField_list_of_list' => array(
                array(2, 4),
                array(array(1)),
                array(array(2)),
                array(2, 4, array(array(1)), array(array(2))),
            ),
            'setField_subdocument' => array(
                array(2, 4),
                array('subdoc' => 1),
                array('subdoc' => 2),
                array(
                    2,
                    4,
                    array('subdoc' => 1),
                    array('subdoc' => 2),
                ),
            ),
            'setField_structure' => array(
                array(2, array('param' => 'value4')),
                $structure1,
                $structure2,
                array(
                    2,
                    array('param' => 'value4'),
                    array('param' => 'value1'),
                    array('param' => 'value2'),
                )
            ),
        );
    }

    /**
     * Abstract test
     *
     * @param mixed $initialValue
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     * @param bool $isDocumentSaved is document already stored to db before push
     */
    private function doAddToSetTest(
        $initialValue,
        $value1,
        $value2,
        $expectedList,
        $isDocumentSaved
    ) {
        $fieldName = 'field';

        // create document
        $document = $initialValue
            ? array($fieldName => $initialValue)
            : array();

        $doc = $this
            ->collection
            ->createDocument($document);

        // test push to saved or new document
        if ($isDocumentSaved) {
            $doc->save();
        }

        // push single to empty
        $doc
            ->addToSet($fieldName, $value1)
            ->addToSet($fieldName, $value2);

        // test document in identity map after push before save
        $this->assertEquals(
            $expectedList,
            $doc->get($fieldName)
        );

        $doc->save();

        // test document in identity map after push after save
        $this->assertEquals(
            $expectedList,
            $this->collection->getDocument($doc->getId())->get($fieldName)
        );

        // test document in db
        $this->assertEquals(
            $expectedList,
            $this->collection->getDocumentDirectly($doc->getId())->get($fieldName)
        );
    }

    /**
     * @dataProvider fieldValuesDataProvider
     * @param mixed $initialField
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
    public function testAddToSetOnNewDocument(
        $initialField,
        $value1,
        $value2,
        $expectedList
    ) {
        $this->doAddToSetTest(
            $initialField,
            $value1,
            $value2,
            $expectedList,
            false
        );
    }

    /**
     * @dataProvider fieldValuesDataProvider
     *
     * @param mixed $initialField
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
    public function testAddToSetOnExistedDocument(
        $initialField,
        $value1,
        $value2,
        $expectedList
    ) {
        $this->doAddToSetTest(
            $initialField,
            $value1,
            $value2,
            $expectedList,
            true
        );
    }

    /**
     * @expectedException \Sokil\Mongo\Document\InvalidOperationException
     * @expectedExceptionMessage The field "field" must be an array but is of type Object
     */
    public function testAddToSetOnSubDocumentField() {
        $this->doAddToSetTest(
            array('sub' => 42),
            1,
            2,
            null, // exceprion expected
            true
        );
    }
}