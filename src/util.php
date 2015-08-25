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

        $db_url = getenv('joindin_db');
        if (!$db_url) {
            die('No database information has been defined. Be sure to set the \'joindin_db\' environment variable to the Doctrine DB URL to use.');
        }

        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = DriverManager::getConnection(['url' => $db_url], $config);
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
