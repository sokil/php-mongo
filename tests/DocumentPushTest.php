<?php

namespace Sokil\Mongo;

class DocumentPushTest extends \PHPUnit_Framework_TestCase
{
    const FIELD_NAME_NEW = 'new';
    const FIELD_NAME_SCALAR = 'scalar';
    const FIELD_NAME_SUB_DOCUMENT = 'sub_document';
    const FIELD_NAME_LIST = 'list';

    private $initialDocument = array(
        self::FIELD_NAME_SCALAR => 'scalar',
        self::FIELD_NAME_SUB_DOCUMENT => array('param1' => 'value', 'param2' => 'value'),
        self::FIELD_NAME_LIST => array('existed1', 'existed2'),
    );

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
        $mongoId = new \MongoId();

        $stdClass = new \stdClass();

        $structure = new Structure();
        $structure->mergeUnmodified(array('param' => 'value'));

        return array(
            'int' => array(
                1, 2, array(1, 2)
            ),
            'string' => array(
                'string1', 'string2', array('string1', 'string2'),
            ),
            'empty_stdclass' => array(
                $stdClass, $stdClass, array(array(), array()),
            ),
            'MongoId' => array(
                $mongoId, $mongoId, array($mongoId, $mongoId),
            ),
            'list' => array(
                array(1), array(2), array(array(1), array(2)),
            ),
            'subdocument' => array(
                array(
                    'subdoc' => 1,
                ),
                array(
                    'subdoc' => 2,
                ),
                array(
                    array(
                        'subdoc' => 1,
                    ),
                    array(
                        'subdoc' => 2,
                    ),
                ),
            ),
            'structure' => array(
                $structure,
                $structure,
                array(
                    array('param' => 'value'),
                    array('param' => 'value'),
                )
            ),
        );
    }

    /**
     * Abstract test
     *
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     * @param int $fieldName name of field, where values pushed: one of self::FIELD_NAME_*
     * @param bool $isDocumentSaved is document already stored to db before push
     */
    private function doPushTest(
        $value1,
        $value2,
        $expectedList,
        $fieldName,
        $isDocumentSaved
    ) {
        // create document
        $doc = $this
            ->collection
            ->createDocument($this->initialDocument);

        // test push to saved or new document
        if ($isDocumentSaved) {
            $doc->save();
        }

        // prepare expected value
        switch ($fieldName) {
            case self::FIELD_NAME_SCALAR:
                $expectedList = array_merge(
                    array($this->initialDocument[$fieldName]),
                    $expectedList
                );
                break;
            case self::FIELD_NAME_LIST:
                $expectedList = array_merge(
                    $this->initialDocument[$fieldName],
                    $expectedList
                );
                break;
            case self::FIELD_NAME_SUB_DOCUMENT:
                $expectedList = array_merge(
                    array($this->initialDocument[$fieldName]),
                    $expectedList
                );
                break;
        }

        // push single to empty
        $doc
            ->push($fieldName, $value1)
            ->push($fieldName, $value2);

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
     *
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
    public function testPushToNewFieldOnNewDocument(
        $value1,
        $value2,
        $expectedList
    ) {
        $this->doPushTest(
            $value1,
            $value2,
            $expectedList,
            self::FIELD_NAME_NEW,
            false
        );
    }

    /**
     * @dataProvider fieldValuesDataProvider
     *
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
    public function testPushToNewFieldOnExistedDocument(
        $value1,
        $value2,
        $expectedList
    ) {
        $this->doPushTest(
            $value1,
            $value2,
            $expectedList,
            self::FIELD_NAME_NEW,
            true
        );
    }

    /**
     * @dataProvider fieldValuesDataProvider
     *
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
    public function testPushToScalarFieldOnNewDocument(
        $value1,
        $value2,
        $expectedList
    ) {
        $this->doPushTest(
            $value1,
            $value2,
            $expectedList,
            self::FIELD_NAME_SCALAR,
            false
        );
    }

    /**
     * @dataProvider fieldValuesDataProvider
     *
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
    public function testPushToScalarFieldOnExistedDocument(
        $value1,
        $value2,
        $expectedList
    ) {
        $this->doPushTest(
            $value1,
            $value2,
            $expectedList,
            self::FIELD_NAME_SCALAR,
            true
        );
    }

    /**
     * @dataProvider fieldValuesDataProvider
     *
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
//    public function testPushToSubDocumentFieldOnNewDocument(
//        $value1,
//        $value2,
//        $expectedList
//    ) {
//        $this->doPushTest(
//            $value1,
//            $value2,
//            $expectedList,
//            self::FIELD_NAME_SUB_DOCUMENT,
//            false
//        );
//    }

    /**
     * @dataProvider fieldValuesDataProvider
     *
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
//    public function testPushToSubDocumentFieldOnExistedDocument(
//        $value1,
//        $value2,
//        $expectedList
//    ) {
//        $this->doPushTest(
//            $value1,
//            $value2,
//            $expectedList,
//            self::FIELD_NAME_SUB_DOCUMENT,
//            true
//        );
//    }

    /**
     * @dataProvider fieldValuesDataProvider
     *
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
    public function testPushToListFieldOnNewDocument(
        $value1,
        $value2,
        $expectedList
    ) {
        $this->doPushTest(
            $value1,
            $value2,
            $expectedList,
            self::FIELD_NAME_LIST,
            false
        );
    }

    /**
     * @dataProvider fieldValuesDataProvider
     *
     * @param mixed $value1 first value to push
     * @param mixed $value2 second value to push
     * @param array $expectedList expected list, stored in db
     */
    public function testPushToListFieldOnExistedDocument(
        $value1,
        $value2,
        $expectedList
    ) {
        $this->doPushTest(
            $value1,
            $value2,
            $expectedList,
            self::FIELD_NAME_LIST,
            true
        );
    }
}