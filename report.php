<?php

require 'vendor/autoload.php';

use Crell\HtmlModel\HtmlPage;

/**
 * Generates an HTML report of the data.
 */
function report()
{
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

    $table = makeHtmlTable('First time speakers', $header, $stmt->fetchAll());

    $stmt = $conn->executeQuery("SELECT FORMAT(AVG(percent_new), 1) FROM (
        SELECT event.start_date, event.name, talks_count, num_speakers, new_speakers, FORMAT((new_speakers/event.num_speakers)*100, 1) AS percent_new
        FROM event
        WHERE start_date >= '2011-01-01'
        ORDER BY start_date) AS stuff");

    $average = $stmt->fetchColumn();

    return $table . "<p>Average new speaker percentage: {$average}</p>\n";
}



report();
