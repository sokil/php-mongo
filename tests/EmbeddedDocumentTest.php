<?php

namespace Sokil\Mongo;

use Sokil\Mongo\Document\InvalidDocumentException;
use Sokil\Mongo\EmbeddedDocumentTest\ProfileDocument;

class EmbeddedDocumentTest extends \PHPUnit_Framework_TestCase
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

    public function testSetEmbeddedDocument()
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

    public function testSetInvalidEmbeddedDocument()
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

    public function testPushEmbeddedDocument()
    {
        $document = new Document($this->collection);

        $document->push('profiles', new ProfileDocument(array(
            'name' => 'USER_NAME1',
        )));
        $document->push('profiles', new ProfileDocument(array(
            'name' => 'USER_NAME2',
        )));

        $this->assertSame(
            array(
                array('name' => 'USER_NAME1'),
                array('name' => 'USER_NAME2'),
            ),
            $document->get('profiles')
        );
    }

    public function testPushInvalidEmbeddedDocument()
    {
        $document = new Document($this->collection);

        try {
            $document->push('profiles', new ProfileDocument(array(
                'name' => null,
            )));
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