<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\Schema\Schema;

/**
 * Initializes the database with a fresh, empty schema.
 */
function init()
{
    $conn = getDb();

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager $sm */
    $sm = $conn->getSchemaManager();

    if ($sm->tablesExist('talk')) {
        $sm->dropTable('talk');
    }

    $schema = new Schema();
    $table = $schema->createTable('event');
    $table->addColumn("url_friendly_name", "string", ["length" => 128]);
    $table->addColumn("name", "string", ["length" => 128]);
    $table->addColumn("start_date", "date");
    $table->addColumn("end_date", "date");
    $table->addColumn("tz_continent", "string", ["length" => 64]);
    $table->addColumn("tz_place", "string", ["length" => 64]);
    $table->addColumn("location", "string", ["length" => 64]);
    $table->addColumn("talks_count", "integer", ["unsigned" => true]);
    $table->setPrimaryKey(["url_friendly_name"]);
//    $table->addUniqueIndex(["username"]);
//    $schema->createSequence("users_seq");
    $sm->dropAndCreateTable($table);

    $schema = new Schema();
    $table = $schema->createTable('talk');
    $table->addColumn("url_friendly_talk_title", "string", ["length" => 128]);
    $table->addColumn("event", "string", ["length" => 64]);
    $table->addColumn("talk_title", "string", ["length" => 128]);
    $table->addColumn("type", "string", ["length" => 128]);
    $table->addColumn("duration", "integer", ["unsigned" => true]);
    $table->addColumn("speaker", "string", ["length" => 128]);
    $table->addColumn("average_rating", "integer", ["unsigned" => true]);
    $table->setPrimaryKey(["url_friendly_talk_title", 'event']);
    $table->addForeignKeyConstraint('event', ['event'], ['url_friendly_name']);
    $sm->dropAndCreateTable($table);
}

init();