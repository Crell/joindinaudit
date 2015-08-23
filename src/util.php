<?php

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;

/**
 * Returns a database connection.
 *
 * @return \Doctrine\DBAL\Connection
 * @throws \Doctrine\DBAL\DBALException
 */
function getDb() {
    static $conn;

    if (empty($conn)) {
        $config = new Configuration();

        $connectionParams = array(
          'url' => 'mysql://test:test@localhost/joindin',
        );

        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = DriverManager::getConnection($connectionParams, $config);
    }

    return $conn;
}

/**
 * Returns a new Guzzle client, configured for JoindIn.
 *
 * @return \GuzzleHttp\Client
 */
function getClient() {
    static $client;

    if (empty($client)) {
        $client = new Client(['headers' => ['X-Foo' => 'Bar']]);
    }

    return $client;
}
