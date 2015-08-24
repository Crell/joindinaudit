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

    //makeFirstAppearanceIndex();

    reportNewSpeakersPerCon();
    //computeNewSpeakersPerCon();

}

function computeNewSpeakersPerCon()
{
    $conn = getDb();

    $conn->executeQuery("UPDATE event SET new_speakers = (SELECT COUNT(*) FROM (SELECT DISTINCT talk.speaker
            FROM event e2
              INNER JOIN talk ON event.url_friendly_name = talk.event_uri
              INNER JOIN first_appearance ON event.url_friendly_name=first_appearance.event_uri
                                             AND talk.speaker=first_appearance.speaker
            WHERE e2.url_friendly_name = event.url_friendly_name) AS stuff)");

}

function reportNewSpeakersPerCon()
{
    $conn = getDb();

    $result = $conn->executeQuery("SELECT DISTINCT uri, name, start_date, talks_count
      FROM event
      WHERE start_date >= '2011-01-01'
      ORDER BY start_date");

    $stmt = $conn->prepare("SELECT COUNT(*) FROM (SELECT DISTINCT talk.speaker
            FROM event
              INNER JOIN talk ON event.uri = talk.event_uri
              INNER JOIN first_appearance ON event.uri = first_appearance.event_uri
                                             AND talk.speaker = first_appearance.speaker
            WHERE event.uri = :event) AS stuff");

    foreach ($result as $event) {
        $stmt->execute(['event' => $event['uri']]);

        $count = $stmt->fetchColumn();

        printf("%s: %s\t%d of %d\n", $event['start_date'], $event['name'], $count, $event['talks_count']);
    }
}

function makeFirstAppearanceIndex()
{
    $conn = getDb();

    $conn->transactional(function(Connection $conn) {
        $conn->executeQuery("DELETE FROM first_appearance");

        $result = $conn->executeQuery("SELECT DISTINCT speaker FROM talk");

        $stmt = $conn->prepare("INSERT INTO first_appearance
                SELECT talk.speaker as speaker, event.uri as event_uri, event.start_date as event_date, event.name as event_name
                FROM event
                  INNER JOIN talk ON event.uri = talk.event_uri
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
