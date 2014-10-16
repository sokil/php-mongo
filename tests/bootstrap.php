<?php

/* @var $loader \Composer\Autoload\ClassLoader */
$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->addPsr4('Sokil\\Mongo\\', __DIR__);

// check mongo connection presence
$client = new \Sokil\Mongo\Client();
try {
    $client->getMongoClient()->connect();
} catch (MongoConnectionException $e) {
    die('Error connecting to mongo server');
}

