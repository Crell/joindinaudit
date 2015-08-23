<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;

function init() {

    $config = new Configuration();

    $connectionParams = array(
      'url' => 'mysql://test:test@localhost/joindin',
    );

    /** @var \Doctrine\DBAL\Connection $conn */
    $conn = DriverManager::getConnection($connectionParams, $config);

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager $sm */
    $sm = $conn->getSchemaManager();
    $schema = new Schema();

    $table = $schema->createTable('event');
    $table->addColumn("stub", "string", ["length" => 128]);
    $table->addColumn("name", "string", ["length" => 128]);
    $table->addColumn("start_date", "date");
    $table->addColumn("end_date", "date");
    $table->addColumn("tz_continent", "string", ["length" => 64]);
    $table->addColumn("tz_place", "string", ["length" => 64]);
    $table->addColumn("location", "string", ["length" => 64]);
    $table->addColumn("talks_count", "integer", ["unsigned" => true]);

    $table->setPrimaryKey(["stub"]);
//    $table->addUniqueIndex(["username"]);
//    $schema->createSequence("users_seq");
    $sm->dropAndCreateTable($table);

    /*
    $table = $schema->createTable('messages');
    $table->addColumn("id", "integer", ["unsigned" => true]);
    $table->addColumn("author", "string", ["length" => 32]);
    $table->addColumn("parent", "integer", ["unsigned" => true]);
    $table->addColumn("message", "string", ["length" => 256]);
    $table->setPrimaryKey(["id"]);
    $sm->dropAndCreateTable($table);
*/
}

init();