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
        $documentId = $this->collection
            ->createDocument()
            ->setGeometryCollection('location', array(
                // point
                new \GeoJson\Geometry\Point(array(30.523400000000038, 50.4501)),
                // line string
                new \GeoJson\Geometry\LineString(array(
                    array(30.523400000000038, 50.4501),
                    array(24.012228, 49.831485),
                    array(36.230376, 49.993499),
                )),
                // polygon
                new \GeoJson\Geometry\Polygon(array(
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
                )),
            ))
            ->save()
            ->getId();

        $this->assertEquals(
            array(
                'type' => 'GeometryCollection',
                'geometries' => array(
                    array(
                        'type' => 'Point',
                        'coordinates' => array(30.523400000000038, 50.4501)
                    ),
                    array(
                        'type' => 'LineString',
                        'coordinates' => array(
                            array(30.523400000000038, 50.4501),
                            array(24.012228, 49.831485),
                            array(36.230376, 49.993499),
                        )
                    ),
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
                ),
            ),
            $this->collection->getDocument($documentId)->get('location')
        );
    }

    public function testEnsure2dSphereIndex()
    {
        $this->collection->ensure2dSphereIndex('location');

        $document1Id = $this->collection
            ->createDocument()
            ->setPoint('location', 24.012228, 49.831485)
            ->save()
            ->getId();

        $document2Id = $this->collection
            ->createDocument()
            ->setPoint('location', 34.551416, 49.588264)
            ->save()
            ->getId();

        $document = $this->collection
            ->find()
            ->nearPoint('location', 34.551, 49.588, 200)
            ->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($document2Id, $document->getId());
    }

    public function testExpressionNearPointArrayDistabce()
    {
        // this feature allowed only in MongoDB 2.6
        if(version_compare($this->collection->getDatabase()->getClient()->getDbVersion(), '2.6', '<')) {
            return;
        }

        $this->collection->ensure2dSphereIndex('location');

        $document1Id = $this->collection
            ->createDocument()
            ->setPoint('location', 24.012228, 49.831485)
            ->save()
            ->getId();

        $document2Id = $this->collection
            ->createDocument()
            ->setPoint('location', 34.551416, 49.588264)
            ->save()
            ->getId();

        // point before min-max range
        $document = $this->collection
            ->find()
            ->nearPoint('location', 34.551, 49.588, array(null, 5))
            ->findOne();

        $this->assertEmpty($document);

        // point before min-max range
        $document = $this->collection
            ->find()
            ->nearPoint('location', 34.551, 49.588, array(1, 5))
            ->findOne();

        $this->assertEmpty($document);

        // point in min-max range
        $document = $this->collection
            ->find()
            ->nearPoint('location', 34.551, 49.588, array(5, 50))
            ->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($document2Id, $document->getId());

        // point after min-max range
        $document = $this->collection
            ->find()
            ->nearPoint('location', 34.551, 49.588, array(50, 500))
            ->findOne();

        $this->assertEmpty($document);

        // point autside min range
        $document = $this->collection
            ->find()
            ->nearPoint('location', 34.551, 49.588, array(50, null))
            ->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($document1Id, $document->getId());
    }

    public function testExpressionNearPointSpherical()
    {
        $this->collection->ensure2dSphereIndex('location');

        $document1Id = $this->collection
            ->createDocument()
            ->setPoint('location', 24.012228, 49.831485)
            ->save()
            ->getId();

        $document2Id = $this->collection
            ->createDocument()
            ->setPoint('location', 34.551416, 49.588264)
            ->save()
            ->getId();

        $document = $this->collection
            ->find()
            ->nearPointSpherical('location', 34.551, 49.588, 200)
            ->findOne();

        $this->assertNotEmpty($document);
        $this->assertEquals($document2Id, $document->getId());
    }

    public function testExpressionIntersects()
    {
        $this->collection->ensure2dSphereIndex('link');

        $link1Id = $this->collection
            ->createDocument()
            ->setLineString('link', array(
                array(24.0122356, 49.8326891), // Lviv
                array(24.717129, 48.9117731), // Ivano-Frankivsk
            ))
            ->save()
            ->getId();

        $link2Id = $this->collection
            ->createDocument()
            ->setLineString('link', array(
                array(28.6737175, 50.2678865), // Gytomyr
                array(34.5572385, 49.6020445), // Poltava
            ))
            ->save()
            ->getId();

        $crossedLink = $this->collection
            ->find()
            ->intersects('link', new \GeoJson\Geometry\LineString(array(
                array(30.5326905, 50.4020355), // Kyiv
                array(34.1092134, 44.946798), // Simferopol
            )))
            ->findOne();

        $this->assertNotEmpty($crossedLink);
        $this->assertEquals($link2Id, $crossedLink->getId());
    }

    public function testExpressionWithin()
    {
        $this->collection->ensure2dSphereIndex('point');

        $point1Id = $this->collection
            ->createDocument()
            ->setPoint('point', 30.5326905, 50.4020355) // Kyiv
            ->save()
            ->getId();

        $point2Id = $this->collection
            ->createDocument()
            ->setPoint('point', 28.4696339, 49.234734) // Vinnytsya
            ->save()
            ->getId();

        $point = $this->collection
            ->find()
            ->within('point', new \GeoJson\Geometry\Polygon(array(
                array(
                    array(24.0122356, 49.8326891), // Lviv
                    array(24.717129, 48.9117731), // Ivano-Frankivsk
                    array(34.1092134, 44.946798), // Simferopol
                    array(34.5572385, 49.6020445), // Poltava
                    array(24.0122356, 49.8326891), // Lviv
                )
            )))
            ->findOne();

        $this->assertNotEmpty($point);
        $this->assertEquals($point2Id, $point->getId());
    }

    public function testExpressionWithinCircle()
    {
        $this->collection->ensure2dSphereIndex('point');

        $point1Id = $this->collection
            ->createDocument()
            ->setPoint('point', 30.5326905, 50.4020355) // Kyiv
            ->save()
            ->getId();

        $point2Id = $this->collection
            ->createDocument()
            ->setPoint('point', 28.4696339, 49.234734) // Vinnytsya
            ->save()
            ->getId();

        $point = $this->collection
            ->find()
            ->withinCircle('point', 28.46963, 49.2347, 1)
            ->findOne();

        $this->assertNotEmpty($point);
        $this->assertEquals($point2Id, $point->getId());
    }

    public function testExpressionWithinCircleSpherical()
    {
        $this->collection->ensure2dSphereIndex('point');

        $point1Id = $this->collection
            ->createDocument()
            ->setPoint('point', 30.5326905, 50.4020355) // Kyiv
            ->save()
            ->getId();

        $point2Id = $this->collection
            ->createDocument()
            ->setPoint('point', 28.4696339, 49.234734) // Vinnytsya
            ->save()
            ->getId();

        $point = $this->collection
            ->find()
            ->withinCircleSpherical('point', 28.46963, 49.2347, 0.001)
            ->findOne();

        $this->assertNotEmpty($point);
        $this->assertEquals($point2Id, $point->getId());
    }

    public function testExpressionWithinBox()
    {
        $this->collection->ensure2dIndex('point');

        $point1Id = $this->collection
            ->createDocument()
            ->setLegacyPoint('point', 5, 4)
            ->save()
            ->getId();

        $point2Id = $this->collection
            ->createDocument()
            ->setLegacyPoint('point', 50, 40)
            ->save()
            ->getId();

        $point = $this->collection
            ->find()
            ->withinBox('point', array(0, 0), array(10, 10))
            ->findOne();

        $this->assertNotEmpty($point);
        $this->assertEquals($point1Id, $point->getId());
    }
}