<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Crell\HtmlModel\HtmlPage;

/**
 * Computes derivative data from the raw information.
 */
function derive()
{
    $conn = getDb();

    //makeFirstAppearanceIndex();
    computeSpeakersPerCon();
    computeNewSpeakersPerCon();

}

function computeSpeakersPerCon()
{
    $conn = getDb();

    $conn->transactional(function(Connection $conn) {
        $result = $conn->executeQuery("SELECT DISTINCT uri, name, start_date, talks_count
          FROM event
          ORDER BY start_date");

        $stmt = $conn->prepare("SELECT COUNT(*) FROM (SELECT DISTINCT talk.speaker
            FROM event
              INNER JOIN talk ON event.uri = talk.event_uri
            WHERE event.uri = :event) AS stuff");

        foreach ($result as $event) {
            $stmt->execute(['event' => $event['uri']]);
            $count = $stmt->fetchColumn();
            $conn->update('event', ['num_speakers' => $count], ['uri' => $event['uri']]);
        }
    });

}

function computeNewSpeakersPerCon()
{
    $conn = getDb();

    $conn->transactional(function(Connection $conn) {
        $result = $conn->executeQuery("SELECT DISTINCT uri, name, start_date, talks_count
          FROM event
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
            $conn->update('event', ['new_speakers' => $count], ['uri' => $event['uri']]);
        }
    });
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

derive();
