<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Connection;

/**
 * Initializes the database with a fresh, empty schema.
 */
function audit()
{
    $conn = getDb();


    //ensureFirstAppearanceTable();
    //makeFirstAppearanceIndex();


    computeNewSpeakersPerCon();

}

function computeNewSpeakersPerCon()
{
    $conn = getDb();

    $conn->executeQuery("UPDATE event SET new_speakers = (SELECT COUNT(*) FROM (SELECT DISTINCT talk.speaker
            FROM event e2
              INNER JOIN talk ON event.url_friendly_name = talk.event
              INNER JOIN first_appearance ON event.url_friendly_name=first_appearance.event
                                             AND talk.speaker=first_appearance.speaker
            WHERE e2.url_friendly_name = event.url_friendly_name) AS stuff)");

}

function reportNewSpeakersPerCon()
{
    $conn = getDb();

    $result = $conn->executeQuery("SELECT DISTINCT url_friendly_name, name, start_date, talks_count
      FROM event
      WHERE start_date >= '2011-01-01'
      ORDER BY start_date");

    $stmt = $conn->prepare("SELECT COUNT(*) FROM (SELECT DISTINCT talk.speaker
            FROM event
              INNER JOIN talk ON event.url_friendly_name = talk.event
              INNER JOIN first_appearance ON event.url_friendly_name=first_appearance.event
                                             AND talk.speaker=first_appearance.speaker
            WHERE event.url_friendly_name = :event) AS stuff");

    foreach ($result as $event) {
        $stmt->execute(['event' => $event['url_friendly_name']]);

        $count = $stmt->fetchColumn();

        printf("%s: %s\t%d of %d\n", $event['start_date'], $event['name'], $count, $event['talks_count']);
    }
}

function ensureFirstAppearanceTable()
{
    $conn = getDb();

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager $sm */
    $sm = $conn->getSchemaManager();

    if ($sm->tablesExist('first_appearance')) {
        $sm->dropTable('first_appearance');
    }

    $schema = new Schema();
    $table = $schema->createTable('first_appearance');
    $table->addColumn("speaker", "string", ["length" => 128]);
    $table->addColumn("event", "string", ["length" => 64]);
    $table->addColumn("event_date", "date");
    $table->addColumn("event_name", "string", ["length" => 128]);
    $table->setPrimaryKey(["speaker"]);
    $table->addForeignKeyConstraint('event', ['event'], ['url_friendly_name']);
    $sm->dropAndCreateTable($table);
}

function makeFirstAppearanceIndex()
{
    $conn = getDb();

    $conn->transactional(function(Connection $conn) {
        $result = $conn->executeQuery("SELECT DISTINCT speaker FROM talk");

        $stmt = $conn->prepare("INSERT INTO first_appearance
                SELECT talk.speaker as speaker, event.url_friendly_name as event, event.start_date as event_date, event.name as event_name
                FROM event
                  INNER JOIN talk ON event.url_friendly_name = talk.event
                WHERE talk.speaker = :name
                ORDER BY event.start_date
                LIMIT 1");

        foreach ($result as $record) {
            $stmt->execute([
                'name' => $record['speaker']
            ]);
        }
    });

}

audit();
