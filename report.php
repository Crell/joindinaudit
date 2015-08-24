<?php

require 'vendor/autoload.php';

use Crell\HtmlModel\HtmlPage;

/**
 * Generates an HTML report of the data.
 */
function report()
{
    $page = new HtmlPage();
    $page = $page->withTitle('Conference audit');

    // Generate the results page.
    $table = reportNewSpeakersPerCon();
    $page = $page->withContent($page->getContent() . $table);

    $table = reportTopSpeakers();
    $page = $page->withContent($page->getContent() . $table);

    file_put_contents('results.html', $page);
}

function reportTopSpeakers()
{
    $conn = getDb();

    $stmt = $conn->executeQuery("SELECT speaker, COUNT(speaker) AS appearances
        FROM talk
          WHERE speaker <> ''
        GROUP BY speaker
        HAVING appearances >= 20
        ORDER BY appearances DESC, speaker DESC");

    $header = ['Speaker', 'Appearances (since 2011)'];

    return makeHtmlTable('Most popular speakers', $header, $stmt->fetchAll());
}


function reportNewSpeakersPerCon()
{
    $conn = getDb();

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
