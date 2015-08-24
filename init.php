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
    if ($sm->tablesExist('first_appearance')) {
        $sm->dropTable('first_appearance');
    }

    $schema = new Schema();
    $table = $schema->createTable('event');
    $table->addColumn("uri", "string", ["length" => 128]);
    $table->addColumn("url_friendly_name", "string", ["length" => 128]);
    $table->addColumn("name", "string", ["length" => 128]);
    $table->addColumn("start_date", "date");
    $table->addColumn("end_date", "date");
    $table->addColumn("tz_continent", "string", ["length" => 64]);
    $table->addColumn("tz_place", "string", ["length" => 64]);
    $table->addColumn("location", "string", ["length" => 64]);
    $table->addColumn("talks_count", "integer", ["unsigned" => true]);
    $table->addColumn('new_speakers', 'integer', ['unsigned' => true]);
    $table->setPrimaryKey(["uri"]);
//    $table->addUniqueIndex(["username"]);
//    $schema->createSequence("users_seq");
    $sm->dropAndCreateTable($table);

    $schema = new Schema();
    $table = $schema->createTable('talk');
    $table->addColumn("uri", "string", ["length" => 128]);
    $table->addColumn("url_friendly_talk_title", "string", ["length" => 128]);
    $table->addColumn("event_uri", "string", ["length" => 128]);
    $table->addColumn("talk_title", "string", ["length" => 128]);
    $table->addColumn("type", "string", ["length" => 128]);
    $table->addColumn("duration", "integer", ["unsigned" => true]);
    $table->addColumn("speaker", "string", ["length" => 128]);
    $table->addColumn("average_rating", "integer", ["unsigned" => true]);
    $table->setPrimaryKey(["uri"]);
    $table->addForeignKeyConstraint('event', ['event_uri'], ['uri']);
    $sm->dropAndCreateTable($table);

    $schema = new Schema();
    $table = $schema->createTable('first_appearance');
    $table->addColumn("speaker", "string", ["length" => 128]);
    $table->addColumn("event_uri", "string", ["length" => 128]);
    $table->addColumn("event_date", "date");
    $table->addColumn("event_name", "string", ["length" => 128]);
    $table->setPrimaryKey(["speaker"]);
    $table->addForeignKeyConstraint('event', ['event_uri'], ['uri']);
    $sm->dropAndCreateTable($table);

}

init();