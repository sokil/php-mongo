<?php

namespace Sokil\Mongo;

use Sokil\Mongo\Document\InvalidDocumentException;
use Sokil\Mongo\SubDocumentTest\ProfileDocument;

class SubDocumentTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $collection;

    public function setUp()
    {
        $client = new Client();
        $database = $client->getDatabase('test');
        $this->collection = $database
            ->getCollection('phpmongo_test_collection')
            ->delete();
    }

    public function testSetSubDocument()
    {
        $profile = new ProfileDocument(array(
            'name' => 'USER_NAME',
            'birth' => array(
                'year' => 1984,
                'month' => 8,
                'day' => 10,
            )
        ));

        $document = new Document($this->collection);
        $document->set('profile', $profile);

        $this->assertSame('USER_NAME', $document->get('profile.name'));
    }

    public function testSetInvalidSubDocument()
    {
        $profile = new ProfileDocument(array(
            'name' => null,
        ));

        $document = new Document($this->collection);

        try {
            $document->set('profile', $profile);
            $this->fail('InvalidDocumentException must be thrown, but method call was successfull');
        } catch (InvalidDocumentException $e) {
            $this->assertSame(
                array(
                    'name' => array(
                        'required' => 'REQUIRED_FIELD_EMPTY_MESSAGE'
                    )
                ),
                $e->getDocument()->getErrors()
            );
        }
    }
}