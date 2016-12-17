<?php

namespace Sokil\Mongo;

use Sokil\Mongo\Document\InvalidOperationException;

class DocumentAddToTest extends \PHPUnit_Framework_TestCase
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
            // sub document
            // set
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
            : [];

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
     * @deprecated
     */
    public function testAddToSet_AddSecondSubDocumentFromStructure()
    {
        // create document with field which contains array of sub documents
        // with one sub document
        $doc = $this->collection->createDocument(array(
            'param' => array(
                array('sub1' => 1),
            ),
        ));
        $doc->save();
        $docId = $doc->getId();

        // add second sub document to array of sub documents from Structure
        $structure = new Structure();
        $structure->set('sub2', 2);
        $doc->addToSet('param', $structure);

        $doc->save();
        $doc = $this->collection->getDocumentDirectly($docId);
        $this->assertEquals(
            array(
                array('sub1' => 1),
                array('sub2' => 2),
            ),
            $doc->param
        );
    }
}