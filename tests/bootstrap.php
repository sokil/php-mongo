<?php

/* @var $loader \Composer\Autoload\ClassLoader */
$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->addPsr4('Sokil\\Mongo\\', __DIR__);

define('MONGO_DSN', 'mongodb://127.0.0.1');

// check mongo connection presence
$client = new \Sokil\Mongo\Client(MONGO_DSN);
try {
    $client->getMongoClient()->connect();
} catch (MongoConnectionException $e) {
    die('Error connecting to mongo server');
}

