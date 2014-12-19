<?php

namespace Sokil\Mongo;

/**
 * @link http://docs.mongodb.org/manual/core/2dsphere/
 */
class DocumentGeoTest extends \PHPUnit_Framework_TestCase
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

    public function tearDown()
    {
        $this->collection->delete();
    }

    public function testSetGeometry()
    {
        $documentId = $this->collection
            ->createDocument()
            ->setGeometry(
                'location',
                new \GeoJson\Geometry\Point(
                    array(30.523400000000038, 50.4501)
                )
            )
            ->save()
            ->getId();

        $this->assertEquals(
            array(
                'type' => 'Point',
                'coordinates' => array(30.523400000000038, 50.4501)
            ),
            $this->collection->getDocument($documentId)->get('location')
        );
    }

    public function testSetPoint()
    {
        $documentId = $this->collection
            ->createDocument()
            ->setPoint('location', 30.523400000000038, 50.4501)
            ->save()
            ->getId();

        $this->assertEquals(
            array(
                'type' => 'Point',
                'coordinates' => array(30.523400000000038, 50.4501)
            ),
            $this->collection->getDocument($documentId)->get('location')
        );
    }

    public function testSetLineString()
    {
        $documentId = $this->collection
            ->createDocument()
            ->setLineString('location', array(
                array(30.523400000000038, 50.4501),
                array(24.012228, 49.831485),
                array(36.230376, 49.993499),
            ))
            ->save()
            ->getId();

        $this->assertEquals(
            array(
                'type' => 'LineString',
                'coordinates' => array(
                    array(30.523400000000038, 50.4501),
                    array(24.012228, 49.831485),
                    array(36.230376, 49.993499),
                )
            ),
            $this->collection->getDocument($documentId)->get('location')
        );
    }

    public function testSetPolygon_ValidLineRing()
    {
        $documentId = $this->collection
            ->createDocument()
            ->setPolygon('location', array(
                // line ring 1
                array(
                    array(24.012228, 49.831485), // Lviv
                    array(36.230376, 49.993499), // Harkiv
                    array(34.174927, 45.035993), // Simferopol
                    array(24.012228, 49.831485), // Lviv
                ),
                // line ring 2
                array(
                    array(34.551416, 49.588264), // Poltava
                    array(32.049226, 49.431181), // Cherkasy
                    array(35.139561, 47.838796), // Zaporizhia
                    array(34.551416, 49.588264), // Poltava
                ),
            ))
            ->save()
            ->getId();

        $this->assertEquals(
            array(
                'type' => 'Polygon',
                'coordinates' => array(
                    // line ring 1
                    array(
                        array(24.012228, 49.831485), // Lviv
                        array(36.230376, 49.993499), // Harkiv
                        array(34.174927, 45.035993), // Simferopol
                        array(24.012228, 49.831485), // Lviv
                    ),
                    // line ring 2
                    array(
                        array(34.551416, 49.588264), // Poltava
                        array(32.049226, 49.431181), // Cherkasy
                        array(35.139561, 47.838796), // Zaporizhia
                        array(34.551416, 49.588264), // Poltava
                    ),
                )
            ),
            $this->collection->getDocument($documentId)->get('location')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage LinearRing requires the first and last positions to be equivalent
     */
    public function testSetPolygon_LineRingNotClosed()
    {
        $documentId = $this->collection
            ->createDocument()
            ->setPolygon('location', array(
                // line ring 1
                array(
                    array(24.012228, 49.831485), // Lviv
                    array(36.230376, 49.993499), // Harkiv
                    array(34.174927, 45.035993), // Simferopol
                    array(24.012228, 49.831485), // Lviv
                ),
                // line ring 2
                array(
                    array(34.551416, 49.588264), // Poltava
                    array(32.049226, 49.431181), // Cherkasy
                    array(35.139561, 47.838796), // Zaporizhia
                    array(24.012228, 49.831485), // Lviv <---- ERROR
                ),
            ))
            ->save()
            ->getId();
    }

    public function testSetMultiPoint()
    {
        $documentId = $this->collection
            ->createDocument()
            ->setMultiPoint('location', array(
                array(24.012228, 49.831485), // Lviv
                array(36.230376, 49.993499), // Harkiv
                array(34.174927, 45.035993), // Simferopol
                array(24.012228, 49.831485), // Lviv
            ))
            ->save()
            ->getId();

        $this->assertEquals(
            array(
                'type' => 'MultiPoint',
                'coordinates' => array(
                    array(24.012228, 49.831485), // Lviv
                    array(36.230376, 49.993499), // Harkiv
                    array(34.174927, 45.035993), // Simferopol
                    array(24.012228, 49.831485), // Lviv
                )
            ),
            $this->collection->getDocument($documentId)->get('location')
        );
    }

    public function testSetMultiLineString()
    {
        $documentId = $this->collection
            ->createDocument()
            ->setMultiLineString('location', array(
                // line string 1
                array(
                    array(34.551416, 49.588264), // Poltava
                    array(32.049226, 49.431181), // Cherkasy
                    array(35.139561, 47.838796), // Zaporizhia
                ),
                // line string 2
                array(
                    array(24.012228, 49.831485), // Lviv
                    array(36.230376, 49.993499), // Harkiv
                    array(34.174927, 45.035993), // Simferopol
                )
            ))
            ->save()
            ->getId();

        $this->assertEquals(
            array(
                'type' => 'MultiLineString',
                'coordinates' => array(
                    // line string 1
                    array(
                        array(34.551416, 49.588264), // Poltava
                        array(32.049226, 49.431181), // Cherkasy
                        array(35.139561, 47.838796), // Zaporizhia
                    ),
                    // line string 2
                    array(
                        array(24.012228, 49.831485), // Lviv
                        array(36.230376, 49.993499), // Harkiv
                        array(34.174927, 45.035993), // Simferopol
                    )
                )
            ),
            $this->collection->getDocument($documentId)->get('location')
        );
    }

    public function testSetMultiPolygon_ValidLineRing()
    {
        $documentId = $this->collection
            ->createDocument()
            ->setMultyPolygon('location', array(
                // polygon 1
                array(
                    // line ring 1
                    array(
                        array(24.012228, 49.831485), // Lviv
                        array(36.230376, 49.993499), // Harkiv
                        array(34.174927, 45.035993), // Simferopol
                        array(24.012228, 49.831485), // Lviv
                    ),
                    // line ring 2
                    array(
                        array(34.551416, 49.588264), // Poltava
                        array(32.049226, 49.431181), // Cherkasy
                        array(35.139561, 47.838796), // Zaporizhia
                        array(34.551416, 49.588264), // Poltava
                    ),
                ),
                // polygon 2
                array(
                    // line ring 1
                    array(
                        array(24.012228, 49.831485), // Lviv
                        array(36.230376, 49.993499), // Harkiv
                        array(34.174927, 45.035993), // Simferopol
                        array(24.012228, 49.831485), // Lviv
                    ),
                    // line ring 2
                    array(
                        array(34.551416, 49.588264), // Poltava
                        array(32.049226, 49.431181), // Cherkasy
                        array(35.139561, 47.838796), // Zaporizhia
                        array(34.551416, 49.588264), // Poltava
                    ),
                ),
            ))
            ->save()
            ->getId();

        $this->assertEquals(
            array(
                'type' => 'MultiPolygon',
                'coordinates' => array(
                    // polygon 1
                    array(
                        // line ring 1
                        array(
                            array(24.012228, 49.831485), // Lviv
                            array(36.230376, 49.993499), // Harkiv
                            array(34.174927, 45.035993), // Simferopol
                            array(24.012228, 49.831485), // Lviv
                        ),
                        // line ring 2
                        array(
                            array(34.551416, 49.588264), // Poltava
                            array(32.049226, 49.431181), // Cherkasy
                            array(35.139561, 47.838796), // Zaporizhia
                            array(34.551416, 49.588264), // Poltava
                        ),
                    ),
                    // polygon 2
                    array(
                        // line ring 1
                        array(
                            array(24.012228, 49.831485), // Lviv
                            array(36.230376, 49.993499), // Harkiv
                            array(34.174927, 45.035993), // Simferopol
                            array(24.012228, 49.831485), // Lviv
                        ),
                        // line ring 2
                        array(
                            array(34.551416, 49.588264), // Poltava
                            array(32.049226, 49.431181), // Cherkasy
                            array(35.139561, 47.838796), // Zaporizhia
                            array(34.551416, 49.588264), // Poltava
                        ),
                    ),
                )
            ),
            $this->collection->getDocument($documentId)->get('location')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage LinearRing requires the first and last positions to be equivalent
     */
    public function testSetMultiPolygon_LineRingNotClosed()
    {
        $documentId = $this->collection
            ->createDocument()
            ->setMultyPolygon('location', array(
                // polygon 1
                array(
                    // line ring 1
                    array(
                        array(24.012228, 49.831485), // Lviv
                        array(36.230376, 49.993499), // Harkiv
                        array(34.174927, 45.035993), // Simferopol
                        array(24.012228, 49.831485), // Lviv
                    ),
                    // line ring 2
                    array(
                        array(34.551416, 49.588264), // Poltava
                        array(32.049226, 49.431181), // Cherkasy
                        array(35.139561, 47.838796), // Zaporizhia
                        array(34.551416, 49.588264), // Poltava
                    ),
                ),
                // polygon 2
                array(
                    // line ring 1
                    array(
                        array(24.012228, 49.831485), // Lviv
                        array(36.230376, 49.993499), // Harkiv
                        array(34.174927, 45.035993), // Simferopol
                        array(24.012228, 49.831485), // Lviv
                    ),
                    // line ring 2
                    array(
                        array(34.551416, 49.588264), // Poltava
                        array(32.049226, 49.431181), // Cherkasy
                        array(35.139561, 47.838796), // Zaporizhia
                        array(24.012228, 49.831485), // Lviv <---- ERROR
                    ),
                ),
            ))
            ->save()
            ->getId();
    }

    public function testSetGeometryCollection()
    {

    }
}