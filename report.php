<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
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

    return makeHtmlTable('First time speakers', $header, $stmt->fetchAll());

}

report();
