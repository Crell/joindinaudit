<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Crell\HtmlModel\HtmlPage;

/**
 * Initializes the database with a fresh, empty schema.
 */
function audit()
{
    $conn = getDb();

    // Calculate data!
    //makeFirstAppearanceIndex();
    computeSpeakersPerCon();
    computeNewSpeakersPerCon();

    // Generate the results page.
    $table = reportNewSpeakersPerCon();

    $page = new HtmlPage($table);
    $page->withTitle('Conference audit');

    file_put_contents('results.html', $page);
}


function reportNewSpeakersPerCon()
{
    $conn = getDb();

    $queryBuilder = $conn->createQueryBuilder();


    $stmt = $conn->executeQuery("SELECT event.start_date, event.name, talks_count, num_speakers, new_speakers, FORMAT((new_speakers/event.num_speakers)*100, 1) AS percent_new
        FROM event
        WHERE start_date >= '2011-01-01'
        ORDER BY start_date");

    $header = ['Date', 'Event', 'Total sessions', 'Speakers', 'New speakers', 'Percent new'];

    return makeHtmlTable('First time speakers', $header, $stmt->fetchAll());

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

audit();
